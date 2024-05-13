<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admins;
use App\Models\BlogPosts;
use App\Models\Categories;
use App\Models\Comments;
use App\Models\PostViews;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Str;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Returns json response
     * 
     * @return mixed
     */
    private function adminId(): mixed
    {
        $user = auth()->user()->id;
        return $user;
    }

    /**
     * Returns json response
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        return $this->success(null, 'Logged out', 202);
    }

    /**
     * Returns json response
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function postList(): JsonResponse
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
    public function postDetails($id): JsonResponse
    {
        // $ip_address = $_SERVER['REMOTE_ADDR'];
        // // Check if the IP address has already viewed this post
        // $existingView = PostViews::where('blog_posts_id', $id)
        //     ->where('ip_address', $ip_address)
        //     ->first();

        // if (!$existingView) {
        //     // If the IP address hasn't viewed this post, increment the view count
        //     $post = BlogPosts::find($id);
        //     $post->blog_view_count++;
        //     $post->save();

        //     // Record the view
        //     $view = new PostViews;
        //     $view->blog_posts_id = $id;
        //     $view->ip_address = $ip_address;
        //     $view->save();
        // }

        $postDetail = BlogPosts::where('id', $id)->latestWithFullRelations()->first();
        if (is_null($postDetail)) {
            return $this->error('Post does not exist or has been deleted.', 404);
        }
        $postDetail->makeHidden([
            'updated_at',
            'created_at',
        ]);

        return $this->success($postDetail, 'Fetch Post Details');
    }

    /**
     * Returns json response
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createPost(Request $request): JsonResponse
    {
        if (is_null($request->blog_title) || $request->blog_title == ' ') {
            return $this->error('Developer Error: Blog Title is required');
        }
        if (is_null($request->blog_image) || $request->blog_image == ' ') {
            return $this->error('Developer Error: Blog Image is required');
        }
        if (is_null($request->blog_feature_image) || $request->blog_feature_image == ' ') {
            return $this->error('Developer Error: Blog Feature Image is required');
        }
        if (is_null($request->blog_short_description) || $request->blog_short_description == ' ') {
            return $this->error('Developer Error: Blog Short Description is required');
        }
        if (is_null($request->blog_content) || $request->blog_content == ' ') {
            return $this->error('Developer Error: Blog Content is required');
        }
        if (is_null($request->blog_categories) || $request->blog_categories == ' ') {
            return $this->error('Developer Error: Blog Categories is required');
        }

        if ($request->hasFile('blog_image') && $request->hasFile('blog_feature_image')) {
            $pattern = '/\.(jpg|png|jpeg)$/i';

            $fileOne = $request->file('blog_image');
            $fileTwo = $request->file('blog_feature_image');

            $exNameOne = $fileOne->getClientOriginalExtension();
            $exNameTwo = $fileTwo->getClientOriginalExtension();

            if (is_null($exNameOne) || is_null($exNameTwo)) {
                return $this->error('Image does not have an extension name');
            }

            $currentUser = auth()->user();
            $author = Admins::where('id', "{$currentUser->id}")->first();

            // Get the file size in bytes
            $fileSizeOne = $fileOne->getSize();
            $fileSizeTwo = $fileTwo->getSize();

            // Convert to MB
            $fileSizeMBOne = $fileSizeOne / (1024 * 1024);
            $fileSizeMBTwo = $fileSizeTwo / (1024 * 1024);

            // Check if the image is greater than 5 MB
            if ($fileSizeMBOne > 5 || $fileSizeMBTwo > 5) {
                return $this->error('Image size cannot be greater than 5MB');
            }

            if (preg_match($pattern, ".{$exNameOne}") || preg_match($pattern, ".{$exNameTwo}")) {
                $uploadedFileUrlOne = Cloudinary::upload($fileOne->getRealPath())->getSecurePath();
                $uploadedFileUrlTwo = Cloudinary::upload($fileTwo->getRealPath())->getSecurePath();

                // create post
                $blogPost = BlogPosts::create([
                    'blog_title' => $request->blog_title,
                    'blog_image' => $uploadedFileUrlOne,
                    'blog_feature_image' => $uploadedFileUrlTwo,
                    'blog_short_description' => $request->blog_short_description,
                    'blog_content' => $request->blog_content,
                    'blog_author' => $author->full_name,
                    'admins_id' => $author->id,
                ]);

                $wordsArray = explode(" ", $request->blog_categories);
                $convertToJson = json_encode($wordsArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $categories = json_decode($convertToJson, true);

                $post = BlogPosts::where('id', $blogPost->id)->first();

                foreach ($categories as $categoryName) {
                    try {
                        // Check if the tag already exist.
                        $category = Categories::firstOrCreate([
                            'category_name' => $categoryName,
                            'blog_posts_id' => $post->id,
                        ]);

                        // Attach the tag to the event if not already attached
                        if (!$post->categories->contains($category->id)) {
                            $post->categories()->attach($category->id);
                        }
                    } catch (\Exception $e) {
                        return $this->error("checking tags: $e", 400, $e);
                    }
                }

                return $this->success($blogPost, 'Blog Post has been Created.', 201);
            } else {
                return $this->error('This File type is not supported.');
            }
        }
        return $this->error('Developer Error: Please read through the required params, image passing might be the issue.');
    }

    /**
     * Returns json response
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function editPost(Request $request): JsonResponse
    {
        if (is_null($request->blog_id) || $request->blog_id == ' ') {
            return $this->error('Developer Error: Blog ID is required');
        }
        $post = BlogPosts::where('id', $request->blog_id)->first();

        if (is_null($post)) {
            return $this->error('Post does not exist.', 404);
        } else {
            if ($request->hasFile('blog_image')) {
                $pattern = '/\.(jpg|png|jpeg)$/i';

                $fileOne = $request->file('blog_image');

                $exNameOne = $fileOne->getClientOriginalExtension();

                if (is_null($exNameOne)) {
                    return $this->error('Image does not have an extension name');
                }

                // Get the file size in bytes
                $fileSizeOne = $fileOne->getSize();

                // Convert to MB
                $fileSizeMBOne = $fileSizeOne / (1024 * 1024);

                // Check if the image is greater than 5 MB
                if ($fileSizeMBOne > 5) {
                    return $this->error('Image size cannot be greater than 5MB');
                }

                if (preg_match($pattern, ".{$exNameOne}")) {
                    $uploadedFileUrlOne = Cloudinary::upload($fileOne->getRealPath())->getSecurePath();

                    BlogPosts::where('id', $request->blog_id)->update([
                        'blog_title' => $request->blog_title ?? $post->blog_title,
                        'blog_image' => $uploadedFileUrlOne ?? $post->blog_image,
                        'blog_short_description' => $request->blog_short_description ?? $post->blog_short_description,
                        'blog_content' => $request->blog_content ?? $post->blog_content,
                    ]);

                    $wordsArray = explode(" ", $request->blog_categories);
                    $convertToJson = json_encode($wordsArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    $newCategories = json_decode($convertToJson, true);

                    $oldCategories = Categories::where('blog_posts_id', $post->id)->get();

                    // Change category names
                    foreach ($oldCategories as $key => $oldCategoryName) {
                        if (isset($newCategories[$key])) {
                            $newCategoryName = $newCategories[$key];

                            try {
                                // Find the category with the old name
                                $category = Categories::where('category_name', $oldCategoryName)->first();

                                // Update the category name
                                if ($category) {
                                    $category->category_name = $newCategoryName;
                                    $category->save();
                                }
                            } catch (\Exception $e) {
                                return $this->error("editing category: $e", 400, $e);
                            }
                        }
                    }

                    return $this->success(null, 'Blog Post has been edited.', 200);
                } else {
                    return $this->error('This File type is not supported.');
                }
            } else if ($request->hasFile('blog_feature_image')) {
                $pattern = '/\.(jpg|png|jpeg)$/i';

                $fileTwo = $request->file('blog_feature_image');

                $exNameTwo = $fileTwo->getClientOriginalExtension();

                if (is_null($exNameTwo)) {
                    return $this->error('Image does not have an extension name');
                }

                // Get the file size in bytes
                $fileSizeTwo = $fileTwo->getSize();

                // Convert to MB
                $fileSizeMBTwo = $fileSizeTwo / (1024 * 1024);

                // Check if the image is greater than 5 MB
                if ($fileSizeMBTwo > 5) {
                    return $this->error('Image size cannot be greater than 5MB');
                }

                if (preg_match($pattern, ".{$exNameTwo}")) {
                    $uploadedFileUrlTwo = Cloudinary::upload($fileTwo->getRealPath())->getSecurePath();

                    BlogPosts::where('id', $request->blog_id)->update([
                        'blog_title' => $request->blog_title ?? $post->blog_title,
                        'blog_feature_image' => $uploadedFileUrlTwo ?? $post->blog_feature_image,
                        'blog_short_description' => $request->blog_short_description ?? $post->blog_short_description,
                        'blog_content' => $request->blog_content ?? $post->blog_content,
                    ]);

                    $wordsArray = explode(" ", $request->blog_categories);
                    $convertToJson = json_encode($wordsArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    $newCategories = json_decode($convertToJson, true);

                    $oldCategories = Categories::where('blog_posts_id', $post->id)->get();

                    // Change category names
                    foreach ($oldCategories as $key => $oldCategoryName) {
                        if (isset($newCategories[$key])) {
                            $newCategoryName = $newCategories[$key];

                            try {
                                // Find the category with the old name
                                $category = Categories::where('category_name', $oldCategoryName)->first();

                                // Update the category name
                                if ($category) {
                                    $category->category_name = $newCategoryName;
                                    $category->save();
                                }
                            } catch (\Exception $e) {
                                return $this->error("editing category: $e", 400, $e);
                            }
                        }
                    }

                    return $this->success(null, 'Blog Post has been edited.', 200);
                } else {
                    return $this->error('This File type is not supported.');
                }
            } else {
                // create post
                BlogPosts::where('id', $request->blog_id)->update([
                    'blog_title' => $request->blog_title ?? $post->blog_title,
                    'blog_short_description' => $request->blog_short_description ?? $post->blog_short_description,
                    'blog_content' => $request->blog_content ?? $post->blog_content,
                ]);

                $wordsArray = explode(" ", $request->blog_categories);
                $convertToJson = json_encode($wordsArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $newCategories = json_decode($convertToJson, true);

                $oldCategories = Categories::where('blog_posts_id', $post->id)->get();

                // Remove existing categories if new categories are provided
                if (!empty($newCategories)) {
                    $post->categories()->detach();
                }

                foreach ($newCategories as $newCategoryName) {
                    try {
                        // Check if the category already exists
                        $category = Categories::firstOrCreate([
                            'category_name' => $newCategoryName,
                        ]);

                        // Attach the category to the blog post
                        $post->categories()->attach($category->id);
                    } catch (\Exception $e) {
                        return $this->error("checking tags: $e", 400, $e);
                    }
                }

                return $this->success(null, 'Blog Post has been edited.', 200);
            }
        }
    }

    /**
     * Returns json response
     * 
     * @param mixed $uuid
     * @return JsonResponse
     */
    public function deletePost($id): JsonResponse
    {
        if (is_null($id)) {
            return $this->error('Blog Post ID was not passed.');
        }

        $post = BlogPosts::where('id', $id)->first();

        if (is_null($post)) {
            return $this->error('Blog Post could not be found or does not exist.');
        }

        $post->comments()->delete();
        $post->categories()->detach();
        $post->postViews()->delete();
        $post->delete();

        return $this->success(null, 'Post Deleted deleted.');
    }

    /**
     * Returns json response
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function editProfile(Request $request): JsonResponse
    {
        $currentUser = auth()->user();
        $admin = Admins::where('id', "{$currentUser->id}")->first();

        if (is_null($admin)) {
            return $this->error('User does not exist');
        }

        if (is_null($request->full_name) || $request->full_name == ' ') {
            return $this->error('Full name cannot be empty');
        }

        try {
            Admins::where('id', "{$currentUser->id}")->update([
                'full_name' => $request->full_name,
            ]);

            return $this->success(null, 'Admin details updated');
        } catch (\Throwable $th) {
            return $this->error('Internal Error', 500, $th);
        }
    }

    /**
     * Returns json response
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchProfile(Request $request): JsonResponse
    {
        $currentUser = auth()->user();
        $admin = Admins::where('id', "{$currentUser->id}")->first();

        return $this->success($admin, 'Admin profile');
    }

    /**
     * Returns json response
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        $currentUser = auth()->user();
        $admin = Admins::where('id', "{$currentUser->id}")->first();

        if (is_null($admin)) {
            return $this->error('User does not exist');
        }

        if (is_null($request->currentPassword) || $request->currentPassword == ' ') {
            return $this->error('Current Password cannot be empty');
        }

        if (is_null($request->newPassword) || $request->newPassword == ' ') {
            return $this->error('New Password cannot be empty');
        }

        $hashPwd = Hash::check($request->currentPassword, $admin->password);

        if (!$hashPwd) {
            return $this->error('Current Password entered is incorrect');
        }

        try {
            Admins::where('id', "{$currentUser->id}")->update([
                'password' => Hash::make($request->newPassword),
            ]);

            return $this->success(null, 'Password has been changed.');
        } catch (\Throwable $th) {
            return $this->error('Internal Error', 500, $th);
        }
    }

    /**
     * Returns json response
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createModerator(Request $request): JsonResponse
    {

        if (is_null($request->full_name) || $request->full_name == ' ') {
            return $this->error('Full Name cannot be empty');
        }

        if (is_null($request->email) || $request->email == ' ') {
            return $this->error('Email cannot be empty');
        }

        if (is_null($request->password) || $request->password == ' ') {
            return $this->error('Password cannot be empty');
        }
        $currentUser = auth()->user();

        $mod = Admins::where('email', $request->email)->first();

        if ($currentUser->role == 'Admin') {
            if (!is_null($mod)) {
                return $this->error('A moderator is already using this email.');
            }
            try {
                Admins::create([
                    'full_name' => $request->full_name,
                    'email' => $request->email,
                    'role' => 'Moderator',
                    'password' => Hash::make($request->password),
                ]);

                return $this->success(null, 'Moderator have been created.');
            } catch (\Throwable $th) {
                return $this->error('Internal Error', 500, $th);
            }
        } else {
            return $this->error('You are not authorized to add anyone.');
        }
    }

    /**
     * Returns json response
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteModerator(Request $request): JsonResponse
    {

        if (is_null($request->mod_id) || $request->mod_id == ' ') {
            return $this->error('Moderator ID cannot be empty');
        }

        $currentUser = auth()->user();

        if ($currentUser->role == 'Admin') {
            $mod = Admins::where('id', $request->mod_id)->first();

            if ($mod->id == $currentUser->id) {
                return $this->error('Oh wow you are trying to delete yourself.', 401);
            }

            if (is_null($mod)) {
                return $this->error('Moderator does not exist', 404);
            }

            $mod->delete();
            return $this->success('You have deleted the moderator');
        } else {
            return $this->error('You are not authorized to delete anyone.');
        }
    }

    /**
     * Returns json response
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchAdmins(Request $request): JsonResponse
    {
        $admin = Admins::latest()->get(); // SELECT * FROM ADMINS ORDER BY DECS

        return $this->success($admin, 'All Admins');
    }

    /**
     * Returns json response
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function modifyModeratorRole(Request $request): JsonResponse
    {

        if (is_null($request->admin_id) || $request->admin_id == ' ') {
            return $this->error('Admin ID cannot be empty');
        }

        if (is_null($request->admin_role) || $request->admin_role == ' ') {
            return $this->error('Admin Role cannot be empty');
        }

        $admin = Admins::where('id', "{$request->admin_id}")->first();

        if (is_null($admin)) {
            return $this->error('User does not exist');
        }

        try {
            Admins::where('id', "{$request->admin_id}")->update([
                'role' => $request->admin_role,
            ]);

            return $this->success(null, 'Admin role have been updated.');
        } catch (\Throwable $th) {
            return $this->error('Internal Error', 500, $th);
        }
    }
}
