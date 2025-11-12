<?php
// セッションを開始 (カートやログイン状態で使用)
session_start();

// 1. 共通ファイルの読み込み
require './header.php'; // ユーザー側ヘッダー 
require '../config/db-connect.php'; // DB接続情報 ($connect, USER, PASS)

// 2. データベース接続 ($pdoの作成)
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "<div class'notification is-danger'>データベース接続エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit();
}

// 3. データ取得 (今日のおすすめ商品)
$products = [];
try {
    // P=product, PM=product_management
    $sql = "
        SELECT 
            P.product_id,
            P.product_name,
            P.price,
            P.image
        FROM 
            product P
        INNER JOIN 
            product_management PM ON P.product_id = PM.product_id
        WHERE 
            P.stock > 0 -- 在庫があるもののみ
        ORDER BY 
            P.product_id DESC -- 仮に「新着順」をおすすめとする
        LIMIT 4
    ";
    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // error_log("DB error: " . $e->getMessage());
}

?>

<section class="section">
    <div class="container">

        <form action="G2-search_result.php" method="GET">
            <div class="field">
                <div class="control has-icons-left">
                    <input class="input is-medium is-rounded" type="text" name="q" placeholder="ワード検索">
                    <span class="icon is-left">
                        <i class="fas fa-search"></i>
                    </span>
                </div>
            </div>
        </form>

        <div class="swiper-container block my-5" style="position: relative; margin-left: auto; margin-right: auto;">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <figure class="image">
                        <img src="../img/junk.png" alt="ジャンク商品セール" style="width: 100%; height: auto; object-fit: contain;">
                    </figure>
                </div>
                <div class="swiper-slide">
                    <figure class="image">
                        <img src="../img/timesale.png" alt="タイムセール" style="width: 100%; height: auto; object-fit: contain;">
                    </figure>
                </div>
                <div class="swiper-slide">
                    <figure class="image">
                        <img src="../img/featurePC.png" alt="PC特集" style="width: 100%; height: auto; object-fit: contain;">
                    </figure>
                </div>
            </div>
            
            <div class="swiper-button-prev has-text-primary" style="left: -10px;"></div>
            <div class="swiper-button-next has-text-primary" style="right: -10px;"></div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            new Swiper('.swiper-container', {
                autoplay: {
                    delay: 3000, 
                    disableOnInteraction: false, 
                },
                loop: true,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
            });
        });
        </script>

        <h2 class="title is-4">今日のおすすめ</h2>
        
        <div class="columns is-multiline is-mobile">
            
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="column is-half-mobile is-half-tablet is-one-quarter-desktop">
                        <div class="card" style="height: 100%;">
                            <a href="G3-product_detail.php?id=<?php echo htmlspecialchars($product['product_id']); ?>">
                                <div class="card-image">
                                    <figure class="image is-1by1">
                                        <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" style="object-fit: contain; padding: 10px;">
                                    </figure>
                                </div>
                                <div class="card-content">
                                    <p class="title is-6" style="min-height: 3em;"><?php echo htmlspecialchars($product['product_name']); ?></p>
                                    <p class="subtitle is-6 has-text-danger">¥<?php echo number_format($product['price']); ?></p>
                                </div>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>現在おすすめの商品はありません。</p>
            <?php endif; ?>

        </div> <div class="block has-text-centered my-6">
            <a href="G2-search_result.php" class="button is-medium is-dark is-outlined">
                <span>もっと見る</span>
                <span class="icon">
                    <i class="fas fa-chevron-down"></i>
                </span>
            </a>
        </div>

    </div> </section>

<section class="section has-background-light">
    <div class="container has-text-centered">
        <p class="title is-5">ログインは <a href="L1-login.php">こちら</a></p>
        <p class="title is-5">新規会員登録は <a href="L2-register_input1.php">こちら</a></p>
    </div>
</section>


<?php
// 6. フッターの読み込み
require './footer.php'; 
?>