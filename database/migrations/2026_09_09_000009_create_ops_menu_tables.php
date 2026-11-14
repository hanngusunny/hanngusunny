<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_login_sessions')) {
            Schema::create('user_login_sessions', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->dateTime('login_at');
                $table->dateTime('logout_at')->nullable();
                $table->dateTime('last_seen_at')->nullable();
                $table->unsignedInteger('active_seconds')->default(0);
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'login_at']);
            });
        }

        if (! Schema::hasTable('user_activity_logs')) {
            Schema::create('user_activity_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('login_session_id')->nullable();
                $table->string('action_type', 40);
                $table->string('action_label');
                $table->string('http_method', 10)->nullable();
                $table->string('route_path')->nullable();
                $table->string('url', 512)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('context_json')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->index(['user_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('user_daily_stats')) {
            Schema::create('user_daily_stats', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->date('stat_date');
                $table->unsignedInteger('active_seconds')->default(0);
                $table->unsignedInteger('page_views')->default(0);
                $table->unsignedInteger('action_count')->default(0);
                $table->dateTime('first_seen_at')->nullable();
                $table->dateTime('last_seen_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'stat_date']);
            });
        }

        if (! Schema::hasTable('school_rules')) {
            Schema::create('school_rules', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title', 200)->default('Nội quy lớp học');
                $table->text('content')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
            });

            DB::table('school_rules')->insert([
                'title' => 'Nội quy lớp học',
                'content' => '<h2>Nội quy chung</h2><p>Giáo viên và quản trị viên có thể cập nhật nội quy tại đây. Học viên vui lòng đọc kỹ và tuân thủ trong quá trình học.</p><ul><li>Đi học đúng giờ, tham gia đầy đủ các buổi học trực tuyến.</li><li>Giữ thái độ lịch sự, tôn trọng giáo viên và bạn học.</li><li>Không chia sẻ tài khoản, bài kiểm tra hoặc tài liệu học tập trái phép.</li><li>Nộp bài tập và bài kiểm tra đúng hạn theo hướng dẫn của giáo viên.</li></ul>',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('online_room_preset_questions')) {
            Schema::create('online_room_preset_questions', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('room_id');
                $table->text('question_text');
                $table->enum('question_type', ['fill', 'choice'])->default('fill');
                $table->text('options_json')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index(['room_id', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('online_room_preset_questions');
        Schema::dropIfExists('user_activity_logs');
        Schema::dropIfExists('user_daily_stats');
        Schema::dropIfExists('user_login_sessions');
        Schema::dropIfExists('school_rules');
    }
};
