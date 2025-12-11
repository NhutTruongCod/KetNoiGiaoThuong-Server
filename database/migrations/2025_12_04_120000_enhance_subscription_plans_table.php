<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Step 1: Add columns without unique constraint first (check if exists)
        Schema::table('subscription_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_plans', 'slug')) {
                $table->string('slug', 50)->nullable()->after('name');
            }
            if (!Schema::hasColumn('subscription_plans', 'description')) {
                $table->text('description')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('subscription_plans', 'currency')) {
                $table->string('currency', 3)->default('VND')->after('price');
            }
            if (!Schema::hasColumn('subscription_plans', 'benefits')) {
                $table->json('benefits')->nullable()->after('features');
            }
            if (!Schema::hasColumn('subscription_plans', 'is_popular')) {
                $table->boolean('is_popular')->default(false)->after('features');
            }
            if (!Schema::hasColumn('subscription_plans', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_popular');
            }
            // is_active already exists from previous migration
            if (!Schema::hasColumn('subscription_plans', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
        
        // Step 2: Update existing records with slug based on name
        DB::statement("UPDATE subscription_plans SET slug = LOWER(REPLACE(name, ' ', '-')) WHERE slug IS NULL OR slug = ''");
        
        // Step 3: Add unique constraint if not exists
        $indexes = DB::select("SHOW INDEX FROM subscription_plans WHERE Key_name = 'subscription_plans_slug_unique'");
        if (empty($indexes)) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->unique('slug');
            });
        }
    }

    public function down()
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn([
                'slug',
                'description',
                'currency',
                'benefits',
                'is_popular',
                'sort_order',
                // Don't drop is_active as it was added by previous migration
                'updated_at'
            ]);
        });
    }
};
