<?php

namespace App\Http\Controllers;

use App\Models\ShopDetail;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ThemeController extends Controller
{
    private $shop;
    public function __construct(Request $request)
    {
        $this->shop = $request->shop;
    }
    public function getThemes()
    {
        $shop = $this->shop;
        $themes = Theme::whereHas('shop_details', function ($q) use ($shop) {
            $q->where('shop_url', $shop);
        })->get();
        return response()->json([
            'data' => $themes,
            'message' => 'Themes retrieved successfully'
        ], Response::HTTP_OK);
    }

    public function getThemeDetail($id)
    {
        $shop = $this->shop;
        $themeDetail = Theme::find($id);
        if (!$themeDetail) {
            return response()->json([
                'message' => 'Theme not found!',
            ], Response::HTTP_NOT_FOUND);
        }
        $callback = function ($q) use ($shop) {
            $q->where('shop_url', $shop);
        };
        $themeDetail = $themeDetail->whereHas('shop_details', $callback)->first();
        if (!$themeDetail) {
            return response()->json([
                'message' => 'Theme do not belongs to this shop!',
            ], Response::HTTP_BAD_REQUEST);
        }
        $themeDetail = $themeDetail->load(['shop_details' => $callback]);
        return response()->json([
            'data' => $themeDetail,
            'message' => 'Theme detail retrieved successfully'
        ], Response::HTTP_OK);
    }
}
