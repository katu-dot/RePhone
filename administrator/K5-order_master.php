<?php
// 作者：勝原優太郎
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require '../config/db-connect.php';
require 'header.php';

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo '<div class="notification is-danger">DB接続エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}

// --- ▼ 検索処理 ▼ ---
$search_query = $_GET['q'] ?? '';
$category    = $_GET['category'] ?? 'all';
$order       = $_GET['order'] ?? 'default';

$like_query = '%' . $search_query . '%';

try {
    // SQLベース
    $sql = "
        SELECT 
            OM.*, 
            CM.name AS customer_name,
            P.product_name, 
            P.price,
            PM.accessories,
            S.status_name,
            PM.category_id
        FROM 
            order_management OM
        INNER JOIN 
            customer_management CM ON OM.customer_management_id = CM.customer_management_id
        LEFT JOIN
            order_detail_management ODM ON CAST(OM.order_management_id AS CHAR) = ODM.order_management_id
        LEFT JOIN
            product_management PM ON ODM.product_management_id = CAST(PM.product_management_id AS CHAR)
        LEFT JOIN
            product P ON PM.product_id = P.product_id
        LEFT JOIN
            status S ON PM.status_id = S.status_id
        WHERE 
            (CM.name LIKE :q_name OR 
            P.product_name LIKE :q_product OR  
            CAST(OM.order_management_id AS CHAR) LIKE :q_orderid)
    ";

    $params = [
        ':q_name'    => $like_query,
        ':q_product' => $like_query,
        ':q_orderid' => $like_query
    ];

    // 状態(カテゴリ)絞り込み
    if ($category !== 'all') {
        switch ($category) {
            case 'pc':
                $sql .= " AND PM.category_id = 2"; // パソコン
                break;
            case 'smartphone':
                $sql .= " AND PM.category_id = 1"; // スマートフォン
                break;
            case 'paid':
                $sql .= " AND OM.payment_confirmation = '入金済み'";
                break;
            case 'pending':
                $sql .= " AND OM.payment_confirmation != '入金済み'";
                break;
        }
    }

    // GROUP BY
    $sql .= " GROUP BY OM.order_management_id";

    // 並び替え
    switch ($order) {
        case 'price_asc':
            $sql .= " ORDER BY P.price ASC";
            break;
        case 'price_desc':
            $sql .= " ORDER BY P.price DESC";
            break;
        case 'rank_asc':
            $sql .= " ORDER BY PM.status_id ASC";
            break;
        case 'rank_desc':
            $sql .= " ORDER BY PM.status_id DESC";
            break;
        default:
            $sql .= " ORDER BY OM.order_date DESC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result_count = count($orders);
} catch (PDOException $e) {
    echo "<div class='notification is-danger'>SQLエラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
?>

<div class="columns">
  <!-- 左メニュー -->
  <?php require '../config/left-menu.php'; ?>

  <!-- メイン -->
  <div class="column" style="padding: 2rem;">
    <h1 class="title is-4">注文管理／注文マスター</h1>
    <h2 class="subtitle is-6 mb-4">注文一覧</h2>

    <!-- 検索フォーム -->
    <form method="GET" class="mb-5">
      <div class="field is-grouped is-grouped-multiline">

        <div class="control">
          <label class="label" style="margin-top: 8px;">注文検索：</label>
        </div>

        <div class="control is-expanded">
          <input 
            class="input" 
            type="text" 
            name="q" 
            placeholder="ワード検索" 
            value="<?= htmlspecialchars($search_query) ?>">
        </div>

        <div class="control">
          <button type="submit" class="button is-info">検索</button>
        </div>

        <?php if ($search_query !== '' || $category !== 'all' || $order !== 'default'): ?>
          <div class="control">
            <a href="K5-order_master.php" class="button is-light">検索結果をクリア</a>
          </div>
        <?php endif; ?>

        <!-- 状態(カテゴリ) -->
        <div class="control">
        <div class="select">
            <select name="category" onchange="this.form.submit()">
            <option value="all" <?= $category === 'all' ? 'selected' : '' ?>>すべての状態</option>
            <option value="smartphone" <?= $category === 'smartphone' ? 'selected' : '' ?>>スマートフォン</option>
            <option value="pc" <?= $category === 'pc' ? 'selected' : '' ?>>パソコン</option>
            <option value="paid" <?= $category === 'paid' ? 'selected' : '' ?>>入金済み</option>
            <option value="pending" <?= $category === 'pending' ? 'selected' : '' ?>>入金待ち</option>
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
            <option value="rank_asc" <?= $order === 'rank_asc' ? 'selected' : '' ?>>ランク：A～D</option>
            <option value="rank_desc" <?= $order === 'rank_desc' ? 'selected' : '' ?>>ランク：D～A</option>
            </select>
        </div>
        </div>
       </div>
    </form>

    <!-- 件数表示 -->
    <?php if ($search_query !== '' && $result_count > 0): ?>
        <h3 class="subtitle is-5 mt-5">検索結果：<?= $result_count ?>件の注文が見つかりました</h3>
    <?php elseif ($search_query !== '' && $result_count === 0): ?>
        <div class="notification is-warning">該当する注文は見つかりませんでした。</div>
    <?php endif; ?>

    <!-- 注文カード一覧 -->
    <div class="columns is-multiline">
      <?php if (!empty($orders)): ?>
        <?php foreach ($orders as $order): ?>
          <div class="column is-one-third">
            <div class="card">
              <div class="card-content">
                <a href="K6-order_detail.php?id=<?= htmlspecialchars($order['order_management_id']); ?>">
                  <p class="title is-6"><?= htmlspecialchars($order['product_name'] ?? '商品情報なし'); ?></p>
                  <p class="subtitle is-7 has-text-danger">¥<?= number_format($order['price'] ?? 0); ?> 円</p>
                  <p>付属品：<?= htmlspecialchars($order['accessories'] ?? '―'); ?></p>
                  <p>状態：<?= htmlspecialchars($order['status_name'] ?? '―'); ?></p>
                  <hr style="margin: 10px 0;">
                  <p>入金状況：<span class="<?= ($order['payment_confirmation'] === '入金済み') ? 'has-text-success' : 'has-text-danger'; ?>">
                      <?= htmlspecialchars($order['payment_confirmation']); ?></span></p>
                  <p>氏名：<?= htmlspecialchars($order['customer_name'] ?? '不明'); ?> 様</p>
                  <p>注文日：<?= date('Y/m/d', strtotime($order['order_date'])); ?></p>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="notification is-warning">現在、登録されている注文情報はありません。</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require 'footer.php'; ?>
