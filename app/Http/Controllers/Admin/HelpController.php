<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HelpController extends Controller
{
    public function index()
    {
        $data = collect(Storage::disk('assets')->files('help'))
            ->mapWithKeys(function ($path) {
                $basename = basename($path);
                return [str_replace('.html', '', $basename) => Storage::disk('assets')->get('help/' . $basename)];
            });

        return response()->json($data);
    }
}
