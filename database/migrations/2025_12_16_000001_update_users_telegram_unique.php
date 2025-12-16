<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Chuyển unique telegram_id thành unique theo cặp (telegram_id, deleted_at)
     * để cho phép đăng ký lại khi bản ghi cũ đã soft delete.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Xóa unique hiện tại trên telegram_id
            $table->dropUnique('users_telegram_id_unique');
            // Thêm unique mới gồm telegram_id + deleted_at
            $table->unique(['telegram_id', 'deleted_at'], 'users_telegram_id_deleted_at_unique');
        });
    }

    /**
     * Rollback về unique telegram_id cũ.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_telegram_id_deleted_at_unique');
            $table->unique('telegram_id', 'users_telegram_id_unique');
        });
    }
};

