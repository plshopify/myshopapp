<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\File;

class FileWriteService {

    private $content;
    public function writeFile($input)
    {
        $this->content = "document.getElementsByClassName('product-form__submit button')[0].innerHTML = `$input`;";
        File::put('js/myscript.js', $this->content);
    }
}
