<?php

return [
    'error' => [
        'service_not_found' => '服务不存在或您没有权限。',
        'service_user_platform_not_google' => '此服务不是 Google Ads 服务。',
        'missing_customer_ids' => '找不到此服务的 Google Ads 子帐户。',
        'no_manager_id_found' => '服务配置中缺少 Business Manager ID。',
        'sync_failed' => '无法同步 Google Ads 数据。请稍后重试。',
        'campaign_not_found' => '未找到 Google Ads 广告系列。',
        'account_not_found' => '未找到 Google Ads 帐户。',
        'failed_to_fetch_campaign_detail' => '无法获取 Google Ads 广告系列详细信息。',
        'oauth_token_expired' => 'Google Ads OAuth 令牌已过期或被撤销。请更新凭据。',
        'date_preset_invalid' => '日期范围无效。',
        'invalid_campaign_status' => '广告系列状态无效。',
        'invalid_budget_amount' => '预算金额必须大于 0。',
        'failed_to_update_campaign_status' => '无法更新 Google Ads 上的广告系列状态。',
        'failed_to_update_campaign_status_suspended' => '无法更新广告系列状态，因为 Google Ads 帐户已暂停。请先解决 Google Ads 中的暂停问题。',
        'failed_to_update_campaign_budget' => '无法更新 Google Ads 上的广告系列预算。',
        'account_budget_not_found' => '未找到有效的 Google Ads 帐户预算。',
        'failed_to_update_account_spending_limit' => '未能提高 Google Ads 帐户支出限额。请检查计费/MCC权限或手动处理。',
        'cannot_resume_spending_exceeded' => '无法恢复活动。终身支出 (:spendingUSD) 超过当前余额 (:balanceUSD) 加上安全阈值 (:thresholdUSD)。请在恢复之前给您的帐户充值。',
    ],
    'account_status' => [
        'enabled' => '积极的',
        'canceled' => '取消',
        'suspended' => '暂停',
        'closed' => '关闭',
        'unknown' => '未知',
    ],
    'campaign_status' => [
        'enabled' => '跑步',
        'paused' => '已暂停',
        'removed' => '已删除',
        'unknown' => '未知',
    ],
    'account_status_messages' => [
        'canceled' => '该帐户已被取消。请检查账单信息或联系支持人员。',
        'suspended' => 'Google 已暂停您的帐户。请检查警告并修复它们。',
        'closed' => '该帐户已关闭，无法再投放广告。',
    ],
    'telegram' => [
        'low_balance' => "⚠️ Google Ads 余额不足提醒\n\n帐户“:accountName”只剩下:balance:currency（阈值:threshold:currency）。\n请为您的 Google Ads 帐户充值以避免中断。",
    ],
];
