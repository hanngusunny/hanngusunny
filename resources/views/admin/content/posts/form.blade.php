@extends('admin.layouts.app')

@section('content')
  <h1 class="admin-page-title">{{ $post ? 'Sửa' : 'Thêm' }} — {{ $cfg['label'] }}</h1>
  <form method="post" action="{{ $post ? route('admin.'.$module.'.posts.update', $post) : route('admin.'.$module.'.posts.store') }}">
    @csrf
    <label class="form-label">Tiêu đề</label>
    <input class="form-control" name="title" value="{{ old('title', $post->title ?? '') }}" required>
    <label class="form-label">Slug</label>
    <input class="form-control" name="slug" value="{{ old('slug', $post->slug ?? '') }}">
    <label class="form-label">Chuyên mục</label>
    <select class="form-control" name="category_id">
      <option value="">—</option>
      @foreach ($categories as $c)
        <option value="{{ $c->id }}" @selected((string) old('category_id', $post->category_id ?? '') === (string) $c->id)>{{ $c->name }}</option>
      @endforeach
    </select>
    <label class="form-label">Tags</label>
    <div class="flex flex-wrap gap-2 mb-2">
      @foreach ($tags ?? [] as $t)
        <label class="form-check-label me-2">
          <input type="checkbox" name="tag_ids[]" value="{{ $t->id }}" @checked(in_array((int) $t->id, array_map('intval', (array) old('tag_ids', $selectedTagIds ?? [])), true))>
          {{ $t->name }}
        </label>
      @endforeach
    </div>
    <label class="form-label">Ảnh đại diện</label>
    <div class="flex gap-2">
      <input class="form-control" name="featured_image" id="post_featured" value="{{ old('featured_image', $post->featured_image ?? '') }}">
      <button type="button" class="btn btn-outline-primary" data-media-target="#post_featured">Thư viện</button>
    </div>
    <label class="form-label">OG image</label>
    <div class="flex gap-2">
      <input class="form-control" name="og_image" id="post_og" value="{{ old('og_image', $post->og_image ?? '') }}">
      <button type="button" class="btn btn-outline-primary" data-media-target="#post_og">Thư viện</button>
    </div>
    <label class="form-label">OG title</label>
    <input class="form-control" name="og_title" value="{{ old('og_title', $post->og_title ?? '') }}">
    <label class="form-label">OG description</label>
    <input class="form-control" name="og_description" value="{{ old('og_description', $post->og_description ?? '') }}">
    <label class="form-label">Template bài</label>
    <select class="form-control" name="template_id">
      <option value="">Mặc định Blade</option>
      @foreach ($templates ?? [] as $tpl)
        <option value="{{ $tpl->id }}" @selected((string) old('template_id', $post->template_id ?? '') === (string) $tpl->id)>{{ $tpl->name }}</option>
      @endforeach
    </select>
    <label class="form-label">SEO title</label>
    <input class="form-control" name="meta_title" value="{{ old('meta_title', $post->meta_title ?? '') }}">
    <label class="form-label">SEO description</label>
    <input class="form-control" name="meta_description" value="{{ old('meta_description', $post->meta_description ?? '') }}">
    <label class="form-label">Tóm tắt</label>
    <textarea class="form-control" name="excerpt" rows="3">{{ old('excerpt', $post->excerpt ?? '') }}</textarea>
    <label class="form-label">Nội dung</label>
    <textarea class="form-control js-richtext" name="body" rows="12">{{ old('body', $post->body ?? '') }}</textarea>
    <label class="form-label">Trạng thái</label>
    <select class="form-control" name="status">
      <option value="draft" @selected(old('status', $post->status ?? 'draft') === 'draft')>draft</option>
      <option value="published" @selected(old('status', $post->status ?? '') === 'published')>published</option>
    </select>
    <button class="btn btn-primary mt-3" type="submit">Lưu</button>
  </form>
  <div id="mediaPickerModal" hidden data-json-url="{{ route('admin.media.json') }}" class="border p-3 mt-3">
    <div class="flex justify-between">
      <strong>Chọn ảnh</strong>
      <button type="button" data-media-close>Đóng</button>
    </div>
    <input id="mediaPickerQ" class="form-control mt-2" placeholder="Tìm">
    <div id="mediaPickerList" class="mt-2"></div>
  </div>
@endsection

@section('scripts')
  @include('admin.partials.tinymce')
  <script src="{{ asset('js/admin-media-picker.js') }}"></script>
@endsection
