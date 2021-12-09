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
    private $token;
    private $storeURL;
    private $hostURL;
    private $writeFileService;
    public function __construct(FileWriteService $writeService)
    {
        $this->storeURL = env('STORE_URL');
        $this->hostURL = env('HOST_URL');
        $this->writeFileService = $writeService;
    }
    public function installApp(Request $request)
    {
        $shop = $request->shop;
        $api_key = "46069c6b8e7cbb39309f352b3e7fefd1";
        $scopes = "read_orders,write_products,read_themes,write_themes";
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
        if(!$shopData) {
            $result = Http::post($shop . '/admin/oauth/access_token', [
                'client_id' => $apiKey,
                'client_secret' => $apiSecret,
                'code' => $code
            ]);
            $response = $result->json();
            $accessToken = $response['access_token'];
            ShopDetail::create([
                'shop_url' => $shop,
                'shop_token' => $accessToken
            ]);
            return redirect()->to('https://4d2c-162-12-210-2.ngrok.io');
        }
        return redirect()->to('https://4d2c-162-12-210-2.ngrok.io');
        // $data = Http::withHeaders([
        //     'X-Shopify-Access-Token' => $this->token,
        // ])->get($this->storeURL . '/admin/api/2021-10/themes/127784779970/assets.json', [
        //     "asset[key]" => "layout/theme.liquid"
        // ]);
        // return $data->json();
        // $themeLiquid = $data->json()['asset']['value'];
        // $document = HtmlDomParser::str_get_html($themeLiquid);
        // $base = $document->createTextNode('button');
        // dd($document->find('head', 0)->innertext);
    }

    public function applyChanges(Request $request)
    {
        $shop = $request->getHost();
        $shopData = ShopDetail::firstWhere('shop_url', $shop);
        if(!$shopData) {
            return response()->json([
                'message' => 'Unauthorised',
            ], Response::HTTP_UNAUTHORIZED);
        }
        $this->writeFileService->writeToFile($request->all());
        $backgroundColor = "hsl(120,100%,100%)";
        $data = Http::withHeaders([
            'X-Shopify-Access-Token' => $shopData->shop_token,
        ])->put($shopData->shop_url . '/admin/api/2021-10/themes/127784779970/assets.json', [
            "asset" => [
                "key" => "assets/custom_theme.css",
                "value" => "
                button {
                    background-color: $backgroundColor
                }
                "
            ]
        ]);
        return response()->json([
            'message' => 'Changes saved'
        ], Response::HTTP_OK);
    }

    public function initScriptTag()
    {
        $data = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->token,
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
                'X-Shopify-Access-Token' => $this->token,
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

    public function initWebHook()
    {
        $data = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->token,
        ])->get($this->storeURL . '/admin/api/2021-10/webhooks.json');
        $src = $this->hostURL . '/api/orders/create';
        $response = $data->json();
        $webHooks = $response['webhooks'];
        $webHookExist = false;
        foreach ($webHooks as $webHook) {
            if ($webHook['address'] == $src) {
                $src = $webHook;
                $webHookExist = true;
            }
        }
        if (!$webHookExist) {
            $data = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->token,
            ])->post($this->storeURL . '/admin/api/2021-10/webhooks.json', [
                "webhook" => [
                    "topic" => "orders/create",
                    "address" => $src,
                    "format" => "json"
                ]
            ]);
            return $data->json();
        }
        return [
            'message' => 'Webhook already exist!',
            'Webhook' => $src
        ];
    }

    public function orderCreate(Request $request)
    {
        Order::create($request->all());
    }
}
