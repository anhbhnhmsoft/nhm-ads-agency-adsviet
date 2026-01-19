<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServicePackageController;
use App\Http\Controllers\ServiceOrderController;
use App\Http\Controllers\ServiceManagementController;
// use App\Http\Controllers\NowPaymentsWebhookController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PlatformSettingController;
use App\Http\Controllers\ServicePurchaseController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WalletTransactionController;
use App\Http\Controllers\API\GoogleAdsController;
use App\Http\Controllers\API\MetaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SpendReportController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\BusinessManagerController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProfitController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

// Route Webhook NowPayments tạm thời tắt vì chuyển sang duyệt nạp thủ công
// Route::post('/webhooks/nowpayments', [NowPaymentsWebhookController::class, 'handle'])->name('nowpayments_webhook');
Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware(['guest:web'])->group(function () {
    Route::get('/login', [AuthController::class, 'loginScreen'])->name('login');
    Route::get('/register', [AuthController::class, 'registerScreen'])->name('register');

    // xác thực
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'handleLoginUsername'])->name('auth_login');
        Route::post('/start', [AuthController::class, 'handleLoginTelegram'])->name('auth_telegram');
        Route::post('/register-email/send-otp', [AuthController::class, 'sendRegisterEmailOtp'])->name('auth_register_send_email_otp');
        Route::post('/register-email/verify-otp', [AuthController::class, 'verifyRegisterEmailOtp'])->name('auth_register_verify_email_otp');
        Route::get('/register-new-user', [AuthController::class, 'registerNewUserScreen'])
            ->name('auth_register_new_user_screen');
        Route::post('/register-new-user', [AuthController::class, 'handleRegisterNewUser'])
            ->name('auth_register_new_user');
    });
});


Route::middleware(['auth:web', EnsureUserIsActive::class])->group(function () {
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile_update');
    Route::post('/profile/resend-email', [ProfileController::class, 'resendEmail'])->name('profile_resend_email');
    Route::post('/profile/verify-email-otp', [ProfileController::class, 'verifyEmailOtp'])->name('profile_verify_email_otp');
    Route::post('/profile/connect-telegram', [ProfileController::class, 'connectTelegram'])->name('profile_connect_telegram');
    Route::put('/profile/change-password', [ProfileController::class, 'changePassword'])->name('profile_change_password');

    Route::get('/contact', [ContactController::class, 'index'])->name('contact_index');

    Route::prefix('user')->group(function () {
        Route::get('/list-employee', [UserController::class, 'listEmployee'])->name('user_list_employee');
        Route::get('/create-employee', [UserController::class, 'createEmployeeScreen'])->name('user_create_employee');
        Route::get('/employee/{id}/edit', [UserController::class, 'editEmployeeScreen'])->name('user_employee_edit');
        Route::post('/employee', [UserController::class, 'store'])->name('user_employee_store');
        Route::put('/employee/{id}', [UserController::class, 'update'])->name('user_employee_update');
        Route::delete('/employee/{id}', [UserController::class, 'destroy'])->name('user_employee_destroy');
        Route::post('/employee/{id}/toggle-disable', [UserController::class, 'toggleDisable'])->name('user_employee_toggle_disable');

        Route::get('/manager/{managerId}/employees', [UserController::class, 'getEmployeesByManager'])->name('user_get_employees_by_manager');
        Route::post('/employee/assign', [UserController::class, 'assignEmployee'])->name('user_assign_employee');
        Route::post('/employee/unassign', [UserController::class, 'unassignEmployee'])->name('user_unassign_employee');
    });

    Route::prefix('/customer')->group(function (){
        Route::get('/list', [UserController::class, 'listCustomer'])->name('user_list');
        Route::get('/{id}/edit', [UserController::class, 'editUserScreen'])->name('user_edit');
        Route::put('/{id}', [UserController::class, 'updateUser'])->name('user_update');
        Route::post('/{id}/toggle-disable', [UserController::class, 'userToggleDisable'])->name('user_toggle_disable');
        Route::delete('/{id}', [UserController::class, 'destroyUser'])->name('user_destroy');
    });

    Route::prefix('/platform-settings')->group(function (){
        Route::get('/', [PlatformSettingController::class, 'index'])->name('platform_settings_index');
        Route::get('/platform/{platform}', [PlatformSettingController::class, 'getByPlatform'])->name('platform_settings_get_by_platform');
        Route::post('/', [PlatformSettingController::class, 'store'])->name('platform_settings_store');
        Route::put('/{id}', [PlatformSettingController::class, 'update'])->name('platform_settings_update');
        Route::post('/{id}/toggle', [PlatformSettingController::class, 'toggle'])->name('platform_settings_toggle');
    });

    Route::prefix('/config')->group(function (){
        Route::get('/', [ConfigController::class, 'index'])->name('config_index');
        Route::put('/', [ConfigController::class, 'update'])->name('config_update');
    });

    Route::prefix('/wallets')->group(function(){
        Route::get('/', [WalletController::class, 'index'])->name('wallet_index');
        Route::get('/me', [WalletController::class, 'me'])->name('wallet_me_json');
        Route::post('/campaign-budget-update', [WalletController::class, 'campaignBudgetUpdate'])->name('wallet_campaign_budget_update');
        Route::post('/campaign-pause', [WalletController::class, 'campaignPause'])->name('wallet_campaign_pause');
        Route::post('/campaign-end', [WalletController::class, 'campaignEnd'])->name('wallet_campaign_end');
        Route::get('/min-amount/{network}', [WalletController::class, 'getMinimalAmount'])->name('wallet_min_amount');
        Route::post('/top-up', [WalletController::class, 'myTopUp'])->name('wallet_my_top_up');
        Route::post('/withdraw', [WalletController::class, 'myWithdraw'])->name('wallet_my_withdraw');
        Route::post('/change-password', [WalletController::class, 'changePassword'])->name('wallet_change_password');
        Route::post('/{userId}/create', [WalletController::class, 'create'])->name('wallet_create');
        Route::post('/{userId}/top-up', [WalletController::class, 'topUp'])->name('wallet_top_up');
        Route::post('/{userId}/withdraw', [WalletController::class, 'withdraw'])->name('wallet_withdraw');
        Route::post('/{userId}/lock', [WalletController::class, 'lock'])->name('wallet_lock');
        Route::post('/{userId}/unlock', [WalletController::class, 'unlock'])->name('wallet_unlock');
        Route::post('/{userId}/reset-password', [WalletController::class, 'resetPassword'])->name('wallet_reset_password');
        Route::post('/deposit/{id}/cancel', [WalletController::class, 'cancelDeposit'])->name('wallet_deposit_cancel');
    });

    Route::prefix('transactions')->group(function () {
        Route::get('/', [WalletTransactionController::class, 'index'])->name('transactions_index');
        Route::post('/{id}/approve', [WalletTransactionController::class, 'approve'])->name('transactions_approve');
        Route::post('/{id}/cancel', [WalletTransactionController::class, 'cancel'])->name('transactions_cancel');
    });

    Route::prefix('/service-packages')->group(function (){
        Route::get('/', [ServicePackageController::class, 'index'])->name('service_packages_index');
        Route::get('/create', [ServicePackageController::class, 'createView'])->name('service_packages_create_view');
        Route::post('/create', [ServicePackageController::class, 'create'])->name('service_packages_create');
        Route::get('/{id}/edit', [ServicePackageController::class, 'editView'])->name('service_packages_edit_view');
        Route::put('/{id}', [ServicePackageController::class, 'update'])->name('service_packages_update');
        Route::delete('/{id}', [ServicePackageController::class, 'destroy'])->name('service_packages_destroy');
        Route::post('/{id}/toggle-disable', [ServicePackageController::class, 'toggleDisable'])->name('service_packages_toggle_disable');
    });

    Route::prefix('/service-purchase')->group(function (){
        Route::get('/', [ServicePurchaseController::class, 'index'])->name('service_purchase_index');
        Route::post('/purchase', [ServicePurchaseController::class, 'purchase'])->name('service_purchase_purchase');
    });

    Route::prefix('/service-orders')->group(function () {
        Route::get('/', [ServiceOrderController::class, 'index'])->name('service_orders_index');
        Route::post('/{id}/approve', [ServiceOrderController::class, 'approve'])->name('service_orders_approve');
        Route::post('/{id}/cancel', [ServiceOrderController::class, 'cancel'])->name('service_orders_cancel');
        Route::put('/{id}/config', [ServiceOrderController::class, 'updateConfig'])->name('service_orders_update_config');
        Route::delete('/{id}', [ServiceOrderController::class, 'destroy'])->name('service_orders_destroy');
    });

    Route::prefix('/service-management')->group(function () {
        Route::get('/', [ServiceManagementController::class, 'index'])->name('service_management_index');
    });

    Route::prefix('/spend-report')->group(function () {
        Route::get('/', [SpendReportController::class, 'index'])->name('spend_report_index');
    });

    Route::prefix('/business-managers')->group(function () {
        Route::get('/', [BusinessManagerController::class, 'index'])->name('business_managers_index');
        Route::get('/{bmId}/accounts', [BusinessManagerController::class, 'getAccounts'])->name('business_managers_get_accounts');
        Route::get('/{parentBmId}/child-business-managers', [BusinessManagerController::class, 'getChildBusinessManagers'])->name('business_managers_get_child_bms');
        Route::post('/{bmId}/top-up', [BusinessManagerController::class, 'topUp'])->name('business_managers_top_up');
    });

    Route::prefix('/profit')->group(function () {
        Route::get('/by-customer', [ProfitController::class, 'byCustomer'])->name('profit_by_customer');
        Route::get('/by-platform', [ProfitController::class, 'byPlatform'])->name('profit_by_platform');
        Route::get('/over-time', [ProfitController::class, 'overTime'])->name('profit_over_time');
        Route::get('/by-bm-mcc', [ProfitController::class, 'byBmMcc'])->name('profit_by_bm_mcc');
    });

    Route::prefix('/tickets')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('ticket_index');
        Route::get('/transfer', [TicketController::class, 'transfer'])->name('ticket_transfer');
        Route::post('/transfer', [TicketController::class, 'storeTransfer'])->name('ticket_transfer_store');
        Route::get('/refund', [TicketController::class, 'refund'])->name('ticket_refund');
        Route::post('/refund', [TicketController::class, 'storeRefund'])->name('ticket_refund_store');
        Route::get('/appeal', [TicketController::class, 'appeal'])->name('ticket_appeal');
        Route::post('/appeal', [TicketController::class, 'storeAppeal'])->name('ticket_appeal_store');
        Route::get('/share', [TicketController::class, 'share'])->name('ticket_share');
        Route::post('/share', [TicketController::class, 'storeShare'])->name('ticket_share_store');
        Route::get('/withdraw-app', [TicketController::class, 'withdrawApp'])->name('ticket_withdraw_app');
        Route::get('/deposit-app', [TicketController::class, 'depositApp'])->name('ticket_deposit_app');
        Route::get('/create-account', [TicketController::class, 'createAccount'])->name('ticket_create_account');
        Route::post('/create-account', [TicketController::class, 'storeCreateAccount'])->name('ticket_create_account_store');
        Route::get('/{id}', [TicketController::class, 'show'])->name('ticket_show');
        Route::post('/', [TicketController::class, 'store'])->name('ticket_store');
        Route::post('/{id}/message', [TicketController::class, 'addMessage'])->name('ticket_add_message');
        Route::put('/{id}/status', [TicketController::class, 'updateStatus'])->name('ticket_update_status');
    });

    Route::prefix('/meta')->group(function () {
        Route::get('/{serviceUserId}/accounts', [MetaController::class, 'getAdsAccount'])->name('meta_get_accounts');
        Route::get('/{serviceUserId}/{accountId}/campaigns', [MetaController::class, 'getCampaigns'])->name('meta_get_campaigns');
        Route::get('/{serviceUserId}/{campaignId}/detail-campaign', [MetaController::class, 'detailCampaign'])->name('meta_detail_campaign');
        Route::get('/{serviceUserId}/{campaignId}/detail-campaign-insight', [MetaController::class, 'getCampaignInsights'])->name('meta_detail_campaign_insight');
        Route::post('/{serviceUserId}/{campaignId}/status', [MetaController::class, 'updateCampaignStatus'])->name('meta_update_campaign_status');
        Route::post('/{serviceUserId}/{campaignId}/spend-cap', [MetaController::class, 'updateCampaignSpendCap'])->name('meta_update_campaign_spend_cap');
    });

    Route::prefix('/google-ads')->group(function () {
        Route::get('/{serviceUserId}/accounts', [GoogleAdsController::class, 'getAdsAccount'])->name('google_ads_get_accounts');
        Route::get('/{serviceUserId}/{accountId}/campaigns', [GoogleAdsController::class, 'getCampaigns'])->name('google_ads_get_campaigns');
        Route::get('/{serviceUserId}/{campaignId}/detail-campaign', [GoogleAdsController::class, 'detailCampaign'])->name('google_ads_detail_campaign');
        Route::get('/{serviceUserId}/{campaignId}/detail-campaign-insight', [GoogleAdsController::class, 'getCampaignInsights'])->name('google_ads_detail_campaign_insight');
        Route::post('/{serviceUserId}/{campaignId}/status', [GoogleAdsController::class, 'updateCampaignStatus'])->name('google_ads_update_campaign_status');
        Route::post('/{serviceUserId}/{campaignId}/budget', [GoogleAdsController::class, 'updateCampaignBudget'])->name('google_ads_update_campaign_budget');
    });
});
