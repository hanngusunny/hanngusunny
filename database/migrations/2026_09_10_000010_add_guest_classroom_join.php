<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('online_room_join_requests') && Schema::hasTable('online_rooms')) {
            Schema::create('online_room_join_requests', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('room_id');
                $table->unsignedInteger('user_id')->nullable();
                $table->string('guest_name', 120)->nullable();
                $table->string('guest_token', 64)->nullable()->unique();
                $table->unsignedTinyInteger('is_guest')->default(0);
                $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
                $table->dateTime('requested_at');
                $table->dateTime('reviewed_at')->nullable();
                $table->unsignedInteger('reviewed_by')->nullable();
                $table->string('review_note', 255)->nullable();
                $table->timestamps();

                $table->index(['room_id', 'status']);
                $table->index(['user_id', 'status']);
                $table->foreign('room_id')->references('id')->on('online_rooms')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete()->cascadeOnUpdate();
                $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            $column = DB::select("SHOW COLUMNS FROM `users` LIKE 'role'");
            $type = strtolower((string) ($column[0]->Type ?? ''));

            if ($type !== '' && ! str_contains($type, "'guest'")) {
                DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin','teacher','student','accountant','study_abroad_student','lecturer','guest') NOT NULL DEFAULT 'student'");
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('online_room_join_requests');
    }
};
