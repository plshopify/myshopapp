<?php

namespace App\Http\Controllers;

use App\Models\ShopDetail;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ThemeController extends Controller
{
    public function getThemes(Request $request)
    {
        $shop = $request->shop;
        $themes = Theme::whereHas('shop_details', function($q) use ($shop) {
            $q->where('shop_url', $shop);
        })->get();
        return response()->json([
            'data' => $themes,
            'message' => 'Themes retrieved successfully'
        ], Response::HTTP_OK);
    }
}
