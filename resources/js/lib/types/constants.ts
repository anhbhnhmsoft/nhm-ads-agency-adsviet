export enum _UserRole {
    ADMIN = 1,
    MANAGER = 2,
    EMPLOYEE = 3,
    AGENCY = 4,
    CUSTOMER = 5,
}

export const userRolesLabel = {
    [_UserRole.ADMIN]: 'enum.user_role.admin',
    [_UserRole.MANAGER]: 'enum.user_role.manager',
    [_UserRole.EMPLOYEE]: 'enum.user_role.employee',
    [_UserRole.AGENCY]: 'enum.user_role.agency',
    [_UserRole.CUSTOMER]: 'enum.user_role.customer',
}

export enum _PlatformType {
    GOOGLE = 1,
    META = 2,
}

export const platformTypeLabel = {
    [_PlatformType.GOOGLE]: 'enum.platform_type.google',
    [_PlatformType.META]: 'enum.platform_type.meta',
}

export enum _WalletStatus {
    ACTIVE = 1,
    LOCKED = 2,
}

export const walletStatusLabel: Record<_WalletStatus, string> = {
    [_WalletStatus.ACTIVE]: 'wallet.active',
    [_WalletStatus.LOCKED]: 'wallet.locked',
}

export enum _ConfigName {
    BEP20_WALLET_ADDRESS = 'BEP20_WALLET_ADDRESS',
    TRC20_WALLET_ADDRESS = 'TRC20_WALLET_ADDRESS',
}

export const configNameLabel: Record<_ConfigName, string> = {
    [_ConfigName.BEP20_WALLET_ADDRESS]: 'config.bep20_wallet_address',
    [_ConfigName.TRC20_WALLET_ADDRESS]: 'config.trc20_wallet_address',
}

export enum _ConfigType {
    IMAGE = 1,
    STRING = 2,
}

export const configTypeLabel: Record<_ConfigType, string> = {
    [_ConfigType.IMAGE]: 'config.type.image',
    [_ConfigType.STRING]: 'config.type.string',
}

export enum _MetaAdsAccountStatus {
    ACTIVE = 1,
    DISABLED = 2,
    UNSETTLED = 3,
    PENDING_RISK_REVIEW = 7,
    PENDING_SETTLEMENT = 8,
    IN_GRACE_PERIOD = 9,
    PENDING_CLOSURE = 100,
    CLOSED = 101,
    ANY_ACTIVE = 201,
    ANY_CLOSED = 202,
}

export const metaAdsAccountStatusLabel: Record<_MetaAdsAccountStatus, string> = {
    [_MetaAdsAccountStatus.ACTIVE]: 'meta.account_status.active',
    [_MetaAdsAccountStatus.DISABLED]: 'meta.account_status.disabled',
    [_MetaAdsAccountStatus.UNSETTLED]: 'meta.account_status.unsettled',
    [_MetaAdsAccountStatus.PENDING_RISK_REVIEW]: 'meta.account_status.pending_risk_review',
    [_MetaAdsAccountStatus.PENDING_SETTLEMENT]: 'meta.account_status.pending_settlement',
    [_MetaAdsAccountStatus.IN_GRACE_PERIOD]: 'meta.account_status.in_grace_period',
    [_MetaAdsAccountStatus.PENDING_CLOSURE]: 'meta.account_status.pending_closure',
    [_MetaAdsAccountStatus.CLOSED]: 'meta.account_status.closed',
    [_MetaAdsAccountStatus.ANY_ACTIVE]: 'meta.account_status.active',
    [_MetaAdsAccountStatus.ANY_CLOSED]: 'meta.account_status.closed',
}

export enum _GoogleCustomerStatus {
    UNSPECIFIED = 0,
    UNKNOWN = 1,
    ENABLED = 2,
    CANCELED = 3,
    SUSPENDED = 4,
    CLOSED = 5,
}

export const googleCustomerStatusLabel: Record<_GoogleCustomerStatus, string> = {
    [_GoogleCustomerStatus.UNSPECIFIED]: 'google_ads.account_status.unknown',
    [_GoogleCustomerStatus.UNKNOWN]: 'google_ads.account_status.unknown',
    [_GoogleCustomerStatus.ENABLED]: 'google_ads.account_status.enabled',
    [_GoogleCustomerStatus.CANCELED]: 'google_ads.account_status.canceled',
    [_GoogleCustomerStatus.SUSPENDED]: 'google_ads.account_status.suspended',
    [_GoogleCustomerStatus.CLOSED]: 'google_ads.account_status.closed',
}
