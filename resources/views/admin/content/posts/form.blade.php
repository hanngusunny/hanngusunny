@extends('admin.layouts.app')

@section('content')
  <h1 class="admin-page-title">{{ $post ? 'Sửa' : 'Thêm' }} — {{ $cfg['label'] }}</h1>
  <form method="post" action="{{ $post ? route('admin.'.$module.'.posts.update', $post) : route('admin.'.$module.'.posts.store') }}">
    @csrf
    <x-sunny.input name="title" label="Tiêu đề" :value="$post->title ?? ''" :required="true" />
    <x-sunny.input name="slug" label="Slug" :value="$post->slug ?? ''" />

    <x-sunny.select name="category_id" label="Chuyên mục" :selected="$post->category_id ?? ''">
      <option value="">—</option>
      @foreach ($categories as $c)
        <option value="{{ $c->id }}" @selected((string) old('category_id', $post->category_id ?? '') === (string) $c->id)>{{ $c->name }}</option>
      @endforeach
    </x-sunny.select>

    <label class="sn-label">Tags</label>
    <div class="flex flex-wrap gap-2 mb-2">
      @foreach ($tags ?? [] as $t)
        <label class="form-check-label me-2">
          <input type="checkbox" name="tag_ids[]" value="{{ $t->id }}" @checked(in_array((int) $t->id, array_map('intval', (array) old('tag_ids', $selectedTagIds ?? [])), true))>
          {{ $t->name }}
        </label>
      @endforeach
    </div>

    <div class="sn-form-group">
      <label class="sn-label" for="post_featured">Ảnh đại diện</label>
      <div class="flex gap-2">
        <input class="sn-input" name="featured_image" id="post_featured" value="{{ old('featured_image', $post->featured_image ?? '') }}">
        <x-sunny.button type="button" variant="white" data-media-target="#post_featured">Thư viện</x-sunny.button>
      </div>
    </div>

    <div class="sn-form-group">
      <label class="sn-label" for="post_og">OG image</label>
      <div class="flex gap-2">
        <input class="sn-input" name="og_image" id="post_og" value="{{ old('og_image', $post->og_image ?? '') }}">
        <x-sunny.button type="button" variant="white" data-media-target="#post_og">Thư viện</x-sunny.button>
      </div>
    </div>

    <x-sunny.input name="og_title" label="OG title" :value="$post->og_title ?? ''" />
    <x-sunny.input name="og_description" label="OG description" :value="$post->og_description ?? ''" />

    <x-sunny.select name="template_id" label="Template bài" :selected="$post->template_id ?? ''">
      <option value="">Mặc định Blade</option>
      @foreach ($templates ?? [] as $tpl)
        <option value="{{ $tpl->id }}" @selected((string) old('template_id', $post->template_id ?? '') === (string) $tpl->id)>{{ $tpl->name }}</option>
      @endforeach
    </x-sunny.select>

    <x-sunny.input name="meta_title" label="SEO title" :value="$post->meta_title ?? ''" />
    <x-sunny.input name="meta_description" label="SEO description" :value="$post->meta_description ?? ''" />

    <x-sunny.textarea name="excerpt" label="Tóm tắt" :rows="3" :value="$post->excerpt ?? ''" />
    <x-sunny.textarea class="js-richtext" name="body" label="Nội dung" :rows="12" :value="$post->body ?? ''" />

    <x-sunny.select name="status" label="Trạng thái" :selected="$post->status ?? 'draft'">
      <option value="draft" @selected(old('status', $post->status ?? 'draft') === 'draft')>draft</option>
      <option value="published" @selected(old('status', $post->status ?? '') === 'published')>published</option>
    </x-sunny.select>

    <x-sunny.button type="submit" class="mt-3">Lưu</x-sunny.button>
  </form>

  <div id="mediaPickerModal" hidden data-json-url="{{ route('admin.media.json') }}" class="border p-3 mt-3">
    <div class="flex justify-between">
      <strong>Chọn ảnh</strong>
      <button type="button" data-media-close>Đóng</button>
    </div>
    <input id="mediaPickerQ" class="sn-input mt-2" placeholder="Tìm">
    <div id="mediaPickerList" class="mt-2"></div>
  </div>
@endsection

@section('scripts')
  @include('admin.partials.tinymce')
  <script src="{{ asset('js/admin-media-picker.js') }}"></script>
@endsection
