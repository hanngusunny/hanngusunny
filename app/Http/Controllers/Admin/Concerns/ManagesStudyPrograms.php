<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\StudyProgram;
use App\Support\Questions\QuestionParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

trait ManagesStudyPrograms
{
    protected function findProgram(int $id): ?StudyProgram
    {
        return StudyProgram::query()->find($id);
    }

    protected function archivedRedirect(StudyProgram $program): RedirectResponse
    {
        return redirect()
            ->route('admin.programs.show', ['id' => $program->id, 'tab' => 'info'])
            ->with('error', 'Chương trình đã lưu trữ.');
    }

    protected function jsonError(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['ok' => false, 'message' => $message], $status);
    }

    /**
     * @return array<string, mixed>
     */
    protected function questionPayload(Request $request): array
    {
        $item = $request->input('question', []);

        return is_array($item) ? $item : [];
    }

    /**
     * @return list<array{slug: string, label: string, type: string, icon: string, desc: string}>
     */
    protected function extendedQuestionTypes(): array
    {
        return [
            ['slug' => 'tu-luan', 'label' => 'Tự luận', 'icon' => 'fa-pencil', 'desc' => 'Học viên nhập câu trả lời tự do', 'type' => 'fill'],
            ['slug' => 'trac-nghiem', 'label' => 'Trắc nghiệm', 'icon' => 'fa-list-ul', 'desc' => 'Một đáp án đúng A–D', 'type' => 'choice'],
            ['slug' => 'nhieu-dap-an', 'label' => 'Nhiều đáp án', 'icon' => 'fa-square-check', 'desc' => 'Chọn nhiều đáp án đúng', 'type' => 'multi_select'],
            ['slug' => 'lat-anh', 'label' => 'Lật ảnh', 'icon' => 'fa-images', 'desc' => 'Ghép cặp ảnh với chữ', 'type' => 'memory_match'],
            ['slug' => 'noi-tu', 'label' => 'Nối từ', 'icon' => 'fa-link', 'desc' => 'Nối cột trái với cột phải', 'type' => 'word_link'],
            ['slug' => 'sap-xep-cau', 'label' => 'Sắp xếp câu', 'icon' => 'fa-arrow-down-a-z', 'desc' => 'Sắp xếp từ thành câu hoàn chỉnh', 'type' => 'word_order'],
            ['slug' => 'nghe-dap', 'label' => 'Nghe đáp', 'icon' => 'fa-headphones', 'desc' => 'Nghe MP3 và chọn đáp án', 'type' => 'listening'],
            ['slug' => 'phan-loai', 'label' => 'Phân loại', 'icon' => 'fa-table-columns', 'desc' => 'Kéo thả vào các nhóm', 'type' => 'category_sort'],
            ['slug' => 'nhieu-cho-trong', 'label' => 'Nhiều chỗ trống', 'icon' => 'fa-i-cursor', 'desc' => 'Điền nhiều từ vào đoạn', 'type' => 'fill_blanks'],
            ['slug' => 'chon-tu', 'label' => 'Chọn từ', 'icon' => 'fa-hand-pointer', 'desc' => 'Chọn từ đúng trong danh sách', 'type' => 'word_select'],
            ['slug' => 'dich-tu', 'label' => 'Dịch từ', 'icon' => 'fa-language', 'desc' => 'Dịch từng mục', 'type' => 'word_translate'],
            ['slug' => 'phat-am', 'label' => 'Phát âm', 'icon' => 'fa-microphone', 'desc' => 'Luyện phát âm', 'type' => 'phat_am'],
            ['slug' => 'click-anh', 'label' => 'Click ảnh', 'icon' => 'fa-bullseye', 'desc' => 'Bấm điểm đúng trên ảnh', 'type' => 'image_hotspot'],
            ['slug' => 'keo-nhan-anh', 'label' => 'Kéo nhãn ảnh', 'icon' => 'fa-up-down-left-right', 'desc' => 'Kéo nhãn vào vị trí trên ảnh', 'type' => 'image_label_drop'],
        ];
    }

    protected function resolveQuestionType(?string $slug): ?string
    {
        $slug = trim((string) $slug);

        if ($slug === '') {
            return null;
        }

        foreach ($this->extendedQuestionTypes() as $item) {
            if ($item['slug'] === $slug) {
                return $item['type'];
            }
        }

        return in_array($slug, QuestionParser::TYPES, true) ? $slug : null;
    }

    protected function groupFromSlug(string $slug): ?string
    {
        return match ($slug) {
            'kiem-tra', 'review' => 'review',
            'trac-nghiem', 'quiz' => 'quiz',
            'on-tap', 'post_practice' => 'post_practice',
            'truoc-buoi', 'pre' => 'pre',
            default => null,
        };
    }

    protected function groupSlug(string $group): string
    {
        return match ($group) {
            'review' => 'kiem-tra',
            'post_practice' => 'on-tap',
            'pre' => 'truoc-buoi',
            default => 'trac-nghiem',
        };
    }

    protected function requireUploadedFile(Request $request, string $field): ?UploadedFile
    {
        $file = $request->file($field);

        return $file instanceof UploadedFile ? $file : null;
    }
}
