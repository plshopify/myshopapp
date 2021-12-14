<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    use HasFactory;

    protected $fillable = [
        'theme_name', 'theme_description', 'theme_image', 'theme_version'
    ];

    public function shop_details()
    {
        return $this->belongsToMany('App\Models\ShopDetail', 'shop_detail_theme', 'theme_id', 'shop_detail_id')
        ->withPivot('effect', 'color', 'font_family');
    }
}
