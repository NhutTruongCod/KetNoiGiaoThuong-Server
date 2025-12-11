<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $t) {
            // company_id: công ty / shop bán
            $t->foreign('company_id', 'tx_company_fk')
              ->references('id')->on('shops')
              ->onDelete('cascade');   // QUY TẮC XÓA: tuỳ bạn, xem ghi chú bên dưới

            // user_id: người thực hiện thanh toán (có thể null)
            $t->foreign('user_id', 'tx_user_fk')
              ->references('id')->on('users')
              ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $t) {
            $t->dropForeign('tx_company_fk');
            $t->dropForeign('tx_user_fk');
        });
    }
};
