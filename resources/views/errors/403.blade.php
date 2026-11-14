<!DOCTYPE html>
<html lang="vi">
<head>
  @include('public.partials.gtm-head')
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Không có quyền</title>
</head>
<body>
  @include('public.partials.gtm-body')
  <h1>403</h1>
  <p>Bạn không có quyền truy cập trang này.</p>
  <p><a href="{{ url('/') }}">Về trang chủ</a></p>
</body>
</html>
