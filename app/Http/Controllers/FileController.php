<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class FileController extends Controller
{
    /**
     * Serve a file from local storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function serve(Request $request)
    {
        $path = $request->query('path');

        if (!$path || !Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $file = Storage::disk('local')->get($path);
        $mimeType = Storage::disk('local')->mimeType($path);

        return Response::make($file, 200)->header("Content-Type", $mimeType);
    }
}
