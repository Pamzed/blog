<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comments extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'blog_posts_id',
    ];

    public function blogPosts()
    {
        return $this->belongsTo(BlogPosts::class);
    }
}
