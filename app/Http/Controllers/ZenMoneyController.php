<?php

namespace App\Http\Controllers;

use App\Services\ZenMoneyService;
use Illuminate\Http\JsonResponse;

class ZenMoneyController extends Controller
{
    protected ZenMoneyService $zenMoneyService;

    public function __construct(ZenMoneyService $zenMoneyService)
    {
        $this->zenMoneyService = $zenMoneyService;
    }

    public function test(): JsonResponse
    {
        try {
            $data = $this->zenMoneyService->getDiff();
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
