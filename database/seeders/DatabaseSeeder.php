<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\Follower;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = User::factory(100)->create();

        $users->each(function ($user) {

            $posts = Post::factory(50)->create(['user_id' => $user->id]);


            $posts->each(function ($post) {

                $usersForLikes = User::inRandomOrder()->take(50)->pluck('id');

                foreach ($usersForLikes as $user_id) {
                    if ($user_id !== $post->user_id) {
                        PostLike::create([
                            'user_id' => $user_id,
                            'post_id' => $post->id,
                        ]);
                    }
                }
            });
        });

        // Create followers for each user
        $users->each(function ($user) {
            $followers = User::inRandomOrder()->take(50)->pluck('id');
            foreach ($followers as $follower_id) {
                if ($follower_id !== $user->id) {  // Prevent self-following
                    Follower::create([
                        'follower_id' => $follower_id,
                        'following_id' => $user->id,
                    ]);
                }
            }
        });
    }
}
