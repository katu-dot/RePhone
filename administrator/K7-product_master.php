<?php
// 作者：勝原優太郎
require '../config/db-connect.php';
require 'header.php';

try {
    // DB接続
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 検索パラメータ取得
    $search_query = $_GET['q'] ?? '';
    $category = $_GET['category'] ?? 'all';
    $order = $_GET['order'] ?? 'default';

    // --- カテゴリ一覧取得 ---
    $catStmt = $pdo->query("SELECT category_id, category_name FROM category_management");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

    // ベースSQL（shipping_date も取得）
    $sql = "
        SELECT 
            pm.product_management_id,
            pm.admin_id,
            pm.product_id,
            pm.status_id,
            pm.accessories,
            pm.stock,
            pm.category_id,
            p.product_name,
            p.product_description,
            p.price,
            p.image,
            s.status_name,
            sh.shipping_date
        FROM product_management pm
        INNER JOIN product p ON pm.product_id = p.product_id
        INNER JOIN status s ON pm.status_id = s.status_id
        LEFT JOIN shipping sh ON p.shipping_id = sh.shipping_id
    ";

    $conditions = [];
    $params = [];

    // 検索条件
    if ($search_query !== '') {
        $conditions[] = "(p.product_name LIKE :keyword OR p.product_description LIKE :keyword)";
        $params[':keyword'] = '%' . $search_query . '%';
    }

    // カテゴリ条件
    if ($category !== 'all') {
        $conditions[] = "pm.category_id = :category";
        $params[':category'] = $category;
    }

    // WHERE句
    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    // 並び替え
    switch ($order) {
        case 'price_asc': $sql .= " ORDER BY p.price ASC"; break;
        case 'price_desc': $sql .= " ORDER BY p.price DESC"; break;
        case 'status_asc': $sql .= " ORDER BY pm.status_id ASC"; break;
        case 'status_desc': $sql .= " ORDER BY pm.status_id DESC"; break;
        case 'stock_asc': $sql .= " ORDER BY pm.stock ASC"; break;
        case 'stock_desc': $sql .= " ORDER BY pm.stock DESC"; break;
        case 'shipping_asc': $sql .= " ORDER BY sh.shipping_id ASC"; break;
        case 'shipping_desc': $sql .= " ORDER BY sh.shipping_id DESC"; break;
        case 'old': $sql .= " ORDER BY pm.product_management_id ASC"; break;
        case 'new':
        default: $sql .= " ORDER BY pm.product_management_id DESC"; break;
    }

    // 実行
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo '<div class="notification is-danger">DB接続エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}
?>

<!-- 削除完了メッセージ -->
<?php if (!empty($_GET['message'])): ?>
  <div class="notification is-info">
    <?= htmlspecialchars($_GET['message']); ?>
  </div>
<?php endif; ?>

<div class="columns">
  <!-- 左メニュー -->
  <?php require '../config/left-menu.php'; ?>

  <div class="column" style="padding: 2rem;">
    <h1 class="title is-4">商品管理／商品マスター</h1>
    <h2 class="subtitle is-6 mb-4">商品一覧</h2>

    <!-- 検索フォーム -->
    <form method="GET" class="mb-5">
      <div class="field is-grouped is-grouped-multiline">
        <div class="control">
          <label class="label" style="margin-top: 8px;">商品検索：</label>
        </div>

        <div class="control is-expanded">
          <input class="input" type="text" name="q" placeholder="ワード検索" value="<?= htmlspecialchars($search_query) ?>">
        </div>

        <div class="control">
          <button type="submit" class="button is-info">検索</button>
        </div>

        <?php if ($search_query !== '' || $category !== 'all' || $order !== 'default'): ?>
          <div class="control">
            <a href="K7-product_master.php" class="button is-light">検索結果をクリア</a>
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
              <option value="stock_desc" <?= $order === 'stock_desc' ? 'selected' : '' ?>>在庫数：多い順</option>
              <option value="stock_asc" <?= $order === 'stock_asc' ? 'selected' : '' ?>>在庫数：少ない順</option>
              <option value="shipping_asc" <?= $order === 'shipping_asc' ? 'selected' : '' ?>>発送日：早い順</option>
              <option value="shipping_desc" <?= $order === 'shipping_desc' ? 'selected' : '' ?>>発送日：遅い順</option>
            </select>
          </div>
        </div>
      </div>
    </form>

    <?php $result_count = count($products); ?>
    <?php if ($search_query !== '' && $result_count > 0): ?>
        <h3 class="subtitle is-5 mt-5">検索結果：<?= $result_count ?>件の商品が見つかりました</h3>
    <?php elseif ($search_query !== '' && $result_count === 0): ?>
        <div class="notification is-warning">該当する商品が見つかりませんでした。</div>
    <?php endif; ?>

    <!-- 商品カード一覧 -->
    <div class="columns is-multiline">
      <?php if (!empty($products)): ?>
        <?php foreach ($products as $p): ?>
          <div class="column is-one-third">
            <a href="K8-product_detail.php?id=<?= htmlspecialchars($p['product_management_id']); ?>" style="text-decoration:none; color:inherit;">
              <div class="card">
                <div class="card-content">
                  <div class="columns is-vcentered">
                    <div class="column is-two-thirds">
                      <p class="title is-6"><?= htmlspecialchars($p['product_name']); ?></p>
                      <p class="subtitle is-7 has-text-danger">¥<?= number_format($p['price']); ?> 円</p>
                      <p class="subtitle is-7">商品番号：<strong><?= htmlspecialchars($p['product_id']); ?></strong></p>
                      <p>付属品：<?= htmlspecialchars($p['accessories'] ?: '―'); ?></p>
                      <p>カテゴリ：
                        <?php
                        $cat_name = '―';
                        foreach ($categories as $c) {
                            if ($c['category_id'] == $p['category_id']) {
                                $cat_name = $c['category_name'];
                                break;
                            }
                        }
                        echo htmlspecialchars($cat_name);
                        ?>
                      </p>
                      <p>ランク：<?= htmlspecialchars($p['status_name'] ?: '―'); ?></p>
                      <p>在庫数：<strong><?= htmlspecialchars($p['stock']); ?>個</strong></p>
                      <p>発送日：<?= htmlspecialchars($p['shipping_date'] ?? '―'); ?></p>
                    </div>
                    <div class="column is-one-third">
                      <figure class="image is-4by3">
                        <?php
                        $imageFilename = ltrim($p['image'] ?? '', '/'); 
                        $imageBaseUrl = '../'; 
                        $imagePath = $imageBaseUrl . htmlspecialchars($imageFilename);
                        if (!empty($p['image']) && file_exists($imagePath)) {
                            echo '<img src="' . $imagePath . '" alt="' . htmlspecialchars($p['product_name']) . '">';
                        } else {
                            echo '<img src="../img/noimage.png" alt="画像なし">';
                        }
                        ?>
                      </figure>
                    </div>
                  </div>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="notification is-warning">該当する商品が見つかりません。</div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php require 'footer.php'; ?>
