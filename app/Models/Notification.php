<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = ['created_at_human'];

    protected $fillable = ['user_id', 'type', 'message', 'read'];

    protected $casts = ['read' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getCreatedAtHumanAttribute()
    {
        return $this->created_at->diffForHumans(); // Return human-readable format
    }
}
