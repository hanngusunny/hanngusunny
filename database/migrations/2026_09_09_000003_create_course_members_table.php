<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_members')) {
            return;
        }

        Schema::create('course_members', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('user_id');
            $table->enum('status', ['active', 'left', 'completed'])->default('active');
            $table->dateTime('joined_at')->nullable();
            $table->dateTime('left_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedInteger('active_user_key')->nullable();
            $table->timestamps();

            $table->unique('active_user_key', 'course_members_one_active_user');
            $table->index(['course_id', 'user_id'], 'course_id_user_id');
            $table->index(['user_id', 'status'], 'user_id_status');
            $table->foreign('course_id', 'course_members_course_id_foreign')
                ->references('id')->on('courses')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('user_id', 'course_members_user_id_foreign')
                ->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_members');
    }
};
