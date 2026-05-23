<?php

namespace Tests\Unit;

use App\Service\CoinRemitterService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CoinRemitterServiceTest extends TestCase
{
    public function test_it_creates_invoice_with_official_coinremitter_headers_and_payload(): void
    {
        config()->set('services.coinremitter.base_url', 'https://api.coinremitter.test/v1');
        config()->set('services.coinremitter.invoice_expire_minutes', 30);
        config()->set('services.coinremitter.networks.TRC20', [
            'coin' => 'USDTTRC20',
            'api_key' => 'test-key',
            'password' => 'test-password',
        ]);

        Http::fake([
            'api.coinremitter.test/v1/invoice/create' => Http::response([
                'flag' => 1,
                'msg' => 'Invoice created',
                'data' => [
                    'invoice_id' => 'INV123',
                    'url' => 'https://coinremitter.test/invoice/INV123',
                    'address' => 'TTestAddress',
                    'status' => 0,
                ],
            ]),
        ]);

        $service = app(CoinRemitterService::class);
        $result = $service->createInvoice(
            network: 'TRC20',
            amount: 10.5,
            orderId: 'wallet_1',
            name: 'Wallet top up #wallet_1_extra_long_name',
            notifyUrl: 'https://app.test/webhooks/coinremitter',
            successUrl: 'https://app.test/wallets',
            failUrl: 'https://app.test/wallets',
        );

        $this->assertTrue($result->isSuccess(), $result->getMessage());
        $this->assertSame('INV123', $service->invoiceId($result->getData()));
        $this->assertSame('https://coinremitter.test/invoice/INV123', $service->invoiceUrl($result->getData()));
        $this->assertSame('TTestAddress', $service->payAddress($result->getData()));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.coinremitter.test/v1/invoice/create'
                && $request->hasHeader('X-Api-Key', 'test-key')
                && $request->hasHeader('X-Api-Password', 'test-password')
                && $request['amount'] === '10.5'
                && $request['fiat_currency'] === 'USD'
                && $request['expiry_time_in_minutes'] === '30'
                && $request['custom_data1'] === 'wallet_1'
                && $request['name'] === 'Wallet top up #wallet_1_extra_';
        });
    }

    public function test_it_reads_paid_and_failed_invoice_statuses(): void
    {
        $service = app(CoinRemitterService::class);

        $this->assertTrue($service->isPaidStatus(CoinRemitterService::STATUS_PAID));
        $this->assertTrue($service->isPaidStatus(CoinRemitterService::STATUS_OVER_PAID));
        $this->assertTrue($service->isFailedStatus(CoinRemitterService::STATUS_EXPIRED));
        $this->assertTrue($service->isFailedStatus(CoinRemitterService::STATUS_CANCELLED));
        $this->assertFalse($service->isPaidStatus(CoinRemitterService::STATUS_PENDING));
        $this->assertFalse($service->isFailedStatus(CoinRemitterService::STATUS_UNDER_PAID));
        $this->assertSame(CoinRemitterService::STATUS_EXPIRED, $service->status(['status' => 'Expired', 'status_code' => '4']));
        $this->assertSame(CoinRemitterService::STATUS_OVER_PAID, $service->status(['status' => 'Over Paid']));
    }
}
