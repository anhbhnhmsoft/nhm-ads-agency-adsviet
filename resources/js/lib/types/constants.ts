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
