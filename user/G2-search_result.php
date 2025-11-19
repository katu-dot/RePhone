<?php
// セッションを開始
session_start();

// 1. 共通ファイルの読み込み
require './header.php'; 
require '../config/db-connect.php'; 

// 2. データベース接続
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "<div class='notification is-danger'>データベース接続エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit();
}

// 3. 検索ワードの取得
$search_query = isset($_GET['q']) ? trim(htmlspecialchars($_GET['q'])) : '';
$like_query = '%' . $search_query . '%';

$products = [];
$total_count = 0;

try {
    // --- (1) 総件数を取得 ---
    $sql_count = "
        SELECT COUNT(p.product_id) AS total 
        FROM product p 
        INNER JOIN product_management PM ON p.product_id = PM.product_id
        WHERE p.stock > 0 
    ";
    
    if ($search_query !== '') {
        $sql_count .= " AND p.product_name LIKE :search_query";
    }

    $stmt_count = $pdo->prepare($sql_count);
    
    if ($search_query !== '') {
        $stmt_count->bindValue(':search_query', $like_query, PDO::PARAM_STR);
    }
    $stmt_count->execute();
    $result = $stmt_count->fetch(PDO::FETCH_ASSOC);
    $total_count = $result["total"];

    
    // --- (2) メインの商品データを取得 ---
    // S=status テーブルも結合して状態名を取得
    $sql_main = "
        SELECT 
            p.product_id, p.product_name, p.price, p.image,
            PM.accessories_name, S.status_name
        FROM product p 
        INNER JOIN product_management PM ON p.product_id = PM.product_id
        INNER JOIN status S ON PM.status_id = S.status_id
        WHERE p.stock > 0 
    ";

    if ($search_query !== '') {
        $sql_main .= " AND p.product_name LIKE :search_query";
    }
    
    $sql_main .= " ORDER BY p.product_id DESC"; // 新着順

    $stmt_main = $pdo->prepare($sql_main);
    
    if ($search_query !== '') {
        $stmt_main->bindValue(':search_query', $like_query, PDO::PARAM_STR);
    }
    
    $stmt_main->execute();
    $products = $stmt_main->fetchAll(PDO::FETCH_ASSOC);


} catch(PDOException $e) {
    echo "データベースクエリ実行エラー: " . htmlspecialchars($e->getMessage());
}
?>

<section class="section">
    <div class="container">

        <form action="G2-search_result.php" method="GET">
            <div class="field has-addons">
                <div class="control is-expanded has-icons-left">
                    <input class="input is-rounded" type="text" name="q" placeholder="ワード検索" value="<?php echo htmlspecialchars($search_query); ?>">
                    <span class="icon is-left"><i class="fas fa-search"></i></span>
                </div>
                <div class="control">
                    <button type="submit" class="button is-dark is-rounded">検索</button>
                </div>
            </div>
        </form>

        <hr>
        
        <h2 class="title is-4">キーワード：<?php echo htmlspecialchars($search_query); ?></h2>
        <h3 class="subtitle is-6">対象商品：<?php echo $total_count; ?> 件</h3>

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
                                    
                                    <?php if (isset($product['accessories_name'])): ?>
                                        <p class="is-size-7 has-text-grey">付属品: <?php echo htmlspecialchars($product['accessories_name']); ?></p>
                                    <?php endif; ?>
                                    <?php if (isset($product['status_name'])): ?>
                                        <p class="is-size-7 has-text-grey">状態: <?php echo htmlspecialchars($product['status_name']); ?></p>
                                    <?php endif; ?>
                                
                                </div>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="column is-full">
                    <div class="notification is-warning">
                        「<?php echo htmlspecialchars($search_query); ?>」に該当する商品はありませんでした。
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php 
require './footer.php'; 
?>