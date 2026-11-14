<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>API Docs</title>
</head>
<body>
  <h1>API Du học Sunny</h1>
  <ul>
    <li><a href="{{ $docsNews }}">Tin tức</a> — <code>GET {{ $newsLatest }}</code></li>
    <li><a href="{{ $docsSchools }}">Trường</a> — <code>GET {{ $schoolsList }}</code>, <code>GET {{ $schoolsPosts }}</code></li>
  </ul>
</body>
</html>
