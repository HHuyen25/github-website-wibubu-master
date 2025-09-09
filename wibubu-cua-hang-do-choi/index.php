<?php
ini_set('display_errors', 0); error_reporting(E_ALL);
session_start();
?><!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>wibubu - Mua sắm vui mỗi ngày</title>
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="description" content="wibubu - Trang thương mại điện tử với trải nghiệm gọn gàng, hiện đại, đa dạng sản phẩm, ưu đãi hấp dẫn.">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="icon" href="assets/img/logo.svg">
</head>
<body>
  <header class="header">
    <div class="container header-inner">
      <a href="#home" class="logo">wibubu</a>
      <nav id="main-nav">
        <a href="#home" data-link>Home</a>
        <a href="#gioi-thieu" data-link>Giới thiệu</a>
        <a href="#san-pham" data-link>Sản phẩm</a>
        <a href="#danh-muc" data-link>Danh mục</a>
        <a href="#khuyen-mai" data-link>Khuyến mãi</a>
        <a href="#gio-hang" data-link>Giỏ hàng <span id="mini-cart-count">0</span></a>
        <a href="#dang-nhap" data-link id="user-auth-link">Đăng nhập/Đăng ký</a>
      </nav>
      <form id="search-form" class="search-box" autocomplete="off">
        <input type="search" name="q" placeholder="Tìm sản phẩm..." id="search-input">
        <div id="search-suggest" class="search-suggest hidden"></div>
      </form>
      <div class="theme-toggle">
        <button id="toggle-theme" aria-label="Đổi chế độ sáng/tối">🌞</button>
        <input id="brightness" type="range" min="0.8" max="1.2" step="0.01" value="1" title="Độ sáng">
      </div>
      <button class="nav-toggle" id="nav-toggle" aria-label="Mở menu">&#9776;</button>
    </div>
  </header>
  <main>
    <!-- Các section như mẫu ở trên -->
    <!-- ... -->
  </main>
  <footer>
    <div class="container">
      <span>&copy; <?=date('Y')?> wibubu. All rights reserved.</span>
    </div>
  </footer>
  <script src="assets/js/components.js" defer></script>
  <script src="assets/js/app.js" defer></script>
</body>
</html>