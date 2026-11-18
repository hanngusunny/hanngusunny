<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            Schema::create('site_settings', function (Blueprint $table) {
                $table->string('setting_key', 100)->primary();
                $table->longText('setting_value')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('site_block')) {
            Schema::create('site_block', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 200);
                $table->string('slug', 100)->unique();
                $table->string('block_type', 50)->default('html');
                $table->longText('config_json')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('site_media_category')) {
            Schema::create('site_media_category', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 120);
                $table->string('slug', 120)->unique();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('site_media')) {
            Schema::create('site_media', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('category_id')->nullable();
                $table->string('path', 512);
                $table->string('original_name', 255)->nullable();
                $table->string('title', 255)->nullable();
                $table->string('alt_text', 255)->nullable();
                $table->string('mime', 100)->nullable();
                $table->unsignedBigInteger('file_size')->default(0);
                $table->dateTime('created_at')->nullable();

                $table->index('category_id');
                $table->foreign('category_id')->references('id')->on('site_media_category')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('site_visit_log')) {
            Schema::create('site_visit_log', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('visitor_id', 64)->nullable()->index();
                $table->string('session_id', 64)->nullable();
                $table->boolean('is_new_visitor')->default(false);
                $table->boolean('is_bot')->default(false);
                $table->string('request_path', 512)->nullable();
                $table->text('query_string')->nullable();
                $table->string('page_url', 1024)->nullable();
                $table->string('http_method', 10)->nullable();
                $table->unsignedSmallInteger('status_code')->nullable();
                $table->string('referrer', 1024)->nullable();
                $table->string('referrer_host', 255)->nullable();
                $table->string('source_type', 50)->nullable();
                $table->string('search_keyword', 255)->nullable();
                $table->string('utm_source', 100)->nullable();
                $table->string('utm_medium', 100)->nullable();
                $table->string('utm_campaign', 100)->nullable();
                $table->string('utm_term', 100)->nullable();
                $table->string('utm_content', 100)->nullable();
                $table->string('client_ip', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('device_type', 50)->nullable();
                $table->string('browser', 50)->nullable();
                $table->string('os', 50)->nullable();
                $table->string('accept_language', 100)->nullable();
                $table->dateTime('created_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visit_log');
        Schema::dropIfExists('site_media');
        Schema::dropIfExists('site_media_category');
        Schema::dropIfExists('site_block');
        Schema::dropIfExists('site_settings');
    }
};
