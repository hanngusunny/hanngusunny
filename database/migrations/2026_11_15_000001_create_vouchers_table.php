<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('student_name', 255);
            $table->string('phone', 50);
            $table->date('dob')->nullable();
            $table->string('status', 32)->default('unused')->index();
            $table->string('image_path', 255)->nullable();
            $table->timestamp('used_at')->nullable();
            $table->unsignedInteger('used_by')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->foreign('used_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
