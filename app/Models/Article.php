<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'article_category');
    }

    public function media()
    {
        return $this->hasMany(Media::class, 'article_id');
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($article) {
            $article->categories()->sync([]);
        });
    }
}
