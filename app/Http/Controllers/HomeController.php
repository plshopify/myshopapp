<?php

namespace App\Http\Controllers;

use App\Http\Services\FileWriteService;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class HomeController extends Controller
{
    private $token;
    private $storeURL;
    private $hostURL;
    private $writeFileService;
    public function __construct(FileWriteService $writeService)
    {
        $this->token = env('SHOPIFY_TOKEN');
        $this->storeURL = env('STORE_URL');
        $this->hostURL = env('HOST_URL');
        $this->writeFileService = $writeService;
    }
    public function index()
    {

    }

    public function writeToFile(Request $request)
    {
        return $this->writeFileService->writeToFile($request->all());
    }

    public function initScriptTag()
    {
        $data = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->token,
        ])->get($this->storeURL . '/admin/api/2021-10/script_tags.json');
        $src = $this->hostURL . '/js/myscript.js';
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
