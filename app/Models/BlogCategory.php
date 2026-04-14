<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'category_id');
    }
}
