<!-- Font Awesome 読み込み（header.phpで読み込んでいない場合のみ必要） -->
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">

<!-- 左サイドメニュー -->
<div class="column is-narrow" style="width:220px; background-color:#fff; border-right:1px solid #ccc;">
  <aside class="menu p-4">
    <p class="menu-label">メニュー</p>
    <ul class="menu-list">

      <!-- ホーム -->
      <li><a href="K2-home.php">ホーム</a></li>

      <!-- 顧客管理 -->
      <li>
        <a class="menu-toggle">
          顧客管理
          <span class="icon is-small"><i class="fas fa-chevron-down"></i></span>
        </a>
        <ul class="submenu" style="display:none;">
          <li><a href="customer_management.php">顧客マスター</a></li>
        </ul>
      </li>

      <!-- 注文管理 -->
      <li>
        <a class="menu-toggle">
          注文管理
          <span class="icon is-small"><i class="fas fa-chevron-down"></i></span>
        </a>
        <ul class="submenu" style="display:none;">
          <li><a href="K5-order_master.php">注文マスター</a></li>
        </ul>
      </li>

      <!-- 商品管理 -->
      <li>
        <a class="menu-toggle">
          商品管理
          <span class="icon is-small"><i class="fas fa-chevron-down"></i></span>
        </a>
        <ul class="submenu" style="display:none;">
          <li><a href="K7-product_master.php">商品マスター</a></li>
          <li><a href="K9-product_register.php">商品登録</a></li>
        </ul>
      </li>

    </ul>
  </aside>
</div>

<!-- メニュー開閉スクリプト -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const toggles = document.querySelectorAll('.menu-toggle');
  toggles.forEach(toggle => {
    toggle.addEventListener('click', () => {
      const submenu = toggle.nextElementSibling;
      const icon = toggle.querySelector('.icon i');
      if (submenu) {
        const isOpen = submenu.style.display === 'block';
        submenu.style.display = isOpen ? 'none' : 'block';
        // アイコンの向きを切り替える
        icon.classList.toggle('fa-chevron-down', isOpen);
        icon.classList.toggle('fa-chevron-up', !isOpen);
      }
    });
  });
});
</script>

<!-- デザイン調整 -->
<style>
.menu-list li a.menu-toggle {
  cursor: pointer;
  font-weight: 600;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.submenu {
  margin-left: 1rem;
  border-left: 3px solid #3273dc;
  padding-left: 0.5rem;
}

.submenu li a {
  color: #363636;
}

.submenu li a:hover {
  color: #3273dc;
}
</style>
