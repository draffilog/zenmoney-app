<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class AdminSettingsController extends Controller
{
    public function index()
    {
        // Получаем токен напрямую из .env
        $token = env('ZENMONEY_API_TOKEN');

        return response()->json([
            'zenMoneyToken' => $token
        ]);
    }
}
