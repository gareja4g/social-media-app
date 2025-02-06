<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Str;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'user_name',
        'email',
        'profile_picture',
        'cover_photo',
        'bio',
        'profile_visibility',
        'post_visibility',
        'password',
        'notification_preferences'
    ];

    protected $appends = ['full_name', 'profile_picture_url', 'cover_photo_url'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
        ];
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'following_id', 'follower_id')->withTimestamps();
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'following_id')->withTimestamps();
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class)->orderBy("read");
    }

    public function getProfilePictureUrlAttribute()
    {
        // If profile picture starts with 'https://', return it as is
        $profile_picture = $this->profile_picture
            ? (Str::startsWith($this->profile_picture, 'http')
                ? 'avatar.jpg'
                : Storage::url($this->profile_picture))
            : 'avatar.jpg';

        return asset($profile_picture);
    }

    public function getCoverPhotoUrlAttribute()
    {
        // If cover photo starts with 'https://', return it as is
        $cover_photo = $this->cover_photo
            ? (Str::startsWith($this->cover_photo, 'http')
                ? 'cover.jpg'
                : Storage::url($this->cover_photo))
            : 'cover.jpg';

        return asset($cover_photo);
    }
}
