<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class QuestionRichTextUpgradeService
{
    /**
     * HTML câu hỏi/đáp án có thể chứa ảnh base64, vượt giới hạn TEXT (64KB).
     *
     * @return list<array{table: string, columns: list<array{name: string, nullable: bool}>}>
     */
    public function targets(): array
    {
        $questionColumns = [
            ['name' => 'question_text', 'nullable' => false],
            ['name' => 'options_json', 'nullable' => true],
            ['name' => 'correct_answer', 'nullable' => true],
            ['name' => 'answer_explanation', 'nullable' => true],
        ];

        return [
            ['table' => 'study_program_session_questions', 'columns' => $questionColumns],
            ['table' => 'study_program_assessment_questions', 'columns' => $questionColumns],
            ['table' => 'study_program_knowledge_review_questions', 'columns' => $questionColumns],
            ['table' => 'course_session_questions', 'columns' => $questionColumns],
            ['table' => 'course_assessment_questions', 'columns' => $questionColumns],
            ['table' => 'course_knowledge_review_questions', 'columns' => $questionColumns],
            [
                'table' => 'online_room_preset_questions',
                'columns' => [
                    ['name' => 'question_text', 'nullable' => false],
                    ['name' => 'options_json', 'nullable' => true],
                ],
            ],
            [
                'table' => 'course_session_quiz_answers',
                'columns' => [
                    ['name' => 'selected_answer', 'nullable' => true],
                    ['name' => 'teacher_comment', 'nullable' => true],
                ],
            ],
            [
                'table' => 'course_assessment_answers',
                'columns' => [
                    ['name' => 'selected_answer', 'nullable' => true],
                    ['name' => 'answer_text', 'nullable' => true],
                    ['name' => 'teacher_comment', 'nullable' => true],
                ],
            ],
            [
                'table' => 'course_assessment_answer_drafts',
                'columns' => [
                    ['name' => 'selected_answer', 'nullable' => true],
                    ['name' => 'answer_text', 'nullable' => true],
                ],
            ],
            [
                'table' => 'course_knowledge_review_answers',
                'columns' => [
                    ['name' => 'selected_answer', 'nullable' => true],
                    ['name' => 'teacher_comment', 'nullable' => true],
                ],
            ],
            [
                'table' => 'course_session_quiz_answer_drafts',
                'columns' => [
                    ['name' => 'selected_answer', 'nullable' => true],
                ],
            ],
            [
                'table' => 'course_knowledge_review_answer_drafts',
                'columns' => [
                    ['name' => 'selected_answer', 'nullable' => true],
                ],
            ],
        ];
    }

    public function sql(string $table, string $column, bool $nullable): string
    {
        $null = $nullable ? 'NULL' : 'NOT NULL';

        return "ALTER TABLE `{$table}` MODIFY `{$column}` LONGTEXT {$null}";
    }

    /**
     * @return list<array{table: string, column: string, nullable: bool, sql: string, data_type: ?string, status: string, label: string}>
     */
    public function inspect(): array
    {
        $rows = [];

        foreach ($this->targets() as $target) {
            $table = $target['table'];

            foreach ($target['columns'] as $column) {
                $sql = $this->sql($table, $column['name'], $column['nullable']);

                if (! Schema::hasTable($table)) {
                    $rows[] = [
                        'table' => $table,
                        'column' => $column['name'],
                        'nullable' => $column['nullable'],
                        'sql' => $sql,
                        'data_type' => null,
                        'status' => 'missing_table',
                        'label' => 'Không có bảng',
                    ];
                    continue;
                }

                if (! Schema::hasColumn($table, $column['name'])) {
                    $rows[] = [
                        'table' => $table,
                        'column' => $column['name'],
                        'nullable' => $column['nullable'],
                        'sql' => $sql,
                        'data_type' => null,
                        'status' => 'missing_column',
                        'label' => 'Không có cột',
                    ];
                    continue;
                }

                $type = $this->columnType($table, $column['name']);
                $ok = $type !== null && strtolower($type) === 'longtext';

                $rows[] = [
                    'table' => $table,
                    'column' => $column['name'],
                    'nullable' => $column['nullable'],
                    'sql' => $sql,
                    'data_type' => $type,
                    'status' => $ok ? 'ok' : 'needs_update',
                    'label' => $ok ? 'Đã LONGTEXT' : 'Cần cập nhật',
                ];
            }
        }

        return $rows;
    }

    /**
     * @return list<array{table: string, column: string, sql: string, action: string, message: string}>
     */
    public function run(): array
    {
        $results = [];

        foreach ($this->inspect() as $row) {
            if (in_array($row['status'], ['missing_table', 'missing_column'], true)) {
                $results[] = [
                    'table' => $row['table'],
                    'column' => $row['column'],
                    'sql' => $row['sql'],
                    'action' => 'skipped',
                    'message' => $row['label'],
                ];
                continue;
            }

            if ($row['status'] === 'ok') {
                $results[] = [
                    'table' => $row['table'],
                    'column' => $row['column'],
                    'sql' => $row['sql'],
                    'action' => 'skipped',
                    'message' => 'Đã là LONGTEXT, bỏ qua',
                ];
                continue;
            }

            try {
                DB::statement($row['sql']);
                $results[] = [
                    'table' => $row['table'],
                    'column' => $row['column'],
                    'sql' => $row['sql'],
                    'action' => 'updated',
                    'message' => 'Đã đổi sang LONGTEXT',
                ];
            } catch (Throwable $e) {
                $results[] = [
                    'table' => $row['table'],
                    'column' => $row['column'],
                    'sql' => $row['sql'],
                    'action' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    public function markMigrationRan(): void
    {
        $migration = '2026_10_07_000001_expand_question_rich_text_to_longtext';

        if (! Schema::hasTable('migrations')) {
            return;
        }

        if (DB::table('migrations')->where('migration', $migration)->exists()) {
            return;
        }

        $batch = (int) DB::table('migrations')->max('batch');

        DB::table('migrations')->insert([
            'migration' => $migration,
            'batch' => $batch + 1,
        ]);
    }

    public function pendingCount(): int
    {
        return count(array_filter(
            $this->inspect(),
            static fn (array $row): bool => $row['status'] === 'needs_update'
        ));
    }

    private function columnType(string $table, string $column): ?string
    {
        $row = DB::selectOne(
            'SELECT DATA_TYPE AS data_type
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
             LIMIT 1',
            [$table, $column]
        );

        return $row->data_type ?? null;
    }
}
