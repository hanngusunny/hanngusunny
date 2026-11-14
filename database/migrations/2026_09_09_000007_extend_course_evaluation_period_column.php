<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('course_monthly_evaluations') || ! Schema::hasColumn('course_monthly_evaluations', 'eval_month')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE course_monthly_evaluations MODIFY eval_month VARCHAR(10) NOT NULL COMMENT 'YYYY-MM or YYYY-Www'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('course_monthly_evaluations') || ! Schema::hasColumn('course_monthly_evaluations', 'eval_month')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE course_monthly_evaluations MODIFY eval_month CHAR(7) NOT NULL COMMENT 'YYYY-MM'");
        }
    }
};
