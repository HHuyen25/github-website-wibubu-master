window.products = [
  { title: "Balo gấu Trắng", price: "320.000đ", img: "images/pro-balo.jpg", discount: 70 },
  { title: "Khủng long Spinosaurus", price: "415.000đ", img: "images/pro-spino.jpg", discount: 0 },
  { title: "Play-Doh Fast Bundle", price: "249.000đ", img: "images/pro-playdoh.jpg", discount: 26 },
  { title: "Đàn piano mini Fisher Price", price: "199.000đ", img: "images/pro3.jpg" },
  { title: "Búp bê Barbie mơ ước", price: "349.000đ", img: "images/pro4.jpg" },
  { title: "Đất nặn Play-Doh", price: "115.000đ", img: "images/pro5.jpg" },
  { title: "Robot điều khiển từ xa", price: "520.000đ", img: "images/pro6.jpg" }
];
function showProducts() {
  const list = document.getElementById('product-list');
  if (!list) return;
  list.innerHTML = window.products.map(p => `
    <div class="product-card">
      ${p.discount ? `<div class="product-discount">-${p.discount}%</div>` : ""}
      <img src="${p.img}" class="product-img" alt="${p.title}" onerror="this.src='https://placehold.co/90x90?text=Img'">
      <div class="product-title">${p.title}</div>
      <div class="product-price">${p.price}</div>
      <button class="button" onclick="addToCart('${p.title}')">
        Shop now
        <svg class="cartIcon" viewBox="0 0 576 512"><path d="M0 24C0 10.7 10.7 0 24 0H69.5c22 0 41.5 12.8 50.6 32h411c26.3 0 45.5 25 38.6 50.4l-41 152.3c-8.5 31.4-37 53.3-69.5 53.3H170.7l5.4 28.5c2.2 11.3 12.1 19.5 23.6 19.5H488c13.3 0 24 10.7 24 24s-10.7 24-24 24H199.7c-34.6 0-64.3-24.6-70.7-58.5L77.4 54.5c-.7-3.8-4-6.5-7.9-6.5H24C10.7 48 0 37.3 0 24zM128 464a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm336-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"></path></svg>
      </button>
    </div>
  `).join('');
}
document.addEventListener('DOMContentLoaded', showProducts);
function addToCart(title) {
  alert("Đã thêm sản phẩm: " + title + " vào giỏ hàng!");
}