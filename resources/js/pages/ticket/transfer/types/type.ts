export type TransferAccount = {
    id: string;
    account_id: string;
    account_name: string;
    platform: number;
};

export type TransferFormProps = {
    accounts: TransferAccount[];
};

