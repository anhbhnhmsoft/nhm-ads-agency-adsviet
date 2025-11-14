<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Service\BinanceService;

class DashboardController extends Controller
{
    // Hiển thị số dư USDT từ Binance
    public function index(BinanceService $binanceService)
    {
        $balance = $binanceService->getUsdtSpotBalance();
        $data = [];
        if ($balance->isSuccess()) {
            $data['binance'] = $balance->getData();
        } else {
            $data['binanceError'] = $balance->getMessage();
        }
        return $this->rendering('dashboard/index', $data);
    }

}
