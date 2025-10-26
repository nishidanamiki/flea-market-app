<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'price', 'brand', 'description', 'img_url', 'condition'
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_item');
    }
}
