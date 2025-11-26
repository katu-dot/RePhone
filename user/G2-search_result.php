<?php
session_start();

// 1. 共通ファイルの読み込み
require './header.php'; 
require '../config/db-connect.php'; 

// 2. DB接続
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "<div class='notification is-danger'>データベース接続エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit();
}

// 3. 検索条件取得
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$order = isset($_GET['order']) ? $_GET['order'] : 'default';
$out_of_stock = isset($_GET['out_of_stock']) ? boolval($_GET['out_of_stock']) : false;

// 4. カテゴリ一覧取得
$categories = $pdo->query("SELECT category_id, category_name FROM category ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// 5. 商品検索
$products = [];

try {
    $sql = "
        SELECT 
            p.product_id, 
            p.product_name, 
            p.price, 
            p.image, 
            PM.stock,
            S.status_name, 
            SH.shipping_date
        FROM product p
        INNER JOIN product_management PM ON p.product_id = PM.product_id
        INNER JOIN status S ON PM.status_id = S.status_id
        LEFT JOIN shipping SH ON PM.shipping_id = SH.shipping_id
        WHERE 1=1
    ";

    $params = [];

    // 検索ワード
    if ($search_query !== '') {
        $sql .= " AND p.product_name LIKE :search_query";
        $params[':search_query'] = "%$search_query%";
    }

    // カテゴリ
    if ($category !== 'all') {
        $sql .= " AND p.category_id = :category_id";
        $params[':category_id'] = $category;
    }

    // 在庫チェック
    if ($out_of_stock) {
        $sql .= " AND PM.stock > 0";
    }

    // 並び替え
    switch ($order) {
        case 'price_asc': $sql .= " ORDER BY p.price ASC"; break;
        case 'price_desc': $sql .= " ORDER BY p.price DESC"; break;
        case 'status_asc': $sql .= " ORDER BY S.status_name ASC"; break;
        case 'status_desc': $sql .= " ORDER BY S.status_name DESC"; break;
        case 'stock_asc': $sql .= " ORDER BY PM.stock ASC"; break;
        case 'stock_desc': $sql .= " ORDER BY PM.stock DESC"; break;
        case 'shipping_asc': $sql .= " ORDER BY SH.shipping_date ASC"; break;
        case 'shipping_desc': $sql .= " ORDER BY SH.shipping_date DESC"; break;
        default: $sql .= " ORDER BY p.product_id DESC"; break;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div class='notification is-danger'>データベースクエリエラー: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<section class="section">
    <div class="container">

        <!-- 検索フォーム + カテゴリ + 並び替え -->
        <form method="GET" class="mb-5">
            <div class="field is-grouped is-grouped-multiline">
                
                <!-- ラベル -->
                <div class="control">
                    <label class="label" style="margin-top: 8px;">商品検索：</label>
                </div>

                <!-- 検索ワード -->
                <div class="control is-expanded">
                    <input class="input" type="text" name="q" placeholder="ワード検索" value="<?= htmlspecialchars($search_query) ?>">
                </div>

                <!-- 検索ボタン -->
                <div class="control">
                    <button type="submit" class="button is-info">検索</button>
                </div>

                <!-- クリア -->
                <?php if ($search_query !== '' || $category !== 'all' || $order !== 'default' || $out_of_stock): ?>
                    <div class="control">
                        <a href="G2-search_result.php" class="button is-light">検索結果をクリア</a>
                    </div>
                <?php endif; ?>

                <!-- カテゴリ選択 -->
                <div class="control">
                    <div class="select">
                        <select name="category" onchange="this.form.submit()">
                            <option value="all" <?= $category === 'all' ? 'selected' : '' ?>>すべての商品</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= $category == $cat['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- 並び替え -->
                <div class="control">
                    <div class="select">
                        <select name="order" onchange="this.form.submit()">
                            <option value="default" <?= $order === 'default' ? 'selected' : '' ?>>並び替え：デフォルト</option>
                            <option value="price_asc" <?= $order === 'price_asc' ? 'selected' : '' ?>>価格：安い順</option>
                            <option value="price_desc" <?= $order === 'price_desc' ? 'selected' : '' ?>>価格：高い順</option>
                            <option value="status_asc" <?= $order === 'status_asc' ? 'selected' : '' ?>>ランク：A～C順</option>
                            <option value="status_desc" <?= $order === 'status_desc' ? 'selected' : '' ?>>ランク：C～A順</option>
                            <option value="stock_asc" <?= $order === 'stock_asc' ? 'selected' : '' ?>>在庫数：少ない順</option>
                            <option value="stock_desc" <?= $order === 'stock_desc' ? 'selected' : '' ?>>在庫数：多い順</option>
                            <option value="shipping_asc" <?= $order === 'shipping_asc' ? 'selected' : '' ?>>発送日：早い順</option>
                            <option value="shipping_desc" <?= $order === 'shipping_desc' ? 'selected' : '' ?>>発送日：遅い順</option>
                        </select>
                    </div>
                </div>

            </div>
        </form>

        <!-- 件数表示 -->
        <?php $result_count = count($products); ?>
        <?php if ($out_of_stock && $result_count === 0): ?>
            <div class="notification is-warning">在庫切れの商品はありません。</div>
        <?php elseif ($search_query !== '' && $result_count > 0): ?>
            <h3 class="subtitle is-5 mt-5">検索結果：<?= $result_count ?>件の商品が見つかりました</h3>
        <?php elseif ($search_query !== '' && $result_count === 0): ?>
            <div class="notification is-warning">該当する商品が見つかりませんでした</div>
        <?php endif; ?>

        <!-- 商品表示 -->
        <div class="columns is-multiline is-mobile">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="column is-half-mobile is-half-tablet is-one-quarter-desktop">
                        <div class="card" style="height: 100%;">
                            <a href="G3-product_detail.php?id=<?= htmlspecialchars($product['product_id']) ?>">
                                <div class="card-image">
                                    <figure class="image is-1by1">
                                        <img src="../<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" style="object-fit: contain; padding: 10px;">
                                    </figure>
                                </div>
                                <div class="card-content">
                                    <p class="title is-6" style="min-height: 3em;"><?= htmlspecialchars($product['product_name']) ?></p>
                                    <p class="subtitle is-6 has-text-danger">¥<?= number_format($product['price']) ?></p>

                                    <?php if (isset($product['status_name'])): ?>
                                        <p class="is-size-7 has-text-grey">状態: <?= htmlspecialchars($product['status_name']) ?></p>
                                    <?php endif; ?>

                                    <p class="is-size-7 has-text-grey">在庫数: <?= htmlspecialchars($product['stock']) ?></p>
                                    <p class="is-size-7 has-text-grey">発送日: <?= htmlspecialchars($product['shipping_date'] ?? '―') ?></p>

                                    <?php if ($product['stock'] == 0): ?>
                                        <p class="is-size-7 has-text-danger">※現在在庫切れです<br>　入荷までしばらくお待ちください</p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="column is-full">
                    <div class="notification is-warning">商品はありません。</div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php require './footer.php'; ?>
