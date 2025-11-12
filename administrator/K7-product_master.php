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

    // ベースSQL（product_management と product と status を結合）
    $sql = "
        SELECT 
            pm.product_management_id,
            pm.admin_id,
            pm.product_id,
            pm.status_id,
            s.status_name,
            pm.accessories,
            pm.stock,
            p.product_name,
            p.product_description,
            p.price
        FROM product_management pm
        INNER JOIN product p ON pm.product_id = p.product_id
        INNER JOIN status s ON pm.status_id = s.status_id
    ";

    $conditions = [];
    $params = [];

    // 検索ワード（商品名 or 説明に部分一致）
    if ($search_query !== '') {
        $conditions[] = "(p.product_name LIKE :keyword OR p.product_description LIKE :keyword)";
        $params[':keyword'] = '%' . $search_query . '%';
    }

    // カテゴリ条件（status_id）
    if ($category !== 'all') {
        $conditions[] = "pm.category_id = :category";
        $params[':category'] = $category;
    }

    // WHERE句の結合
    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    // 並び替え
    switch ($order) {
        case 'price_asc':
            $sql .= " ORDER BY p.price ASC";
            break;
        case 'price_desc':
            $sql .= " ORDER BY p.price DESC";
            break;
        case 'status_asc':
            $sql .= " ORDER BY pm.status_id ASC";
            break;
        case 'status_desc':
            $sql .= " ORDER BY pm.status_id DESC";
            break;
        case 'stock_asc':
            $sql .= " ORDER BY pm.stock ASC";
            break;
        case 'stock_desc':
            $sql .= " ORDER BY pm.stock DESC";
            break;
        case 'old':
            $sql .= " ORDER BY pm.product_management_id ASC";
            break;
        case 'new':
        default:
            $sql .= " ORDER BY pm.product_management_id DESC";
            break;
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

<div class="columns">
  <!-- 左メニュー -->
  <?php require '../config/left-menu.php'; ?>

  <!-- メイン -->
  <div class="column" style="padding: 2rem;">
    <h1 class="title is-4">商品管理／商品マスター</h1>
    <h2 class="subtitle is-6 mb-4">商品一覧</h2>

    <!-- 検索フォーム -->
    <form method="GET" class="mb-5">
      <div class="field is-grouped is-grouped-multiline">

        <!-- ラベル -->
        <div class="control">
          <label class="label" style="margin-top: 8px;">商品検索：</label>
        </div>

        <!-- ワード検索 -->
        <div class="control is-expanded">
          <input 
            class="input" 
            type="text" 
            name="q" 
            placeholder="ワード検索" 
            value="<?= htmlspecialchars($search_query) ?>">
        </div>

        <!-- 検索ボタン -->
        <div class="control">
          <button type="submit" class="button is-info">検索</button>
        </div>

        <!-- 検索結果クリア -->
        <?php if ($search_query !== '' || $category !== 'all' || $order !== 'default'): ?>
          <div class="control">
            <a href="K7-product_master.php" class="button is-light">検索結果をクリア</a>
          </div>
        <?php endif; ?>

        <!-- カテゴリ選択（status_nameを反映） -->
        <div class="control">
          <div class="select">
            <select name="category" onchange="this.form.submit()">
              <option value="all" <?= $category === 'all' ? 'selected' : '' ?>>すべての商品</option>
              <option value="1" <?= $category === '1' ? 'selected' : '' ?>>スマートフォン</option>
              <option value="2" <?= $category === '2' ? 'selected' : '' ?>>パソコン</option>
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
              <option value="status_asc" <?= $order === 'status_asc' ? 'selected' : '' ?>>ランク：A～D順</option>
              <option value="status_desc" <?= $order === 'status_desc' ? 'selected' : '' ?>>ランク：D～A順</option>
              <option value="stock_desc" <?= $order === 'stock_desc' ? 'selected' : '' ?>>在庫数：多い順</option>
              <option value="stock_asc" <?= $order === 'stock_asc' ? 'selected' : '' ?>>在庫数：少ない順</option>
            </select>
          </div>
        </div>
      </div>
    </form>

<?php 
    $result_count = count($products); 
?>

<!-- 件数表示 -->
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
            <div class="card">
              <div class="card-content">
                <a href="K8-product_detail.php?id=<?= htmlspecialchars($p['product_management_id']); ?>">
                  <p class="title is-6"><?= htmlspecialchars($p['product_name']); ?></p>
                  <p class="subtitle is-7 has-text-grey">¥<?= number_format($p['price']); ?> 円</p>
                  <p>付属品：<?= htmlspecialchars($p['accessories'] ?: '―'); ?></p>
                  <p>カテゴリ：<?= htmlspecialchars($p['status_name']); ?></p>
                  <p>在庫数：<strong><?= htmlspecialchars($p['stock']); ?></strong></p>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="notification is-warning">該当する商品が見つかりません。</div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php require 'footer.php'; ?>
