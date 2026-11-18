<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('content_category')) {
            Schema::create('content_category', function (Blueprint $table) {
                $table->increments('id');
                $table->string('scope', 32)->default('post')->index();
                $table->unsignedInteger('parent_id')->nullable()->index();
                $table->string('name', 200);
                $table->string('slug', 200);
                $table->longText('description')->nullable();
                $table->string('meta_title', 255)->nullable();
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords', 255)->nullable();
                $table->string('seo_robots', 50)->nullable();
                $table->string('og_title', 255)->nullable();
                $table->text('og_description')->nullable();
                $table->string('og_image', 512)->nullable();
                $table->string('logo_url', 512)->nullable();
                $table->string('cover_image', 512)->nullable();
                $table->string('official_website_url', 512)->nullable();
                $table->decimal('map_lat', 10, 7)->nullable();
                $table->decimal('map_lng', 10, 7)->nullable();
                $table->string('map_region', 100)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['scope', 'slug'], 'content_category_scope_slug_unique');
                $table->foreign('parent_id')->references('id')->on('content_category')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('content_tag')) {
            Schema::create('content_tag', function (Blueprint $table) {
                $table->increments('id');
                $table->string('scope', 32)->default('post')->index();
                $table->string('name', 120);
                $table->string('slug', 120);
                $table->text('description')->nullable();
                $table->string('meta_title', 255)->nullable();
                $table->text('meta_description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['scope', 'slug'], 'content_tag_scope_slug_unique');
            });
        }

        if (! Schema::hasTable('content_template')) {
            Schema::create('content_template', function (Blueprint $table) {
                $table->increments('id');
                $table->string('scope', 32)->default('post');
                $table->string('page_type', 50)->default('article');
                $table->string('slug', 100)->unique();
                $table->string('name', 200);
                $table->string('rel_path', 255)->nullable();
                $table->longText('content')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('content_post')) {
            Schema::create('content_post', function (Blueprint $table) {
                $table->increments('id');
                $table->string('scope', 32)->default('post')->index();
                $table->unsignedInteger('category_id')->nullable()->index();
                $table->unsignedInteger('template_id')->nullable()->index();
                $table->string('title', 255);
                $table->string('slug', 200);
                $table->text('excerpt')->nullable();
                $table->longText('body')->nullable();
                $table->string('featured_image', 512)->nullable();
                $table->string('status', 32)->default('draft')->index();
                $table->dateTime('published_at')->nullable()->index();
                $table->string('meta_title', 255)->nullable();
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords', 255)->nullable();
                $table->string('seo_robots', 50)->nullable();
                $table->string('og_title', 255)->nullable();
                $table->text('og_description')->nullable();
                $table->string('og_image', 512)->nullable();
                $table->unsignedBigInteger('view_count')->default(0);
                $table->timestamps();

                $table->unique(['scope', 'slug'], 'content_post_scope_slug_unique');
                $table->foreign('category_id')->references('id')->on('content_category')->nullOnDelete();
                $table->foreign('template_id')->references('id')->on('content_template')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('content_post_tag')) {
            Schema::create('content_post_tag', function (Blueprint $table) {
                $table->unsignedInteger('post_id');
                $table->unsignedInteger('tag_id');

                $table->primary(['post_id', 'tag_id']);
                $table->foreign('post_id')->references('id')->on('content_post')->cascadeOnDelete();
                $table->foreign('tag_id')->references('id')->on('content_tag')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('content_comment')) {
            Schema::create('content_comment', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('post_id');
                $table->string('scope', 32)->default('post');
                $table->string('author_name', 120);
                $table->string('author_email', 191)->nullable();
                $table->text('body');
                $table->string('status', 32)->default('pending')->index();
                $table->string('ip', 45)->nullable();
                $table->timestamps();

                $table->foreign('post_id')->references('id')->on('content_post')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('content_comment');
        Schema::dropIfExists('content_post_tag');
        Schema::dropIfExists('content_post');
        Schema::dropIfExists('content_template');
        Schema::dropIfExists('content_tag');
        Schema::dropIfExists('content_category');
    }
};
