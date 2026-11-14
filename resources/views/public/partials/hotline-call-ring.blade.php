@php
  $hotlineTel = preg_replace('/\D+/', '', (string) ($publicHotline ?? '')) ?: '0981698292';
  $hotlineLabel = $publicHotline ?: '0981 698 292';
@endphp
<div class="hotline-call-ring-wrap">
  <a id="hotline-call-ring-link" href="tel:{{ $hotlineTel }}" class="hotline-call-ring" aria-label="Gọi hotline {{ $hotlineLabel }}" data-hotline-click>
    <span class="hotline-call-ring__waves" aria-hidden="true">
      <span class="hotline-call-ring__wave"></span>
      <span class="hotline-call-ring__wave hotline-call-ring__wave--2"></span>
      <span class="hotline-call-ring__wave hotline-call-ring__wave--3"></span>
    </span>
    <span class="hotline-call-ring__core" aria-hidden="true">
      <i class="bi bi-telephone-fill hotline-call-ring__icon" aria-hidden="true"></i>
    </span>
  </a>
</div>
