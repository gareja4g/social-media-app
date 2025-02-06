<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends BaseController
{
    /**
     * Search for users by name or email and check if they are followed.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchUser(Request $request): JsonResponse
    {
        try {
            $query = $request->input('search');
            $authUserId = auth()->id(); // Get authenticated user ID

            // Fetch public users and check if the authenticated user follows them
            $users = User::where('profile_visibility', 'public')->where("id", "!=", $authUserId)
                ->withExists(['followers as is_following' => function ($query) use ($authUserId) {
                    $query->where('follower_id', $authUserId);
                }]);

            // Apply search filter if query exists
            if (!empty($query)) {
                $users->where('user_name', 'like', '%' . $query . '%');
            }

            // Get all matching users
            $users = $users->get();

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => $users->isNotEmpty() ? 'Users found' : 'No users found',
            ]);
        } catch (\Exception $e) {
            Log::error('User search failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching for users.' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Get the profile of the authenticated user.
     *
     * @return JsonResponse
     */
    public function profile(): JsonResponse
    {
        try {
            $user = Auth::user();
            $user->loadCount(['followers', 'following', 'posts']);

            return $this->sendSuccess($user, 'Profile fetched successfully');
        } catch (\Exception $e) {
            Log::error('Failed to fetch user profile', ['error' => $e->getMessage()]);
            return $this->sendError('An error occurred while fetching the profile.', 500);
        }
    }

    /**
     * Update the profile of the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $rules = [
            'bio' => 'nullable|string|max:500',
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'profile_visibility' => 'required|in:public,private,friends-only',
            'post_visibility' => 'required|in:public,private,friends-only',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', 422, $validator->errors());
        }

        try {
            $user = Auth::user();
            $user->update($request->only(['first_name', 'email', 'last_name', 'bio', 'profile_visibility', 'post_visibility']));

            // Handle profile and cover photo uploads
            if ($request->hasFile('profile_picture')) {
                $profilePicturePath = $request->file('profile_picture')->store('profile_pictures', 'public');
                $user->profile_picture = $profilePicturePath;
            }

            if ($request->hasFile('cover_photo')) {
                $coverPhotoPath = $request->file('cover_photo')->store('cover_photos', 'public');
                $user->cover_photo = $coverPhotoPath;
            }

            $user->save();

            return $this->sendSuccess($user, 'Profile updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update user profile', ['error' => $e->getMessage()]);
            return $this->sendError('An error occurred while updating the profile.', 500);
        }
    }

    /**
     * Change the password for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'old_password' => ['required', 'string', 'min:8'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:old_password']
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', 422, $validator->errors());
        }

        try {
            // Get the authenticated user
            $user = Auth::user();

            // Check if old password is correct
            if (!Hash::check($request->old_password, $user->password)) {
                return $this->sendError('Old password is incorrect', 400);
            }

            // Update the user's password
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return $this->sendSuccess([], 'Password changed successfully');
        } catch (\Exception $e) {
            Log::error('Failed to change password', ['error' => $e->getMessage()]);
            return $this->sendError('An error occurred while changing the password.', 500);
        }
    }

    /**
     * Follow a user.
     *
     * @param $id
     * @return JsonResponse
     */
    public function followUser($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            Auth::user()->following()->attach($user->id);

            return $this->sendSuccess([], 'User followed successfully');
        } catch (\Exception $e) {
            Log::error('Failed to follow user', ['error' => $e->getMessage(), 'user_id' => $id]);
            return $this->sendError('An error occurred while following the user.', 500);
        }
    }

    /**
     * Unfollow a user.
     *
     * @param $id
     * @return JsonResponse
     */
    public function unfollowUser($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            Auth::user()->following()->detach($user->id);

            return $this->sendSuccess([], 'User unfollowed successfully');
        } catch (\Exception $e) {
            Log::error('Failed to unfollow user', ['error' => $e->getMessage(), 'user_id' => $id]);
            return $this->sendError('An error occurred while unfollowing the user.', 500);
        }
    }

    /**
     * Update notification preferences.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'follow' => 'boolean',
                'like' => 'boolean',
                'comment' => 'boolean',
            ]);

            $user->notification_preferences = $validated;
            $user->save();

            return $this->sendSuccess([], 'Notification preferences updated');
        } catch (\Exception $e) {
            Log::error('Failed to update notification preferences', ['error' => $e->getMessage()]);
            return $this->sendError('An error occurred while updating notification preferences.', 500);
        }
    }
}
