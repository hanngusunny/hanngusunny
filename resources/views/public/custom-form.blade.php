@extends('public.layout')
@section('content')
  <h1>{{ $form->name }}</h1>
  @if (session('form_ok')) <p>{{ session('form_ok') }}</p> @endif
  <form method="post" action="{{ route('public.form.submit', $form->slug) }}">
    @csrf
    @foreach ($fields as $field)
      <label>{{ $field->label }}</label>
      @if ($field->field_type === 'textarea')
        <textarea class="tw-field" name="{{ $field->field_key }}" {{ $field->is_required ? 'required' : '' }}></textarea>
      @elseif ($field->field_type === 'select')
        <select class="tw-field" name="{{ $field->field_key }}">
          @foreach ($field->optionList() as $opt) <option>{{ $opt }}</option> @endforeach
        </select>
      @elseif ($field->field_type === 'checkbox')
        <input type="hidden" name="{{ $field->field_key }}" value="0">
        <input type="checkbox" name="{{ $field->field_key }}" value="1">
      @else
        <input class="tw-field" type="{{ $field->field_type === 'datetime-local' ? 'datetime-local' : $field->field_type }}" name="{{ $field->field_key }}" {{ $field->is_required ? 'required' : '' }}>
      @endif
    @endforeach
    <button class="btn btn-primary mt-3" type="submit">Gửi</button>
  </form>
@endsection
