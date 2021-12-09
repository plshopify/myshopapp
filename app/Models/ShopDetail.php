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
        'shop_url', 'shop_token', 'shop_status'
    ];
}
