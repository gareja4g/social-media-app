<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FollowerController extends BaseController
{
    /**
     * Follow a user.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function follow($id): JsonResponse
    {
        try {
            $userToFollow = User::findOrFail($id);

            if (Auth::id() === $userToFollow->id) {
                return $this->sendError('You cannot follow yourself', 400);
            }

            Auth::user()->following()->attach($userToFollow->id);

            // Send notification if the user has enabled "follow" notifications
            if ($userToFollow->notification_preferences['follow'] ?? true) {
                Notification::create([
                    'user_id' => $userToFollow->id,
                    'type' => 'follow',
                    'message' => Auth::user()->full_name . ' started following you.',
                ]);
            }

            return $this->sendSuccess([], 'User followed successfully');
        } catch (\Exception $e) {
            Log::error('Follow action failed', ['error' => $e->getMessage()]);
            return $this->sendError('An error occurred while trying to follow the user. Please try again later.', 500);
        }
    }

    /**
     * Unfollow a user.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function unfollow($id): JsonResponse
    {
        try {
            $userToUnfollow = User::findOrFail($id);

            // Detach the user from the following list
            Auth::user()->following()->detach($userToUnfollow->id);

            return $this->sendSuccess([], 'User unfollowed successfully');
        } catch (\Exception $e) {
            Log::error('Unfollow action failed', ['error' => $e->getMessage()]);
            return $this->sendError('An error occurred while trying to unfollow the user. Please try again later.', 500);
        }
    }

    /**
     * Get the list of followers of a user.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function followers($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $followers = $user->followers()->get();

            return $this->sendSuccess($followers, 'Followers fetched successfully');
        } catch (\Exception $e) {
            Log::error('Fetching followers failed', ['error' => $e->getMessage()]);
            return $this->sendError('An error occurred while trying to fetch the followers. Please try again later.', 500);
        }
    }

    /**
     * Get the list of users that a user is following.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function following($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $following = $user->following()->get();

            return $this->sendSuccess($following, 'Following list fetched successfully');
        } catch (\Exception $e) {
            Log::error('Fetching following failed', ['error' => $e->getMessage()]);
            return $this->sendError('An error occurred while trying to fetch the following list. Please try again later.', 500);
        }
    }
}
