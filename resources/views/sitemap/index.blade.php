{!! '<'.'?xml version="1.0" encoding="UTF-8"?>'."\n" !!}
@if (!empty($xslHref))
{!! '<'.'?xml-stylesheet type="text/xsl" href="'.e($xslHref).'"?>'."\n" !!}
@endif
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:sunny="https://duhocsunny.com/sitemap">
@foreach ($sitemaps as $item)
@continue(empty($item['loc']))
  <sitemap>
    <loc>{{ $item['loc'] }}</loc>
@if (!empty($item['lastmod']))
    <lastmod>{{ $item['lastmod'] }}</lastmod>
@endif
@if (!empty($item['name']))
    <sunny:name>{{ $item['name'] }}</sunny:name>
@endif
@if (isset($item['count']))
    <sunny:count>{{ (int) $item['count'] }}</sunny:count>
@endif
  </sitemap>
@endforeach
</sitemapindex>
