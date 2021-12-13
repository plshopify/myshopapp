<?php

namespace App\Http\Controllers;

use App\Http\Services\FileWriteService;
use App\Models\Order;
use App\Models\ShopDetail;
use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

            // persist shop to the database
            $newShop = ShopDetail::create([
                'shop_url' => $shop,
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
            $node = $document->createTextNode('<link rel="stylesheet" href="{{ ' . "'" . 'custom_theme.css' . "'" . ' | asset_url }}" type="text/css">');
            $document->find('head', 0)->appendChild($node);
            $data = Http::withHeaders([
                'X-Shopify-Access-Token' => $newShop->shop_token,
            ])->put($shop . '/admin/api/2021-10/themes/126774870210/assets.json', [
                "asset" => [
                    "key" => "layout/theme.liquid",
                    "value" => $document->save()
                ]
            ]);

            // register script tag
            $data = Http::withHeaders([
                'X-Shopify-Access-Token' => $newShop->shop_token,
            ])->get($newShop->shop_url . '/admin/api/2021-10/script_tags.json');
            $src = $this->hostURL . '/storage/files/snowflake.js';
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
                ])->post($this->storeURL . '/admin/api/2021-10/script_tags.json', [
                    "script_tag" => [
                        "event" => "onload",
                        "src" => $src
                    ]
                ]);
                return $data->json();
            }
            return redirect()->to($this->AppURL . '?shop=' . $shop);
        }
        return redirect()->to($this->AppURL . '?shop=' . $shop);
    }

    public function applyChanges(Request $request)
    {
        $shop = $request->shop;
        $shopData = ShopDetail::firstWhere('shop_url', $shop);
        // if(!$shopData) {
        //     return response()->json([
        //         'message' => 'Unauthorised',
        //     ], Response::HTTP_UNAUTHORIZED);
        // }
        $this->writeFileService->writeToFile($request->all());
        $backgroundColor = $request->color;
        $fontFamily = $request->font;
        $data = Http::withHeaders([
            'X-Shopify-Access-Token' => $shopData->shop_token,
        ])->put($shopData->shop_url . '/admin/api/2021-10/themes/126774870210/assets.json', [
            "asset" => [
                "key" => "assets/custom_theme.css",
                "value" => "button, .button, .btn {
    background-color: $backgroundColor;
}
body, h1, h2, h3, h4, h5, h6, p, div, span, a, button {
    font-family: '$fontFamily' !Important;
}
"
            ]
        ]);
        return response()->json([
            'message' => 'Changes saved'
        ], Response::HTTP_OK);
    }

    public function initScriptTag(Request $request)
    {
        $shopData = ShopDetail::firstWhere('shop_url', $request->shop);
        $data = Http::withHeaders([
            'X-Shopify-Access-Token' => $shopData->shop_token,
        ])->get($this->storeURL . '/admin/api/2021-10/script_tags.json');
        $src = $this->hostURL . '/storage/files/snowflake.js';
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
                'X-Shopify-Access-Token' => $shopData->shop_token,
            ])->post($this->storeURL . '/admin/api/2021-10/script_tags.json', [
                "script_tag" => [
                    "event" => "onload",
                    "src" => $src
                ]
            ]);
            return $data->json();
        }
        return [
            'message' => 'Script already exist!',
            'script' => $src
        ];
    }
}
