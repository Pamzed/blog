<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogPosts extends Model
{
    use HasFactory;

    protected $table = 'blog_posts';

    protected $fillable = [
        'blog_title',
        'blog_image',
        'blog_feature_image',
        'blog_short_description',
        'blog_content',
        'blog_view_count',
        'blog_author',

        'admins_id',
        'categories_id',
    ];

    public function scopeLatest(Builder $query)
    {
        return $query->orderBy(static::CREATED_AT, 'desc');
    }

    public function scopeLatestWithRelations(Builder $query)
    {
        return $query->latest()
            ->with('categories');
    }

    public function scopeLatestWithFullRelations(Builder $query)
    {
        return $query->latest()
            ->with('categories', 'comments')->latest();
    }

    public function comments()
    {
        return $this->hasMany(Comments::class);
    }

    public function postViews()
    {
        return $this->hasMany(PostViews::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Categories::class, 'blog_posts_categories')->withTimestamps();
    }

    // Then, you can fetch related posts like this:
    // public function getRelatedPosts($post)
    // {
    //     // Get the categories of the current post
    //     $categories = $post->categories;

    //     // Eager load posts for these categories
    //     $relatedPosts = BlogPosts::whereHas('categories', function ($query) use ($categories) {
    //         $query->whereIn('categories.id', $categories->pluck('id'));
    //     })
    //         ->where('id', '!=', $post->id) // Exclude the current post
    //         ->get();

    //     return $relatedPosts;
    // }

    public function getRelatedPosts()
    {
        // Get the category IDs of the current post
        $currentPostCategoryIds = $this->categories->pluck('id')->toArray();

        // Fetch related posts using a raw SQL query
        $relatedPosts = self::selectRaw('blog_posts.*')
            ->join('blog_posts_categories', 'blog_posts.id', '=', 'blog_posts_categories.blog_posts_id')
            ->join('categories', 'blog_posts_categories.categories_id', '=', 'categories.id')
            ->whereIn('categories.id', $currentPostCategoryIds)
            ->where('blog_posts.id', '!=', $this->id)
            ->with('categories')
            ->groupBy('blog_posts.id')
            ->get();

        return $relatedPosts;
    }
}
