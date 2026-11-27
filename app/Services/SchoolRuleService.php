<?php

namespace App\Services;

use App\Models\SchoolRule;
use App\Models\User;
use App\Support\HtmlContent;
use Illuminate\Support\Facades\Schema;

class SchoolRuleService
{
    public function tablesReady(): bool
    {
        return Schema::hasTable('school_rules');
    }

    public function current(): SchoolRule
    {
        $row = SchoolRule::query()->with('editor')->orderBy('id')->first();

        if ($row !== null) {
            return $row;
        }

        $row = SchoolRule::query()->create([
            'title' => 'Nội quy lớp học',
            'content' => '',
        ]);

        return $row->fresh(['editor']) ?? $row;
    }

    public function save(string $title, string $content, User $editor): SchoolRule
    {
        $row = $this->current();
        $title = trim($title);

        $row->fill([
            'title' => mb_substr($title !== '' ? $title : 'Nội quy lớp học', 0, 200),
            'content' => HtmlContent::sanitize($content),
            'updated_by' => (int) $editor->id,
        ]);
        $row->save();

        return $row->fresh(['editor']) ?? $row;
    }

    public function canEdit(?User $user): bool
    {
        return $user?->isAdmin() === true;
    }
}
