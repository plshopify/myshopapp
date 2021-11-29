<?php

namespace App\Http\Services;

use DOMDocument;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Sunra\PhpSimple\HtmlDomParser;

use function simplehtmldom_1_5\str_get_html;

class FileWriteService
{
    private $token;
    private $storeURL;
    private $hostURL;
    private $writeFileService;
    private $content;
    public function __construct()
    {
        $this->token = env('SHOPIFY_TOKEN');
        $this->storeURL = env('STORE_URL');
        $this->hostURL = env('HOST_URL');
    }
    public function writeToFile($input)
    {
        $this->content = File::get('files/snowflake.js');
        $this->content = str_replace(
            '<div id="snowflakeContainer"><p class="snowflake">*</p></div>',
            '<div id="snowflakeContainer"><p class="snowflake">' . $input['sign'] . '</p></div>',
            $this->content
        );
        File::put('files/snowflake.js', $this->content);
        return response()->json(['message' => 'Data written to file!'], 200);
    }
}
