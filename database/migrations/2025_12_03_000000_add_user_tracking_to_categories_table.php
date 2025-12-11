<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            // Thêm user_id để biết ai tạo category
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            // Thêm status để admin duyệt
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('is_active');
            
            // Thêm admin notes
            $table->text('admin_notes')->nullable()->after('status');
            
            // Thêm timestamps cho approve/reject
            $table->timestamp('approved_at')->nullable()->after('admin_notes');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->unsignedBigInteger('approved_by')->nullable()->after('rejected_at');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'user_id',
                'status',
                'admin_notes',
                'approved_at',
                'rejected_at',
                'approved_by'
            ]);
        });
    }
};
