<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class Post extends Model
{
    use HasFactory, SoftDeletes;
    protected $appends = ['created_at_human', 'is_liked_by_user', 'media_url'];

    protected $fillable = ['user_id', 'content', 'media'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class);
    }

    public function userLikes()
    {
        return $this->belongsToMany(User::class, 'post_likes')->withTimestamps();
    }

    public function getCreatedAtHumanAttribute()
    {
        return $this->created_at->diffForHumans(); // Return human-readable format
    }

    public function getIsLikedByUserAttribute()
    {
        return auth()->check() ? $this->likes()->where('user_id', auth()->id())->exists() : false;
    }

    public function getMediaUrlAttribute()
    {
        $media = $this->media
            ? (Str::startsWith($this->media, 'https://')
                ? null
                : asset(Storage::url($this->media)))
            : null;
        return $media;
    }
}
