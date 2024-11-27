<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ZenMoneyProfileController extends Controller
{
    public function index()
    {
        try {
            // Здесь будет логика получения данных из API ZenMoney
            $zenMoneyData = [
                'accounts' => [], // Список счетов
                'tags' => [],     // Категории расходов/доходов
                'user' => [],     // Информация о пользователе
                'instrument' => [] // Валюты
            ];

            return view('admin.zenmoney.profile', compact('zenMoneyData'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Не удалось получить данные из ZenMoney API']);
        }
    }
}
