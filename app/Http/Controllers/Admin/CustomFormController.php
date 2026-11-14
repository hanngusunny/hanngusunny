<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomForm;
use App\Models\CustomFormField;
use App\Services\Content\ContentCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomFormController extends Controller
{
    public function __construct(private readonly ContentCatalogService $catalog)
    {
    }

    public function index(): View
    {
        return view('admin.forms.index', [
            'pageTitle' => 'Form tùy chỉnh',
            'adminNavActive' => 'custom_forms',
            'rows' => CustomForm::query()->orderByDesc('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.forms.form', ['pageTitle' => 'Tạo form', 'adminNavActive' => 'custom_forms_add', 'row' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'success_message' => ['nullable', 'string', 'max:500'],
        ]);
        CustomForm::query()->create([
            'name' => $data['name'],
            'slug' => $this->catalog->slugify($data['slug'] !== '' ? $data['slug'] : $data['name']),
            'description' => $data['description'] ?? '',
            'success_message' => $data['success_message'] ?? 'Đã gửi thành công.',
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.forms.index')->with('ok', 'Đã tạo form.');
    }

    public function edit(CustomForm $custom_form): View
    {
        return view('admin.forms.form', ['pageTitle' => 'Sửa form', 'adminNavActive' => 'custom_forms_edit', 'row' => $custom_form]);
    }

    public function update(Request $request, CustomForm $custom_form): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'success_message' => ['nullable', 'string', 'max:500'],
        ]);
        $custom_form->update([
            'name' => $data['name'],
            'slug' => $this->catalog->slugify($data['slug'] !== '' ? $data['slug'] : $data['name']),
            'description' => $data['description'] ?? '',
            'success_message' => $data['success_message'] ?? '',
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.forms.index')->with('ok', 'Đã cập nhật.');
    }

    public function destroy(CustomForm $custom_form): RedirectResponse
    {
        $custom_form->fields()->delete();
        $custom_form->submissions()->delete();
        $custom_form->delete();

        return redirect()->route('admin.forms.index')->with('ok', 'Đã xóa.');
    }

    public function fields(CustomForm $custom_form): View
    {
        return view('admin.forms.fields', [
            'pageTitle' => 'Trường — '.$custom_form->name,
            'adminNavActive' => 'custom_forms_fields',
            'form' => $custom_form,
            'fields' => $custom_form->fields,
            'types' => CustomForm::FIELD_TYPES,
        ]);
    }

    public function storeField(Request $request, CustomForm $custom_form): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'field_type' => ['required', 'in:'.implode(',', CustomForm::FIELD_TYPES)],
            'field_key' => ['nullable', 'string', 'max:64'],
            'options' => ['nullable', 'string'],
            'is_required' => ['sometimes', 'boolean'],
        ]);
        $key = trim((string) ($data['field_key'] ?? ''));
        if ($key === '') {
            $key = $this->catalog->slugify($data['label']);
        }
        CustomFormField::query()->create([
            'form_id' => $custom_form->id,
            'field_key' => str_replace('-', '_', $key),
            'label' => $data['label'],
            'field_type' => $data['field_type'],
            'options' => $data['options'] ?? '',
            'is_required' => $request->boolean('is_required'),
            'sort_order' => (int) $custom_form->fields()->max('sort_order') + 1,
        ]);

        return back()->with('ok', 'Đã thêm trường.');
    }

    public function destroyField(CustomFormField $custom_form_field): RedirectResponse
    {
        $custom_form_field->delete();

        return back()->with('ok', 'Đã xóa trường.');
    }

    public function updateField(Request $request, CustomFormField $custom_form_field): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'field_type' => ['required', 'in:'.implode(',', CustomForm::FIELD_TYPES)],
            'options' => ['nullable', 'string'],
        ]);
        $custom_form_field->update([
            'label' => $data['label'],
            'field_type' => $data['field_type'],
            'options' => $data['options'] ?? '',
            'is_required' => $request->boolean('is_required'),
        ]);

        return back()->with('ok', 'Đã cập nhật trường.');
    }

    public function moveFieldUp(CustomFormField $custom_form_field): RedirectResponse
    {
        return $this->moveField($custom_form_field, 'up');
    }

    public function moveFieldDown(CustomFormField $custom_form_field): RedirectResponse
    {
        return $this->moveField($custom_form_field, 'down');
    }

    private function moveField(CustomFormField $custom_form_field, string $dir): RedirectResponse
    {
        $siblings = CustomFormField::query()->where('form_id', $custom_form_field->form_id)->orderBy('sort_order')->orderBy('id')->get();
        \App\Support\SortOrderMover::move($siblings, (int) $custom_form_field->id, $dir);

        return back()->with('ok', 'Đã đổi thứ tự.');
    }

    public function exportSubmissions(CustomForm $custom_form): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows = $custom_form->submissions()->orderBy('id')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['id', 'created_at', 'ip', 'payload']);
            foreach ($rows as $row) {
                fputcsv($out, [$row->id, (string) $row->created_at, $row->ip, $row->payload_json]);
            }
            fclose($out);
        }, 'form-'.$custom_form->slug.'-submissions.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function submissions(CustomForm $custom_form): View
    {
        return view('admin.forms.submissions', [
            'pageTitle' => 'Gửi form — '.$custom_form->name,
            'adminNavActive' => 'custom_forms_submissions',
            'form' => $custom_form,
            'rows' => $custom_form->submissions()->paginate(30),
        ]);
    }
}
