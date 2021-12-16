<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Theme extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'theme_name', 'theme_description', 'theme_image', 'theme_version'
    ];

    public function shop_details()
    {
        return $this->belongsToMany('App\Models\ShopDetail', 'shop_detail_theme', 'theme_id', 'shop_detail_id')
            ->withPivot('effect', 'color', 'font_family', 'applied')->withTimestamps();
    }

    public function shop_details_reviews()
    {
        return $this->belongsToMany('App\Models\ShopDetail', 'reviews', 'theme_id', 'shop_detail_id')
            ->withPivot('rating', 'review')->withTimestamps();
    }
}
