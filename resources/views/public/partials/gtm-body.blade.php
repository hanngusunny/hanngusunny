@php
  $gtmId = trim((string) ($gtmId ?? ($site['google_tag_manager_id'] ?? config('site.defaults.google_tag_manager_id', ''))));
@endphp
@if ($gtmId !== '')
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
@endif
