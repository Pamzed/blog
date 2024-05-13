<?php

namespace App\Http\Controllers\API\BlogPost;

use App\Http\Controllers\Controller;
use App\Models\BlogPosts;
use App\Models\Categories;
use App\Models\Comments;
use App\Models\PostViews;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BlogPostController extends Controller
{
    // ========== Blog Section ============== //
    /**
     * Returns json response
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchBlogPost(): JsonResponse
    {
        $posts = BlogPosts::latestWithRelations()->get();
        $posts->makeHidden([
            'updated_at',
            'created_at',
        ]);

        return $this->success($posts, 'Fetch all Post');
    }

    /**
     * Returns json response
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function blogDetail($id): JsonResponse
    {
        $ip_address = $_SERVER['REMOTE_ADDR'];

        // Check if the IP address has already viewed this post
        $existingView = PostViews::where('blog_posts_id', $id)
            ->where('ip_address', $ip_address)
            ->first();

        $post = BlogPosts::find($id);

        if ($post) {
            if (!$existingView) {
                // If the IP address hasn't viewed this post, increment the view count
                $post->blog_view_count++;
                $post->save();

                // Record the view
                $view = new PostViews;
                $view->blog_posts_id = $id;
                $view->ip_address = $ip_address;
                $view->save();
            }

            $postDetail = BlogPosts::where('id', $id)->latestWithFullRelations()->first();
            if (!is_null($postDetail)) {
                $postDetail->makeHidden([
                    'updated_at',
                    'created_at',
                ]);
                return $this->success($postDetail, 'Fetch Post Details');
            }
        }

        return $this->error('Post does not exist or has been deleted.', 404);
    }

    /**
     * Returns json response
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchTopPost(): JsonResponse
    {
        $posts = BlogPosts::orderBy('blog_view_count', 'desc')->latestWithRelations()->take(5)->get();
        $posts->makeHidden([
            'updated_at',
            'created_at',
        ]);

        return $this->success($posts, 'Fetch all top Post');
    }

    /**
     * Returns json response
     *
     * @param mixed $blog_id
     * @return JsonResponse
     */
    public function fetchRelatedPost($blog_id): JsonResponse
    {
        $post = BlogPosts::findOrFail($blog_id);
        $relatedPosts = $post->getRelatedPosts();
        $relatedPosts->makeHidden([
            'updated_at',
            'created_at',
        ]);

        return $this->success($relatedPosts, 'Fetch related posts');
    }

    /**
     * Returns json response
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addComment(Request $request): JsonResponse
    {
        if (is_null($request->blog_posts_id) || $request->blog_posts_id == ' ') {
            return $this->error('Blog Post ID is required');
        }
        if (is_null($request->comment) || $request->comment == ' ') {
            return $this->error('Comment content is required');
        }

        $blogPost = BlogPosts::where('id', $request->blog_posts_id)->first();

        if (is_null($blogPost)) {
            return $this->error('Blog Post does not exist', 404);
        }

        try {
            $comment = new Comments();
            $comment->content = $request->comment;
            $comment->blog_posts_id = $request->blog_posts_id;
            $comment->save();

            return $this->success($comment, 'Comment added successfully', 201);
        } catch (\Throwable $th) {
            return $this->error('Internal Error', 500, $th);
        }
    }
}
