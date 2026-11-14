<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_notification_reads')) {
            return;
        }

        Schema::create('user_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('notification_key', 120);
            $table->dateTime('read_at')->nullable();

            $table->unique(['user_id', 'notification_key']);
            $table->index(['user_id', 'read_at']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_reads');
    }
};
