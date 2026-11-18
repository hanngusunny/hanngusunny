<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nav_menu')) {
            Schema::create('nav_menu', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 120);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('nav_menu_item')) {
            Schema::create('nav_menu_item', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('nav_menu_id');
                $table->unsignedInteger('parent_id')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->string('item_type', 32)->default('custom');
                $table->unsignedInteger('ref_id')->nullable();
                $table->string('label', 191);
                $table->string('url', 512)->nullable();
                $table->boolean('target_blank')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('nav_menu_id')->references('id')->on('nav_menu')->cascadeOnDelete();
                $table->foreign('parent_id')->references('id')->on('nav_menu_item')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('slider_category')) {
            Schema::create('slider_category', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 120);
                $table->string('slug', 120)->unique();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('slider_slide')) {
            Schema::create('slider_slide', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('category_id');
                $table->string('image_desktop', 512);
                $table->string('image_mobile', 512)->nullable();
                $table->text('content')->nullable();
                $table->string('link_url', 512)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('category_id')->references('id')->on('slider_category')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('faq_category')) {
            Schema::create('faq_category', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 120);
                $table->string('slug', 120)->unique();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('faq_item')) {
            Schema::create('faq_item', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('category_id');
                $table->text('question');
                $table->longText('answer');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('category_id')->references('id')->on('faq_category')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('custom_form')) {
            Schema::create('custom_form', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 200);
                $table->string('slug', 100)->unique();
                $table->text('description')->nullable();
                $table->text('success_message')->nullable();
                $table->boolean('is_active')->default(true);
                $table->longText('style_json')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('custom_form_field')) {
            Schema::create('custom_form_field', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('form_id');
                $table->string('field_key', 64);
                $table->string('label', 191);
                $table->string('field_type', 32)->default('text');
                $table->text('options')->nullable();
                $table->string('placeholder', 191)->nullable();
                $table->string('help_text', 255)->nullable();
                $table->boolean('is_required')->default(false);
                $table->string('width', 32)->default('full');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('form_id')->references('id')->on('custom_form')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('custom_form_submission')) {
            Schema::create('custom_form_submission', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('form_id');
                $table->longText('payload_json');
                $table->string('ip', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->dateTime('created_at')->nullable();

                $table->foreign('form_id')->references('id')->on('custom_form')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('consult_lead')) {
            Schema::create('consult_lead', function (Blueprint $table) {
                $table->increments('id');
                $table->string('topic_key', 64)->nullable();
                $table->string('topic_label', 120)->nullable();
                $table->string('full_name', 120);
                $table->string('phone', 32);
                $table->string('province', 120)->nullable();
                $table->text('message')->nullable();
                $table->string('client_ip', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('source_page', 512)->nullable();
                $table->string('form_origin', 100)->nullable();
                $table->dateTime('read_at')->nullable();
                $table->dateTime('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('hotline_click')) {
            Schema::create('hotline_click', function (Blueprint $table) {
                $table->increments('id');
                $table->string('source_page', 512)->nullable();
                $table->string('client_ip', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->dateTime('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('api_tokens')) {
            Schema::create('api_tokens', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->string('token_hash', 64)->unique();
                $table->dateTime('expires_at')->nullable();
                $table->dateTime('last_used_at')->nullable();
                $table->dateTime('created_at')->nullable();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
        Schema::dropIfExists('hotline_click');
        Schema::dropIfExists('consult_lead');
        Schema::dropIfExists('custom_form_submission');
        Schema::dropIfExists('custom_form_field');
        Schema::dropIfExists('custom_form');
        Schema::dropIfExists('faq_item');
        Schema::dropIfExists('faq_category');
        Schema::dropIfExists('slider_slide');
        Schema::dropIfExists('slider_category');
        Schema::dropIfExists('nav_menu_item');
        Schema::dropIfExists('nav_menu');
    }
};
