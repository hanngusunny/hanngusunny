<?php
/**
 * Khối nội dung — sinh tự động từ Admin > Blocks (CI4).
 * Không sửa tay; lưu lại trong admin sẽ ghi đè file.
 */
defined('FRONTEND_BLOCK') OR define('FRONTEND_BLOCK', true);

return array (
  'id' => 2,
  'slug' => 'gioi-thieu-ve-sunny',
  'name' => 'Giới thiệu về Sunny',
  'block_type' => 'flex',
  'is_active' => 1,
  'sort_order' => 0,
  'updated_at' => '2026-05-03 04:34:36',
  'config' => 
  array (
    '_fields' => 
    array (
    ),
    'html' => '  <!-- Giới thiệu -->
  <section id="gioi-thieu" class="intro-sunny-section py-5 bg-white">
    <span class="intro-sunny-watermark" aria-hidden="true">SUNNY</span>
    <div class="container">
      <div class="intro-sunny-grid">
        <div class="intro-sunny-grid__copy">
          <div class="title-line mb-3"></div>
          <h2 class="section-title mb-3">Trung tâm du học hàn quốc SUNNY</h2>
          <p class="sunny-tagline mb-3">
            <span class="sunny-tagline__part">Minh bạch chi phí</span>
            <span class="sunny-tagline__sep" aria-hidden="true"></span>
            <span class="sunny-tagline__part sunny-tagline__part--accent">An tâm du học</span>
          </p>
          <p class="text-black mb-3">
            SUNNY là trung tâm du học Hàn Quốc được xây dựng với một nguyên tắc cốt lõi: minh bạch về chi phí và thông tin trong mọi lộ trình du học. Trong một thị trường còn nhiều thiếu rõ ràng, SUNNY lựa chọn con đường rõ ràng hơn — tư vấn đúng, nói thật và đồng hành có trách nhiệm đến cùng với học sinh.
          </p>
          <p class="text-black mb-4">
            Chúng tôi tin rằng du học không chỉ là việc &quot;đi được&quot;, mà là một hành trình dài hạn cần định hướng đúng ngay từ đầu. Vì vậy, SUNNY không chạy theo số lượng, mà tập trung giúp mỗi học sinh hiểu rõ năng lực của mình, lựa chọn phù hợp và chuẩn bị đầy đủ cho hành trình phía trước.
          </p>
          <a href="#vi-sao" class="btn btn-sunny btn-sunny-pulse text-white">Xem chi tiết</a>
        </div>
        <div class="intro-sunny-grid__media">
          <div class="intro-section-bg rounded-4 shadow" role="img" aria-label="Trung tâm du học Hàn Quốc SUNNY"></div>
          <div class="intro-ceo-block">
            <p class="intro-ceo-label">Thông tin CEO</p>
            <div class="intro-ceo-line" aria-hidden="true"></div>
            <p class="intro-ceo-name">Trần Thị Hồng Nhung</p>
          </div>
        </div>
      </div>
    </div>
  </section>',
  ),
);
