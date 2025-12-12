<?php

namespace App\Common\Constants\Ticket;

enum TicketMetadataType: string
{
    case TRANSFER = 'transfer';
    case REFUND = 'refund';
    case APPEAL = 'appeal';
    case SHARE = 'share';
    case TRANSFER_BUDGET = 'transfer_budget';
    case ACCOUNT_LIQUIDATION = 'account_liquidation';
    case ACCOUNT_APPEAL = 'account_appeal';
    case ACCOUNT_CLOSE = 'account_close';
    case SHARE_BM = 'share_bm';
    case WALLET_WITHDRAW_APP = 'wallet_withdraw_app';
    case WALLET_DEPOSIT_APP = 'wallet_deposit_app';

    public function label(): string
    {
        return match ($this) {
            TicketMetadataType::TRANSFER,
            TicketMetadataType::TRANSFER_BUDGET => __('ticket.type.transfer_budget'),
            
            TicketMetadataType::REFUND,
            TicketMetadataType::ACCOUNT_LIQUIDATION,
            TicketMetadataType::ACCOUNT_CLOSE => __('ticket.type.account_liquidation'),
            
            TicketMetadataType::APPEAL,
            TicketMetadataType::ACCOUNT_APPEAL => __('ticket.type.account_appeal'),
            
            TicketMetadataType::SHARE,
            TicketMetadataType::SHARE_BM => __('ticket.type.share_bm'),
            
            TicketMetadataType::WALLET_WITHDRAW_APP => __('ticket.type.wallet_withdraw_app'),
            TicketMetadataType::WALLET_DEPOSIT_APP => __('ticket.type.wallet_deposit_app'),
        };
    }

    public static function getDatabaseValue(string $frontendValue): string
    {
        return match ($frontendValue) {
            'transfer_budget' => self::TRANSFER->value,
            'account_liquidation', 'account_close' => self::REFUND->value,
            'account_appeal' => self::APPEAL->value,
            'share_bm' => self::SHARE->value,
            default => $frontendValue,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function databaseValues(): array
    {
        return [
            self::TRANSFER->value,
            self::REFUND->value,
            self::APPEAL->value,
            self::SHARE->value,
            self::WALLET_WITHDRAW_APP->value,
            self::WALLET_DEPOSIT_APP->value,
        ];
    }
}

