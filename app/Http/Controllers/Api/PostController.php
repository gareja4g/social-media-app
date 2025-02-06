<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\Notification;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PostController extends BaseController
{
    /**
     * Get all posts from the authenticated user with pagination.
     *
     * @return JsonResponse
     */
    public function getMyPosts(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $page = $request->input('page', 1);
            $postsPerPage = 5;

            $posts = Post::withCount(['likes', 'comments'])
                ->with('user')
                ->where('user_id', $user->id)
                ->paginate($postsPerPage, ['*'], 'page', $page);

            return $this->sendSuccess($posts, 'Posts fetched successfully');
        } catch (\Exception $e) {
            Log::error('Failed to fetch posts for user', ['error' => $e->getMessage()]);
            return $this->sendError('An error occurred while fetching posts.', 500);
        }
    }


    /**
     * Get posts relevant to the authenticated user (following and public).
     *
     * @return JsonResponse
     */
    public function getRelevantPosts(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $followingIds = $user->following()->pluck('users.id');
            $page = $request->input('page', 1);
            $postsPerPage = 5;

            $posts = Post::withCount(['likes', 'comments'])
                ->with(['user'])
                ->where(function ($query) use ($user, $followingIds) {
                    $query->whereHas('user', function ($query) {
                        $query->where('post_visibility', 'public');
                    })
                        ->orWhereIn('user_id', $followingIds);
                })
                ->paginate($postsPerPage, ['*'], 'page', $page);

            return $this->sendSuccess($posts, 'Relevant posts fetched successfully');
        } catch (\Exception $e) {
            Log::error('Failed to fetch relevant posts', ['error' => $e->getMessage()]);
            return $this->sendError('An error occurred while fetching relevant posts.', 500);
        }
    }

    /**
     * Store a new post.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $rules = [
            'content' => 'nullable|string',
            'media' => 'nullable|file|mimes:jpg,jpeg,png,mp4,mov,avi|max:10240',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', 422, $validator->errors());
        }

        try {
            $post = Auth::user()->posts()->create([
                'content' => $request->content,
                'media' => $request->file('media') ? $request->file('media')->store('posts', 'public') : null,
            ]);

            return $this->sendSuccess($post, 'Post created successfully');
        } catch (\Exception $e) {
            Log::error('Failed to create post', ['error' => $e->getMessage()]);
            return $this->sendError('An error occurred while creating the post.', 500);
        }
    }

    /**
     * Update an existing post.
     *
     * @param UpdatePostRequest $request
     * @param $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $rules =  [
            'content' => 'nullable|string',
            'media' => 'nullable|file|mimes:jpg,jpeg,png,mp4,mov,avi|max:10240',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', 422, $validator->errors());
        }

        try {
            $post = Post::where('user_id', Auth::id())->findOrFail($id);
            if ($request->hasFile('media')) {
                $mediaPath = $request->file('media')->store('posts', "public");
            } else {
                $mediaPath = $post->media;
            }

            $post->update([
                'content' => $request->content,
                'media' => $mediaPath,
            ]);

            return $this->sendSuccess($post, 'Post updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update post', ['error' => $e->getMessage(), 'post_id' => $id]);
            return $this->sendError('An error occurred while updating the post.', 500);
        }
    }

    /**
     * Delete an existing post.
     *
     * @param $id
     * @return JsonResponse
     */
    public function deletePost($id): JsonResponse
    {
        try {
            $post = Post::where('user_id', Auth::id())->findOrFail($id);
            $post->delete();

            return $this->sendSuccess([], 'Post deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete post', ['error' => $e->getMessage(), 'post_id' => $id]);
            return $this->sendError('An error occurred while deleting the post.', 500);
        }
    }

    /**
     * Like a post.
     *
     * @param $id
     * @return JsonResponse
     */
    public function likePost($id): JsonResponse
    {
        try {
            $post = Post::findOrFail($id);
            $post->userLikes()->attach(Auth::id());

            if ($post->user->notification_preferences['like'] ?? true) {
                Notification::create([
                    'user_id' => $post->user->id,
                    'type' => 'like',
                    'message' => Auth::user()->full_name . ' liked your post.',
                ]);
            }

            return $this->sendSuccess([], 'Post liked successfully');
        } catch (\Exception $e) {
            Log::error('Failed to like post', ['error' => $e->getMessage(), 'post_id' => $id]);
            return $this->sendError('An error occurred while liking the post.', 500);
        }
    }

    /**
     * Unlike a post.
     *
     * @param $id
     * @return JsonResponse
     */
    public function unlikePost($id): JsonResponse
    {
        try {
            $post = Post::findOrFail($id);
            $post->userLikes()->detach(Auth::id());

            return $this->sendSuccess([], 'Post unliked successfully');
        } catch (\Exception $e) {
            Log::error('Failed to unlike post', ['error' => $e->getMessage(), 'post_id' => $id]);
            return $this->sendError('An error occurred while unliking the post.', 500);
        }
    }

    /**
     * Add a comment to a post.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function commentOnPost(Request $request, $id): JsonResponse
    {
        $rules = [
            'comment' => 'required|string|max:500',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', 422, $validator->errors());
        }

        try {
            $post = Post::findOrFail($id);
            $comment = $post->comments()->create([
                'user_id' => Auth::id(),
                'comment' => $request->comment,
            ]);

            if ($post->user->notification_preferences['comment'] ?? true) {
                Notification::create([
                    'user_id' => $post->user->id,
                    'type' => 'comment',
                    'message' => Auth::user()->full_name . ' commented on your post.',
                ]);
            }

            return $this->sendSuccess($comment, 'Comment added successfully');
        } catch (\Exception $e) {
            Log::error('Failed to comment on post', ['error' => $e->getMessage(), 'post_id' => $id]);
            return $this->sendError('An error occurred while commenting on the post.', 500);
        }
    }

    /**
     * Get comments of a post.
     *
     * @param $id
     * @return JsonResponse
     */
    public function getPostComments($id): JsonResponse
    {
        try {
            $post = Post::findOrFail($id);
            $comments = $post->comments()->with('user:id,user_name,first_name,last_name')->get();

            return $this->sendSuccess($comments, 'Comments fetched successfully');
        } catch (\Exception $e) {
            Log::error('Failed to fetch comments for post', ['error' => $e->getMessage(), 'post_id' => $id]);
            return $this->sendError('An error occurred while fetching comments.', 500);
        }
    }
}
