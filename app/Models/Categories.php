<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'category_name',
        'blog_posts_id',
    ];

    public function blogPosts()
    {
        return $this->belongsToMany(BlogPosts::class, 'blog_posts_categories');
    }
}
