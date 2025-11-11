<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar" role="navigation" aria-label="main navigation">
    <div class="container">
        
        <div class="navbar-brand">
            <a class="navbar-item" href="index.php">
                <img src="../img/RePhone_logo.png" alt="RePhone ロゴ" style="max-height: 35px;">
            </a>

            <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="navbarMenu">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </a>
        </div>

        <div id="navbarMenu" class="navbar-menu">
            <div class="navbar-end">
                
                <a class="navbar-item" href="search.php" aria-label="検索">
                    <span class="icon is-medium">
                        <i class="fas fa-search"></i>
                    </span>
                </a>

                <a class="navbar-item" href="cart.php" aria-label="カート">
                    <span class="icon-text">
                        <span class="icon is-medium">
                            <i class="fas fa-shopping-cart"></i>
                        </span>
                        <span class="tag is-danger is-rounded" style="position:relative; top: -10px; left: -10px; font-size: 0.75rem; height: 1.5em; width: 1.5em; padding: 0;">1</span>
                    </span>
                </a>

                <a class="navbar-item" href="menu.php" aria-label="メニュー">
                    <span class="icon is-medium">
                        <i class="fas fa-bars"></i>
                    </span>
                </a>

            </div>
        </div>
    </div>
</nav>

<hr style="margin-top: 0;">

<script>
document.addEventListener('DOMContentLoaded', () => {
    // ハンバーガーボタン(navbar-burger)を取得
    const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);

    if ($navbarBurgers.length > 0) {
        $navbarBurgers.forEach( el => {
            el.addEventListener('click', () => {
                // data-targetの値を取得 (e.g., "navbarMenu")
                const target = el.dataset.target;
                // data-targetのIDを持つ要素を取得
                const $target = document.getElementById(target);

                // ボタンとメニュー本体に 'is-active' クラスを付け外しする
                el.classList.toggle('is-active');
                $target.classList.toggle('is-active');
            });
        });
    }
});
</script>

</body>
</html>