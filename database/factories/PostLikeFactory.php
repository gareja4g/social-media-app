<?php

namespace Database\Factories;

use App\Models\PostLike;
use App\Models\User;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostLikeFactory extends Factory
{
    protected $model = PostLike::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),  // The user liking the post
            'post_id' => Post::factory(),  // The post being liked
        ];
    }
}
