<?php

namespace Tests\Unit;

use App\Common\Constants\Wallet\WalletTransactionStatus;
use App\Common\Constants\Wallet\WalletTransactionDescription;
use App\Core\ServiceReturn;
use App\Service\CoinRemitterService;
use App\Service\WalletTransactionService;
use Mockery;
use Tests\TestCase;

class CoinRemitterWebhookControllerTest extends TestCase
{
    public function test_webhook_health_check_returns_200_for_coinremitter_validator(): void
    {
        $response = $this->post('/webhooks/coinremitter', [
            'ping' => 'site',
        ]);

        $response->assertOk();
        $this->assertSame('OK', $response->getContent());
    }

    public function test_webhook_acknowledges_missing_invoice_id_for_coinremitter_validator(): void
    {
        $response = $this->postJson('/webhooks/coinremitter', [
            'unexpected' => 'payload',
        ]);

        $response->assertOk();
        $this->assertSame('OK', $response->getContent());
    }

    public function test_webhook_acknowledges_unknown_invoice_id(): void
    {
        $walletTransactions = Mockery::mock(WalletTransactionService::class);
        $walletTransactions->shouldReceive('findByPaymentId')
            ->once()
            ->with('UNKNOWN')
            ->andReturn(ServiceReturn::error('Giao dịch không tồn tại'));

        $this->app->instance(WalletTransactionService::class, $walletTransactions);

        $response = $this->postJson('/webhooks/coinremitter', [
            'invoice_id' => 'UNKNOWN',
        ]);

        $response->assertOk();
        $this->assertSame('OK', $response->getContent());
    }

    public function test_webhook_verifies_invoice_and_approves_pending_deposit(): void
    {
        $transaction = (object) [
            'id' => 123,
            'network' => 'TRC20',
            'status' => WalletTransactionStatus::PENDING->value,
        ];

        $walletTransactions = Mockery::mock(WalletTransactionService::class);
        $walletTransactions->shouldReceive('findByPaymentId')
            ->once()
            ->with('INV123')
            ->andReturn(ServiceReturn::success($transaction));
        $walletTransactions->shouldReceive('approveDeposit')
            ->once()
            ->with(123, 'TX123')
            ->andReturn(ServiceReturn::success());

        $coinRemitter = Mockery::mock(CoinRemitterService::class);
        $coinRemitter->shouldReceive('getInvoice')
            ->once()
            ->with('TRC20', 'INV123')
            ->andReturn(ServiceReturn::success([
                'invoice_id' => 'INV123',
                'status' => CoinRemitterService::STATUS_PAID,
                'txid' => 'TX123',
            ]));
        $coinRemitter->shouldReceive('status')
            ->once()
            ->andReturn(CoinRemitterService::STATUS_PAID);
        $coinRemitter->shouldReceive('isPaidStatus')
            ->once()
            ->with(CoinRemitterService::STATUS_PAID)
            ->andReturnTrue();
        $coinRemitter->shouldReceive('txHash')
            ->once()
            ->andReturn('TX123');

        $this->app->instance(WalletTransactionService::class, $walletTransactions);
        $this->app->instance(CoinRemitterService::class, $coinRemitter);

        $response = $this->postJson('/webhooks/coinremitter', [
            'invoice_id' => 'INV123',
        ]);

        $response->assertOk();
        $this->assertSame('OK', $response->getContent());
    }

    public function test_webhook_matches_wallet_callback_to_paid_pending_invoice(): void
    {
        config(['services.coinremitter.networks.TRC20.coin' => 'TCN']);

        $transaction = (object) [
            'id' => 123,
            'network' => 'TRC20',
            'status' => WalletTransactionStatus::PENDING->value,
            'payment_id' => '0UJjQ2s',
        ];

        $walletTransactions = Mockery::mock(WalletTransactionService::class);
        $walletTransactions->shouldReceive('findByPaymentId')
            ->once()
            ->with('6a101e28b64958fa09c665cf')
            ->andReturn(ServiceReturn::error('Giao dịch không tồn tại'));
        $walletTransactions->shouldReceive('findPendingCoinRemitterDeposits')
            ->once()
            ->with(['TRC20'])
            ->andReturn(ServiceReturn::success(collect([$transaction])));
        $walletTransactions->shouldReceive('approveDeposit')
            ->once()
            ->with(123, 'TX123')
            ->andReturn(ServiceReturn::success());

        $coinRemitter = Mockery::mock(CoinRemitterService::class);
        $coinRemitter->shouldReceive('getInvoice')
            ->once()
            ->with('TRC20', '0UJjQ2s')
            ->andReturn(ServiceReturn::success([
                'invoice_id' => '0UJjQ2s',
                'status' => 'Paid',
                'txid' => 'TX123',
            ]));
        $coinRemitter->shouldReceive('status')
            ->twice()
            ->andReturn(CoinRemitterService::STATUS_PAID);
        $coinRemitter->shouldReceive('payAddress')
            ->once()
            ->andReturnNull();
        $coinRemitter->shouldReceive('isPaidStatus')
            ->twice()
            ->with(CoinRemitterService::STATUS_PAID)
            ->andReturnTrue();
        $coinRemitter->shouldReceive('txHash')
            ->once()
            ->andReturn('TX123');

        $this->app->instance(WalletTransactionService::class, $walletTransactions);
        $this->app->instance(CoinRemitterService::class, $coinRemitter);

        $response = $this->postJson('/webhooks/coinremitter', [
            'id' => '6a101e28b64958fa09c665cf',
            'txid' => 'TX123',
            'coin_symbol' => 'TCN',
            'amount' => '2',
            'confirmations' => '11',
        ]);

        $response->assertOk();
        $this->assertSame('OK', $response->getContent());
    }

    public function test_webhook_matches_wallet_callback_to_overpaid_pending_invoice(): void
    {
        config(['services.coinremitter.networks.TRC20.coin' => 'TCN']);

        $transaction = (object) [
            'id' => 123,
            'network' => 'TRC20',
            'status' => WalletTransactionStatus::PENDING->value,
            'payment_id' => '0UJjQ2s',
        ];

        $walletTransactions = Mockery::mock(WalletTransactionService::class);
        $walletTransactions->shouldReceive('findByPaymentId')
            ->once()
            ->with('6a101e28b64958fa09c665cf')
            ->andReturn(ServiceReturn::error('Giao dịch không tồn tại'));
        $walletTransactions->shouldReceive('findPendingCoinRemitterDeposits')
            ->once()
            ->with(['TRC20'])
            ->andReturn(ServiceReturn::success(collect([$transaction])));
        $walletTransactions->shouldReceive('approveDeposit')
            ->once()
            ->with(123, 'TX123')
            ->andReturn(ServiceReturn::success());

        $coinRemitter = Mockery::mock(CoinRemitterService::class);
        $coinRemitter->shouldReceive('getInvoice')
            ->once()
            ->with('TRC20', '0UJjQ2s')
            ->andReturn(ServiceReturn::success([
                'invoice_id' => '0UJjQ2s',
                'status' => 'Over Paid',
                'txid' => 'TX123',
            ]));
        $coinRemitter->shouldReceive('status')
            ->twice()
            ->andReturn(CoinRemitterService::STATUS_OVER_PAID);
        $coinRemitter->shouldReceive('payAddress')
            ->once()
            ->andReturnNull();
        $coinRemitter->shouldReceive('isPaidStatus')
            ->twice()
            ->with(CoinRemitterService::STATUS_OVER_PAID)
            ->andReturnTrue();
        $coinRemitter->shouldReceive('txHash')
            ->once()
            ->andReturn('TX123');

        $this->app->instance(WalletTransactionService::class, $walletTransactions);
        $this->app->instance(CoinRemitterService::class, $coinRemitter);

        $response = $this->postJson('/webhooks/coinremitter', [
            'id' => '6a101e28b64958fa09c665cf',
            'txid' => 'TX123',
            'coin_symbol' => 'TCN',
            'amount' => '6',
            'confirmations' => '11',
        ]);

        $response->assertOk();
        $this->assertSame('OK', $response->getContent());
    }

    public function test_webhook_marks_wallet_callback_underpaid_without_approving_deposit(): void
    {
        config(['services.coinremitter.networks.TRC20.coin' => 'TCN']);

        $transaction = (object) [
            'id' => 123,
            'network' => 'TRC20',
            'status' => WalletTransactionStatus::PENDING->value,
            'payment_id' => 'GZk9WRv',
        ];

        $walletTransactions = Mockery::mock(WalletTransactionService::class);
        $walletTransactions->shouldReceive('findByPaymentId')
            ->once()
            ->with('6a10238edd55f32e3ffa917d')
            ->andReturn(ServiceReturn::error('Giao dịch không tồn tại'));
        $walletTransactions->shouldReceive('findPendingCoinRemitterDeposits')
            ->once()
            ->with(['TRC20'])
            ->andReturn(ServiceReturn::success(collect([$transaction])));
        $walletTransactions->shouldReceive('updateTransactionStatus')
            ->once()
            ->with(
                123,
                WalletTransactionStatus::PENDING->value,
                WalletTransactionDescription::DEPOSIT_UNDERPAID->value
            )
            ->andReturn(ServiceReturn::success());
        $walletTransactions->shouldNotReceive('approveDeposit');

        $coinRemitter = Mockery::mock(CoinRemitterService::class);
        $coinRemitter->shouldReceive('getInvoice')
            ->once()
            ->with('TRC20', 'GZk9WRv')
            ->andReturn(ServiceReturn::success([
                'invoice_id' => 'GZk9WRv',
                'status' => 'Under Paid',
                'address' => 'naHGmdDRGnjdB3VgJjdHaaat2MSwHrnbaW',
            ]));
        $coinRemitter->shouldReceive('status')
            ->twice()
            ->andReturn(CoinRemitterService::STATUS_UNDER_PAID);
        $coinRemitter->shouldReceive('payAddress')
            ->once()
            ->andReturn('naHGmdDRGnjdB3VgJjdHaaat2MSwHrnbaW');
        $coinRemitter->shouldReceive('isPaidStatus')
            ->twice()
            ->with(CoinRemitterService::STATUS_UNDER_PAID)
            ->andReturnFalse();
        $coinRemitter->shouldReceive('isFailedStatus')
            ->once()
            ->with(CoinRemitterService::STATUS_UNDER_PAID)
            ->andReturnFalse();

        $this->app->instance(WalletTransactionService::class, $walletTransactions);
        $this->app->instance(CoinRemitterService::class, $coinRemitter);

        $response = $this->postJson('/webhooks/coinremitter', [
            'id' => '6a10238edd55f32e3ffa917d',
            'txid' => 'TX123',
            'coin_symbol' => 'TCN',
            'address' => 'naHGmdDRGnjdB3VgJjdHaaat2MSwHrnbaW',
            'amount' => '1',
            'confirmations' => '11',
        ]);

        $response->assertOk();
        $this->assertSame('OK', $response->getContent());
    }
}
