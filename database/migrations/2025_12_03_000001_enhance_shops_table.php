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
        Schema::table('shops', function (Blueprint $table) {
            // Thêm updated_at (hiện tại chỉ có created_at)
            $table->timestamp('updated_at')->nullable()->after('created_at');
            
            // Thông tin cơ bản
            $table->string('slug', 191)->unique()->nullable()->after('name');
            $table->string('business_name', 255)->nullable()->after('slug'); // Tên doanh nghiệp chính thức
            $table->string('business_registration_number', 50)->nullable()->after('business_name'); // Mã số doanh nghiệp
            $table->enum('business_type', ['individual', 'company', 'enterprise'])->default('individual')->after('business_registration_number');
            
            // Liên hệ
            $table->string('phone', 20)->nullable()->after('description');
            $table->string('email', 191)->nullable()->after('phone');
            $table->string('website', 255)->nullable()->after('email');
            
            // Địa chỉ chi tiết
            $table->text('address')->nullable()->after('website');
            $table->string('ward', 100)->nullable()->after('address'); // Phường/Xã
            $table->string('district', 100)->nullable()->after('ward'); // Quận/Huyện
            $table->string('city', 100)->nullable()->after('district'); // Tỉnh/Thành phố
            $table->string('postal_code', 20)->nullable()->after('city');
            $table->decimal('latitude', 10, 8)->nullable()->after('postal_code');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            
            // Media
            $table->string('logo', 500)->nullable()->after('longitude');
            $table->string('banner', 500)->nullable()->after('logo');
            $table->json('images')->nullable()->after('banner'); // Gallery images
            
            // Social links
            $table->string('facebook_url', 255)->nullable()->after('images');
            $table->string('instagram_url', 255)->nullable()->after('facebook_url');
            $table->string('zalo_phone', 20)->nullable()->after('instagram_url');
            $table->string('youtube_url', 255)->nullable()->after('zalo_phone');
            
            // Business hours
            $table->json('business_hours')->nullable()->after('youtube_url'); // {"monday": "9:00-18:00", ...}
            
            // Status & verification
            $table->boolean('is_active')->default(true)->after('business_hours');
            $table->boolean('is_verified')->default(false)->after('is_active'); // Xác minh doanh nghiệp
            $table->timestamp('verified_at')->nullable()->after('is_verified');
            
            // Statistics (có thể tính từ relationships nhưng cache lại cho nhanh)
            $table->integer('total_products')->default(0)->after('verified_at');
            $table->integer('total_orders')->default(0)->after('total_products');
            $table->integer('total_reviews')->default(0)->after('total_orders');
            $table->decimal('rating', 3, 2)->default(0)->after('total_reviews'); // 0.00 - 5.00
            $table->integer('followers_count')->default(0)->after('rating');
            
            // SEO
            $table->string('meta_title', 255)->nullable()->after('followers_count');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->json('meta_keywords')->nullable()->after('meta_description');
            
            // Policies
            $table->text('return_policy')->nullable()->after('meta_keywords');
            $table->text('shipping_policy')->nullable()->after('return_policy');
            $table->text('warranty_policy')->nullable()->after('shipping_policy');
            
            // Soft delete
            $table->softDeletes()->after('warranty_policy');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn([
                'updated_at',
                'slug',
                'business_name',
                'business_registration_number',
                'business_type',
                'phone',
                'email',
                'website',
                'address',
                'ward',
                'district',
                'city',
                'postal_code',
                'latitude',
                'longitude',
                'logo',
                'banner',
                'images',
                'facebook_url',
                'instagram_url',
                'zalo_phone',
                'youtube_url',
                'business_hours',
                'is_active',
                'is_verified',
                'verified_at',
                'total_products',
                'total_orders',
                'total_reviews',
                'rating',
                'followers_count',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'return_policy',
                'shipping_policy',
                'warranty_policy',
                'deleted_at'
            ]);
        });
    }
};
