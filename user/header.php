<?php
session_start();

// ▼ ログアウト処理（ヘッダー内で完結）
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    // ユーザー情報だけ削除
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']); // 必要に応じて
    // カートは残す

    // 同じページにリダイレクトして更新
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bulma CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <!-- カスタムCSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- 固定ヘッダー -->
<nav class="navbar is-fixed-top" role="navigation" aria-label="main navigation" style="z-index: 1000; background-color: white;">
    <div class="container">
        
        <div class="navbar-brand">
            <a class="navbar-item" href="G1-top.php">
                <img src="../img/user-logo.jpg" alt="RePhone ロゴ" style="max-height: 35px;">
            </a>

            <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="navbarMenu">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </a>
        </div>

        <div id="navbarMenu" class="navbar-menu">
            <div class="navbar-end">

                <!-- カートアイコン -->
                <a class="navbar-item" href="G4-cart.php" aria-label="カート">
                    <span class="icon-text">
                        <span class="icon is-medium">
                            <i class="fas fa-shopping-cart"></i>
                        </span>
                        <?php 
                            $cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
                            if ($cart_count > 0): 
                        ?>
                            <span class="tag is-danger is-rounded" style="position:relative; top: -10px; left: -10px; font-size: 0.75rem; height: 1.5em; width: 1.5em; padding: 0;">
                                <?= $cart_count; ?>
                            </span>
                        <?php endif; ?>
                    </span>
                </a>

                <!-- 購入履歴アイコン -->
                <a class="navbar-item" 
                   href="<?= isset($_SESSION['user_id']) ? 'G8-purchase_history.php' : 'L1-login.php'; ?>" 
                   aria-label="購入履歴">
                    <span class="icon is-medium">
                        <i class="fas fa-clock-rotate-left"></i>
                    </span>
                </a>

                <!-- 会員ページアイコン -->
                <a class="navbar-item" 
                   href="<?php echo isset($_SESSION['user_id']) ? 'L4-mypage.php' : 'L1-login.php'; ?>" 
                   aria-label="会員ページ">
                    <span class="icon is-medium">
                        <i class="fas fa-user"></i>
                    </span>
                </a>

                <!-- 会員ページ / ログアウト -->
                <a class="navbar-item" 
                   href="<?= isset($_SESSION['user_id']) ? $_SERVER['PHP_SELF'].'?logout=1' : 'L1-login.php'; ?>" 
                   aria-label="会員ページ">
                    <span class="icon is-medium">
                        <i class="fas fa-door-open"></i>
                    </span>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="ml-1">ログアウト</span>
                    <?php endif; ?>
                </a>

            </div>
        </div>
    </div>
</nav>

<!-- コンテンツがヘッダーに隠れないように余白を追加 -->
<div style="margin-top: 3.5rem;"></div>

<hr style="margin-top: 0;">

<script>
// モバイル用メニューのJS
document.addEventListener('DOMContentLoaded', () => {
    const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);
    if ($navbarBurgers.length > 0) {
        $navbarBurgers.forEach( el => {
            el.addEventListener('click', () => {
                const target = el.dataset.target;
                const $target = document.getElementById(target);
                el.classList.toggle('is-active');
                $target.classList.toggle('is-active');
            });
        });
    }
});
</script>

</body>
</html>
