<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username', 50)->unique();
            $table->string('email', 120)->unique();
            $table->string('phone', 20)->nullable()->unique();
            $table->string('password');
            $table->string('full_name', 120)->nullable();
            $table->string('student_code', 10)->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->enum('role', [
                'admin',
                'teacher',
                'student',
                'accountant',
                'study_abroad_student',
                'lecturer',
            ])->default('student');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->dateTime('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
