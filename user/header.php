<?php
// 修正点: ログイン状態を判別するため、ファイルの先頭でセッションを開始します
session_start(); 
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar" role="navigation" aria-label="main navigation">
    <div class="container">
        
        <div class="navbar-brand">
            <a class="navbar-item" href="G1-top.html">
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
                
                <a class="navbar-item" href="cart.php" aria-label="カート">
                    <span class="icon-text">
                        <span class="icon is-medium">
                            <i class="fas fa-shopping-cart"></i>
                        </span>
                        <span class="tag is-danger is-rounded" style="position:relative; top: -10px; left: -10px; font-size: 0.75rem; height: 1.5em; width: 1.5em; padding: 0;">1</span>
                    </span>
                </a>

                <a class="navbar-item" 
                   href="<?php 
                            // ログイン状態(セッションに 'user_id' があるか)でリンク先を切り替え
                            if (isset($_SESSION['user_id'])) {
                                echo 'L4-mypage.php'; // ログイン済み
                            } else {
                                echo 'L1-login.php'; // 未ログイン
                            }
                         ?>" 
                   aria-label="会員ページ">
                    <span class="icon is-medium">
                        <i class="fas fa-user"></i> </span>
                </a>
                </div>
        </div>
    </div>
</nav>

<hr style="margin-top: 0;">

<script>
// モバイル用メニューのJS (変更なし)
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