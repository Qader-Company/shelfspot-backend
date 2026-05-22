<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['company_id', 'slug'], 'brands_company_slug_unique');
        });

        Schema::table('sub_brands', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['company_id', 'slug'], 'sub_brands_company_slug_unique');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['company_id', 'slug'], 'categories_company_slug_unique');
        });

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['company_id', 'slug'], 'sub_categories_company_slug_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['company_id', 'slug'], 'products_company_slug_unique');
            $table->unique(['company_id', 'sku'], 'products_company_sku_unique');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('form_key')->nullable()->after('slug');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['form_key', 'sort_order']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_company_sku_unique');
            $table->dropUnique('products_company_slug_unique');
            $table->unique('slug');
        });

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropUnique('sub_categories_company_slug_unique');
            $table->unique('slug');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_company_slug_unique');
            $table->unique('slug');
        });

        Schema::table('sub_brands', function (Blueprint $table) {
            $table->dropUnique('sub_brands_company_slug_unique');
            $table->unique('slug');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropUnique('brands_company_slug_unique');
            $table->unique('slug');
        });
    }
};
