<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShopDetail extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'shop_name', 'shop_url', 'shop_image', 'shop_token', 'shop_status'
    ];

    protected $hidden = [
        'shop_token'
    ];

    public function themes()
    {
        return $this->belongsToMany('App\Models\Theme', 'shop_detail_theme', 'shop_detail_id', 'theme_id')
            ->withPivot('effect', 'color', 'font_family', 'applied')->withTimestamps();
    }

    public function themes_reviews()
    {
        return $this->belongsToMany('App\Models\Theme', 'reviews', 'shop_detail_id', 'theme_id')
            ->withPivot('rating', 'review')->withTimestamps();
    }
}
