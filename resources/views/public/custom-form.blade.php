@extends('public.layout')
@section('content')
  <h1 class="sn-h1">{{ $form->name }}</h1>
  @if (session('form_ok')) <p class="alert alert-success">{{ session('form_ok') }}</p> @endif
  <form method="post" action="{{ route('public.form.submit', $form->slug) }}">
    @csrf
    @foreach ($fields as $field)
      <div class="sn-form-group">
        <label class="sn-label">{{ $field->label }} @if($field->is_required)<span class="required">*</span>@endif</label>
        @if ($field->field_type === 'textarea')
          <textarea class="sn-textarea" name="{{ $field->field_key }}" {{ $field->is_required ? 'required' : '' }}></textarea>
        @elseif ($field->field_type === 'select')
          <select class="sn-select" name="{{ $field->field_key }}">
            @foreach ($field->optionList() as $opt) <option>{{ $opt }}</option> @endforeach
          </select>
        @elseif ($field->field_type === 'checkbox')
          <div class="form-check">
            <input type="hidden" name="{{ $field->field_key }}" value="0">
            <input class="form-check-input" type="checkbox" name="{{ $field->field_key }}" value="1">
          </div>
        @else
          <input class="sn-input" type="{{ $field->field_type === 'datetime-local' ? 'datetime-local' : $field->field_type }}" name="{{ $field->field_key }}" {{ $field->is_required ? 'required' : '' }}>
        @endif
      </div>
    @endforeach
    <x-sunny.button class="mt-3" type="submit">Gửi</x-sunny.button>
  </form>
@endsection
