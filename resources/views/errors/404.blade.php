<!DOCTYPE html>
<html lang="vi">
<head>
  @include('public.partials.gtm-head')
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Không tìm thấy trang</title>
</head>
<body>
  @include('public.partials.gtm-body')
  <h1>404</h1>
  <p>Không tìm thấy trang bạn yêu cầu.</p>
  <p><a href="{{ url('/') }}">Về trang chủ</a></p>
</body>
</html>
