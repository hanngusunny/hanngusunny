<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_session_attendance') && ! Schema::hasColumn('course_session_attendance', 'attendance_status')) {
            Schema::table('course_session_attendance', function (Blueprint $table) {
                $table->string('attendance_status', 16)->default('present')->after('joined_at');
            });
        }

        if (Schema::hasTable('course_members')) {
            Schema::table('course_members', function (Blueprint $table) {
                if (! Schema::hasColumn('course_members', 'package_type')) {
                    $table->string('package_type', 32)->nullable()->after('status');
                }
                if (! Schema::hasColumn('course_members', 'absence_adjustment')) {
                    $table->smallInteger('absence_adjustment')->default(0)->after('package_type');
                }
                if (! Schema::hasColumn('course_members', 'homework_adjustment')) {
                    $table->smallInteger('homework_adjustment')->default(0)->after('absence_adjustment');
                }
            });
        }

        if (Schema::hasTable('course_monthly_evaluations') && ! Schema::hasColumn('course_monthly_evaluations', 'is_draft')) {
            Schema::table('course_monthly_evaluations', function (Blueprint $table) {
                $table->boolean('is_draft')->default(false)->after('comment');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('course_monthly_evaluations') && Schema::hasColumn('course_monthly_evaluations', 'is_draft')) {
            Schema::table('course_monthly_evaluations', function (Blueprint $table) {
                $table->dropColumn('is_draft');
            });
        }

        if (Schema::hasTable('course_members')) {
            Schema::table('course_members', function (Blueprint $table) {
                $cols = [];
                foreach (['homework_adjustment', 'absence_adjustment', 'package_type'] as $col) {
                    if (Schema::hasColumn('course_members', $col)) {
                        $cols[] = $col;
                    }
                }
                if ($cols !== []) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('course_session_attendance') && Schema::hasColumn('course_session_attendance', 'attendance_status')) {
            Schema::table('course_session_attendance', function (Blueprint $table) {
                $table->dropColumn('attendance_status');
            });
        }
    }
};
