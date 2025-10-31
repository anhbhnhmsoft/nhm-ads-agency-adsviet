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
