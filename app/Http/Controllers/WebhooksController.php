<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebhooksController extends Controller
{
    public function github(Request $request)
    {
        file_put_contents('data.json', json_encode($request->all()));
        return response()->json();
    }
}
