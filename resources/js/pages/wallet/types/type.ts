export interface WalletTransaction {
    id: string;
    amount: number;
    type: number;
    status: number;
    description?: string | null;
    network?: string | null;
    txHash?: string | null;
    payment_id?: string | null;
    withdraw_info?: {
        bank_name?: string;
        account_holder?: string;
        account_number?: string;
        crypto_address?: string;
        network?: 'TRC20' | 'BEP20';
        withdraw_type?: 'bank' | 'usdt';
    } | null;
    createdAt?: string | null;
    user?: {
        id: string;
        name: string;
    } | null;
}

export interface WalletData {
    id: number;
    user_id: number;
    balance: string;
    status: number;
    has_password?: boolean;
    transactions: WalletTransaction[];
}

export type Network = {
    key: 'BEP20' | 'TRC20';
    config_key: string;
    address: string;
};

export interface PendingDeposit {
    id: string;
    amount: number;
    network: 'BEP20' | 'TRC20';
    deposit_address?: string;
    pay_address?: string;
    payment_id?: string;
    expires_at: string;
    expires_in?: number;
}

export interface WalletIndexProps {
    wallet: WalletData | null;
    walletError?: string;
    networks?: Network[];
    pending_deposit?: PendingDeposit | null;
}

export type WalletTab = 'topup' | 'withdraw' | 'password';

export type TopUpFormData = {
  network?: 'BEP20' | 'TRC20';
  amount: string;
};

export type WithdrawFormData = {
  amount: string;
  password: string;
  bank_name?: string;
  account_holder?: string;
  account_number?: string;
  crypto_address?: string;
  network?: 'TRC20' | 'BEP20';
  withdraw_type?: 'bank' | 'usdt';
};

export type PasswordFormData = {
  current_password: string;
  new_password: string;
  confirm_password: string;
};

