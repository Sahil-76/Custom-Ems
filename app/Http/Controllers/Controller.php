<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\Response;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    function fileDownload($folder, $fileName)
    {
        if (request()->has('subFolder')) {
            $subFolder = str_replace('%2F','/',request()->subFolder);
            $path = storage_path("app/$folder/$subFolder/$fileName");
        }else{
            $path = storage_path("app/$folder/$fileName");
        }
        if (file_exists($path)) {
            return Response::download($path);
        } else {
            return 'file not found.';
        }
    }
}
