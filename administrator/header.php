<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bulma Header Sample</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
    
    </head>
<body>

    <nav class="navbar is-light" role="navigation" aria-label="main navigation">
        <div class="container">
            <div class="navbar-brand">
                <a class="navbar-item" href="index.html">
                    <img src="../images/RePhone_logo.png" alt="RePhone ロゴ" style="max-height: 35px;">
                </a>

                <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="navbarMenu">
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                </a>
            </div>

            <div id="navbarMenu" class="navbar-menu">
                <div class="navbar-end">
                    <div class="navbar-item">
                        <span class="icon-text">
                            <span class="icon">
                                👤 </span>
                            <span>
                                〇〇〇さん
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <section class="section">
        <div class="container">
            <h1 class="title">
                サイトのメインコンテンツ
            </h1>
            <p class="subtitle">
                Bulmaを使ってヘッダーが正しく表示されました。
            </p>
        </div>
    </section>

</body>
</html>