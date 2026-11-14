<?php
/**
 * Khối nội dung — sinh tự động từ Admin > Blocks (CI4).
 * Không sửa tay; lưu lại trong admin sẽ ghi đè file.
 */
defined('FRONTEND_BLOCK') OR define('FRONTEND_BLOCK', true);

return array (
  'id' => 1,
  'slug' => 'gi-i-thi-u',
  'name' => 'Giới thiệu',
  'block_type' => 'flex',
  'is_active' => 1,
  'sort_order' => 0,
  'updated_at' => '2026-05-11 17:55:51',
  'config' => 
  array (
    '_fields' => 
    array (
      0 => 
      array (
        'key' => 'etayk6t5s6',
        'kind' => 'image',
        'label' => 'Video 1',
        'options' => 
        array (
        ),
      ),
      1 => 
      array (
        'key' => '7085ek3qi5',
        'kind' => 'image',
        'label' => '',
        'options' => 
        array (
        ),
      ),
      2 => 
      array (
        'key' => 'y5j87lrdn2',
        'kind' => 'image',
        'label' => '',
        'options' => 
        array (
        ),
      ),
    ),
    'etayk6t5s6' => 'assets/video/bg1.mp4',
    'etayk6t5s6_alt' => '',
    'etayk6t5s6_title' => '',
    '7085ek3qi5' => 'assets/video/sejong.mp4',
    '7085ek3qi5_alt' => '',
    '7085ek3qi5_title' => '',
    'y5j87lrdn2' => 'assets/video/dai-hoc-kookmin-han-quoc.mp4',
    'y5j87lrdn2_alt' => '',
    'y5j87lrdn2_title' => '',
    'visa98video' => 'public/sunny-ty-le-do-visa-98.mp4',
    'html' => '<div class="hero-video-root" id="slider">
    <div class="swiper hero-video-swiper hero-video-swiper--single" aria-label="Video giới thiệu">
      <div class="swiper-wrapper">
        <div
          class="swiper-slide hero-video-slide"
          data-hero-kicker-sub="Tỷ lệ đỗ visa 98%"
          data-hero-kicker-href="{{site_url:sunny-do-visa-98}}"
        >
          <video
            class="hero-video-bg"
            muted
            playsinline
            loop
            preload="none"
            fetchpriority="high"
          >
            <source src="{{visa98video}}" type="video/mp4" />
          </video>
        </div>
      </div>
    </div>
    <div class="hero-video-overlay" aria-hidden="true"></div>
    <div class="hero-brand-kicker">
      <div class="hero-brand-kicker__inner">
        <p class="hero-brand-kicker__text">Du học Hàn Quốc SUNNY</p>
        <a
          class="hero-brand-kicker__sub hero-brand-kicker__link"
          id="heroBrandKickerLink"
          href="{{site_url:sunny-do-visa-98}}"
          rel="bookmark"
          >Tỷ lệ đỗ visa 98%</a>
      </div>
    </div>
    <div class="hero-video-controls">
      <button
        type="button"
        class="hero-video-toggle"
        id="heroVideoToggle"
        aria-label="Tạm dừng video"
        aria-pressed="true"
      >
        <i class="bi bi-pause-fill" aria-hidden="true"></i>
      </button>
      <button
        type="button"
        class="hero-video-sound"
        id="heroVideoSound"
        aria-label="Bật tiếng video"
        aria-pressed="false"
      >
        <i class="bi bi-volume-mute-fill" aria-hidden="true"></i>
      </button>
    </div>
    <h1 class="sr-only">Trung tâm du học Hàn Quốc SUNNY</h1>
  </div>',
  ),
);
