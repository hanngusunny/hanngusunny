{!! '<'.'?xml version="1.0" encoding="UTF-8"?>'."\n" !!}
@if (!empty($xslHref))
{!! '<'.'?xml-stylesheet type="text/xsl" href="'.e($xslHref).'"?>'."\n" !!}
@endif
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as $u)
@continue(empty($u['loc']))
  <url>
    <loc>{{ $u['loc'] }}</loc>
@if (!empty($u['lastmod']))
    <lastmod>{{ $u['lastmod'] }}</lastmod>
@endif
@if (!empty($u['changefreq']))
    <changefreq>{{ $u['changefreq'] }}</changefreq>
@endif
@if (!empty($u['priority']))
    <priority>{{ $u['priority'] }}</priority>
@endif
  </url>
@endforeach
</urlset>
