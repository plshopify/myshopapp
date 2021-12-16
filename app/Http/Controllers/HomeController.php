<?php

namespace App\Http\Controllers;

use App\Http\Services\FileWriteService;
use App\Models\Order;
use App\Models\ShopDetail;
use DOMDocument;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Sunra\PhpSimple\HtmlDomParser;

class HomeController extends Controller
{
    private $AppURL;
    private $hostURL;
    private $writeFileService;
    public function __construct(FileWriteService $writeService)
    {
        $this->AppURL = env('SHOPIFY_APP_URL');
        $this->hostURL = env('HOST_URL');
        $this->writeFileService = $writeService;
    }
    public function installApp(Request $request)
    {
        $shop = $request->shop;
        $api_key = "46069c6b8e7cbb39309f352b3e7fefd1";
        $scopes = "read_orders,write_products,read_themes,write_themes,read_script_tags,write_script_tags";
        $redirect_uri = "http://rdp3.servnet.com.pk/public/";

        // Build install/approval URL to redirect to
        $install_url = "https://" . $shop . "/admin/oauth/authorize?client_id=" . $api_key . "&scope=" . $scopes . "&redirect_uri=" . urlencode($redirect_uri);
        return redirect()->to($install_url);
    }

    public function index(Request $request)
    {
        $shop = 'https://' . $request->shop;
        $code = $request->code;
        $apiKey = "46069c6b8e7cbb39309f352b3e7fefd1";
        $apiSecret = "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";
        $shopData = ShopDetail::firstWhere('shop_url', $shop);
        if (!$shopData) {

            // generating access token
            $result = Http::post($shop . '/admin/oauth/access_token', [
                'client_id' => $apiKey,
                'client_secret' => $apiSecret,
                'code' => $code
            ]);
            $response = $result->json();
            $accessToken = $response['access_token'];

            $data = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
            ])->get($shop . '/admin/api/2021-10/shop.json');

            $shopConfig = $data->json();

            // persist shop to the database
            $newShop = ShopDetail::create([
                'shop_name' => $shopConfig['shop']['name'],
                'shop_url' => $shop,
                'shop_image' => 'image',
                'shop_token' => $accessToken
            ]);

            // include custom_theme.css in theme.liquid
            $data = Http::withHeaders([
                'X-Shopify-Access-Token' => $newShop->shop_token,
            ])->get($shop . '/admin/api/2021-10/themes/126774870210/assets.json', [
                "asset[key]" => "layout/theme.liquid"
            ]);
            $themeLiquid = $data->json()['asset']['value'];
            $document = HtmlDomParser::str_get_html($themeLiquid, TRUE, TRUE, DEFAULT_TARGET_CHARSET, false);
            $node = $document->createTextNode('<link rel="stylesheet" href="{{ ' . "'" . 'themeapp_style.css' . "'" . ' | asset_url }}" type="text/css">');
            $document->find('head', 0)->appendChild($node);
            $data = Http::withHeaders([
                'X-Shopify-Access-Token' => $newShop->shop_token,
            ])->put($shop . '/admin/api/2021-10/themes/126774870210/assets.json', [
                "asset" => [
                    "key" => "layout/theme.liquid",
                    "value" => $document->save()
                ]
            ]);
            $fileData = $this->writeFileService->writeToFile($request->all(), $request->shop);
            // register script tag
            $data = Http::withHeaders([
                'X-Shopify-Access-Token' => $newShop->shop_token,
            ])->get($newShop->shop_url . '/admin/api/2021-10/script_tags.json');
            $src = $fileData['url'];
            $response = $data->json();
            $scriptTags = $response['script_tags'];
            $scriptExist = false;
            foreach ($scriptTags as $scriptTag) {
                if ($scriptTag['src'] == $src) {
                    $src = $scriptTag;
                    $scriptExist = true;
                }
            }
            if (!$scriptExist) {
                $data = Http::withHeaders([
                    'X-Shopify-Access-Token' => $newShop->shop_token,
                ])->post($newShop->shop_url . '/admin/api/2021-10/script_tags.json', [
                    "script_tag" => [
                        "event" => "onload",
                        "src" => $src
                    ]
                ]);
            }
            return redirect()->to($this->AppURL . '?shop=' . $shop . '&type=install');
        }
        return redirect()->to($this->AppURL . '?shop=' . $shop . '&type=openapp');
    }

    public function applyChanges(Request $request, $id)
    {
        $shop = $request->shop;
        $shopData = ShopDetail::firstWhere('shop_url', $shop);
        $shopName = preg_replace("(^https?://)", "", $shop );
        $this->writeFileService->writeToFile($request->all(), $shopName);
        $backgroundColor = $request->color;
        $fontFamily = $request->font_family;
        $data = Http::withHeaders([
            'X-Shopify-Access-Token' => $shopData->shop_token,
        ])->put($shopData->shop_url . '/admin/api/2021-10/themes/126774870210/assets.json', [
            "asset" => [
                "key" => "assets/themeapp_style.css",
                "value" => "button, .button, .btn {
    background-color: $backgroundColor;
}
body, h1, h2, h3, h4, h5, h6, p, div, span, a, button {
    font-family: '$fontFamily' !important;
}
"
            ]
        ]);
        DB::beginTransaction();
        try {
            $themeData = $shopData->themes()->wherePivot('applied', 1)->first();
            if ($themeData) {
                $shopData->themes()->updateExistingPivot($themeData->id, [
                    'applied' => 0
                ]);
            }
            $shopData->themes()->syncWithoutDetaching([$id => [
                'effect' => $request->sign,
                'color' => $request->color,
                'font_family' => $request->font_family,
                'applied' => 1
            ]]);
            DB::commit();
            return response()->json([
                'message' => 'Changes saved'
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
        }
    }
}
