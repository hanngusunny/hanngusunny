@extends('layouts.app', ['active' => 'assessments'])

@section('title', 'Danh sách bài kiểm tra')

@section('content')
<div class="op-stack sw-page">
    <x-page-title kicker="Học tập" icon="fa-file-pen" title="Bài kiểm tra của tôi" lead="Danh sách các bài kiểm tra định kỳ, giữa khóa và cuối khóa." />
    @include('student.partials.assessments')
</div>
@endsection
