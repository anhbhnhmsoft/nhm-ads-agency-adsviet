import { _PlatformType } from '@/lib/types/constants';
import { useTranslation } from 'react-i18next';
import type { AccountConfig, ServiceOrderConfigAccount } from '../types/type';

type AccountInfoCellProps = {
    config: ServiceOrderConfigAccount | null;
    platform?: number | null;
    packageBillingSource?: string | null;
    metaTimezones?: Array<{ value: string; label: string }>;
    googleTimezones?: Array<{ value: string; label: string }>;
};

export const AccountInfoCell = ({
    config,
    platform,
    packageBillingSource,
    metaTimezones = [],
    googleTimezones = [],
}: AccountInfoCellProps) => {
    const { t } = useTranslation();

    if (!config) {
        return <span className="text-xs text-muted-foreground">-</span>;
    }

    const isMeta = platform === _PlatformType.META;

    const resolveTimezoneLabel = (value: string): string => {
        if (!value) return '';
        const options = isMeta ? metaTimezones : googleTimezones;
        const found = options.find((opt) => opt.value === value);
        return found ? found.label : value;
    };

    const accounts = config.accounts;
    const resolvedAccountIds = Array.isArray(config.resolved_account_ids)
        ? config.resolved_account_ids.filter((id: string) => id?.trim())
        : [];
    const configAccountIds = Array.isArray(config.account_ids)
        ? config.account_ids.filter((id: string) => id?.trim())
        : [];
    const singleAccountId = config.account_id ? [config.account_id as string] : [];
    const allAssignedAccounts = Array.from(new Set([...resolvedAccountIds, ...configAccountIds, ...singleAccountId]));

    if (Array.isArray(accounts) && accounts.length > 0) {
        const billingSource =
            packageBillingSource ||
            config.billing_source ||
            (config as any).payment_source;
        return (
            <div className="space-y-2 text-xs">
                {allAssignedAccounts.length > 0 && (
                    <div className="rounded border border-emerald-200 bg-emerald-50/80 p-2 font-medium text-emerald-950">
                        <span className="font-semibold text-emerald-900">
                            {t('service_orders.table.assigned_account', {
                                defaultValue: 'Tài khoản đã gán',
                            })}:
                        </span>{' '}
                        <span className="font-mono font-bold text-emerald-800">
                            {allAssignedAccounts.join(', ')}
                        </span>
                    </div>
                )}
                {billingSource && (
                    <div className="rounded border border-indigo-100 bg-indigo-50/50 p-2 font-medium text-indigo-950">
                        <span className="font-semibold text-indigo-900">
                            {t('service_orders.form.billing_source_label', {
                                defaultValue: 'Nguồn thanh toán',
                            })}:
                        </span>{' '}
                        {t(`service_orders.form.billing_sources.${billingSource}`, {
                            defaultValue: billingSource === 'customer_card'
                                ? 'Thẻ của khách'
                                : billingSource === 'adviet_card'
                                ? 'Thẻ Adviet'
                                : billingSource === 'supplier_credit_line'
                                ? 'Hạn mức nhà cung cấp'
                                : billingSource,
                        })}
                    </div>
                )}
                {accounts.map((account: AccountConfig, idx: number) => {
                    const email = account.meta_email || '';
                    const name = account.display_name || '';
                    const bmIds = Array.isArray(account.bm_ids)
                        ? account.bm_ids.filter((id: string) => id?.trim())
                        : [];
                    const fanpages = Array.isArray(account.fanpages)
                        ? account.fanpages.filter((fp: string) => fp?.trim())
                        : [];
                    const websites = Array.isArray(account.websites)
                        ? account.websites.filter((ws: string) => ws?.trim())
                        : [];
                    const timezone = account.timezone_bm || '';
                    const assetAccess = account.asset_access || '';
                    const assignedAcc = account.account_id || '';

                    if (
                        !email &&
                        !name &&
                        bmIds.length === 0 &&
                        fanpages.length === 0 &&
                        websites.length === 0 &&
                        !timezone &&
                        !assignedAcc
                    ) {
                        return null;
                    }

                    return (
                        <div
                            key={idx}
                            className="rounded border bg-gray-50 p-2"
                        >
                            <div className="mb-1 text-xs font-medium">
                                {t('service_purchase.account_number', {
                                    number: idx + 1,
                                    defaultValue: `Tài khoản ${idx + 1}`,
                                })}
                            </div>
                            <div className="space-y-1">
                                {assignedAcc && (
                                    <div>
                                        <span className="font-medium text-emerald-800">
                                            {t('service_orders.table.assigned_account', {
                                                defaultValue: 'Tài khoản đã gán',
                                            })}:
                                        </span>{' '}
                                        <span className="font-mono font-bold text-emerald-700">
                                            {assignedAcc}
                                        </span>
                                    </div>
                                )}
                                {email && (
                                    <div>
                                        <span className="font-medium">
                                            Email:
                                        </span>{' '}
                                        {email}
                                    </div>
                                )}
                                {name && (
                                    <div>
                                        <span className="font-medium">
                                            Name:
                                        </span>{' '}
                                        {name}
                                    </div>
                                )}
                                {bmIds.length > 0 && (
                                    <div>
                                        <span className="font-medium">
                                            {isMeta ? 'BM ID' : 'MCC ID'}:
                                        </span>{' '}
                                        {bmIds.map(
                                            (bmId: string, bmIdx: number) => (
                                                <span key={bmIdx}>
                                                    {bmId}
                                                    {bmIdx < bmIds.length - 1
                                                        ? ', '
                                                        : ''}
                                                </span>
                                            ),
                                        )}
                                    </div>
                                )}
                                {timezone && (
                                    <div>
                                        <span className="font-medium">
                                            {isMeta
                                                ? t(
                                                      'service_purchase.timezone_bm_label',
                                                      {
                                                          defaultValue:
                                                              'Múi giờ BM',
                                                      },
                                                  )
                                                : t(
                                                      'service_purchase.timezone_mcc_label',
                                                      {
                                                          defaultValue:
                                                              'Múi giờ MCC',
                                                      },
                                                  )}
                                            :
                                        </span>{' '}
                                        {resolveTimezoneLabel(timezone)}
                                    </div>
                                )}
                                {fanpages.length > 0 && (
                                    <div>
                                        <span className="font-medium">
                                            {t(
                                                'service_orders.table.info_fanpage',
                                            )}
                                            :
                                        </span>{' '}
                                        {fanpages.map(
                                            (
                                                fanpage: string,
                                                fpIdx: number,
                                            ) => (
                                                <span key={fpIdx}>
                                                    {fanpage}
                                                    {fpIdx < fanpages.length - 1
                                                        ? ', '
                                                        : ''}
                                                </span>
                                            ),
                                        )}
                                    </div>
                                )}
                                {websites.length > 0 && (
                                    <div>
                                        <span className="font-medium">
                                            {t(
                                                'service_orders.table.info_website',
                                            )}
                                            :
                                        </span>{' '}
                                        {websites.map(
                                            (
                                                website: string,
                                                wsIdx: number,
                                            ) => (
                                                <span key={wsIdx}>
                                                    {website}
                                                    {wsIdx < websites.length - 1
                                                        ? ', '
                                                        : ''}
                                                </span>
                                            ),
                                        )}
                                    </div>
                                )}
                                {assetAccess && (
                                    <div>
                                        <span className="font-medium">
                                            {t(
                                                'service_purchase.asset_access_label',
                                                {
                                                    defaultValue:
                                                        'Quyền truy cập',
                                                },
                                            )}
                                            :
                                        </span>{' '}
                                        {assetAccess === 'full_asset'
                                            ? t(
                                                  'service_purchase.asset_access_full',
                                                  {
                                                      defaultValue:
                                                          'Full access',
                                                  },
                                              )
                                            : t(
                                                  'service_purchase.asset_access_basic',
                                                  {
                                                      defaultValue:
                                                          'Basic access',
                                                  },
                                              )}
                                    </div>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
        );
    }

    const email = (config.meta_email as string) || '';
    const name = (config.display_name as string) || '';
    const bm = (config.bm_id as string) || '';
    const fanpage = (config.info_fanpage as string) || '';
    const website = (config.info_website as string) || '';
    const timezone = (config.timezone_bm as string) || '';
    const billingSource =
        packageBillingSource ||
        config.billing_source ||
        (config as any).payment_source ||
        '';

    if (!email && !name && !bm && !fanpage && !website && !timezone && !billingSource && allAssignedAccounts.length === 0) {
        return <span className="text-xs text-muted-foreground">-</span>;
    }

    return (
        <div className="space-y-1 text-xs">
            {allAssignedAccounts.length > 0 && (
                <div className="rounded border border-emerald-200 bg-emerald-50/80 p-1.5 font-medium text-emerald-950 mb-1">
                    <span className="font-semibold text-emerald-900">
                        {t('service_orders.table.assigned_account', {
                            defaultValue: 'Tài khoản đã gán',
                        })}:
                    </span>{' '}
                    <span className="font-mono font-bold text-emerald-800">
                        {allAssignedAccounts.join(', ')}
                    </span>
                </div>
            )}
            {billingSource && (
                <div>
                    <span className="font-medium">
                        {t('service_orders.form.billing_source_label', {
                            defaultValue: 'Nguồn thanh toán',
                        })}:
                    </span>{' '}
                    <span className="font-semibold text-indigo-700">
                        {t(`service_orders.form.billing_sources.${billingSource}`, {
                            defaultValue: billingSource === 'customer_card'
                                ? 'Thẻ của khách'
                                : billingSource === 'adviet_card'
                                ? 'Thẻ Adviet'
                                : billingSource === 'supplier_credit_line'
                                ? 'Hạn mức nhà cung cấp'
                                : billingSource,
                        })}
                    </span>
                </div>
            )}
            {email && (
                <div>
                    <span className="font-medium">Email:</span> {email}
                </div>
            )}
            {name && (
                <div>
                    <span className="font-medium">Name:</span> {name}
                </div>
            )}
            {bm && (
                <div>
                    <span className="font-medium">
                        {isMeta ? 'BM ID' : 'MCC ID'}:
                    </span>{' '}
                    {bm}
                </div>
            )}
            {timezone && (
                <div>
                    <span className="font-medium">
                        {isMeta
                            ? t('service_purchase.timezone_bm_label', {
                                  defaultValue: 'Múi giờ BM',
                              })
                            : t('service_purchase.timezone_mcc_label', {
                                  defaultValue: 'Múi giờ MCC',
                              })}
                        :
                    </span>{' '}
                    {resolveTimezoneLabel(timezone)}
                </div>
            )}
            {fanpage && (
                <div>
                    <span className="font-medium">
                        {t('service_orders.table.info_fanpage')}:
                    </span>{' '}
                    {fanpage}
                </div>
            )}
            {website && (
                <div>
                    <span className="font-medium">
                        {t('service_orders.table.info_website')}:
                    </span>{' '}
                    {website}
                </div>
            )}
        </div>
    );
};
