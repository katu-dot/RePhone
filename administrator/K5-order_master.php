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
$category     = $_GET['category'] ?? 'all';
$order        = $_GET['order'] ?? 'default';
$filter       = $_GET['filter'] ?? '';

$like_query = '%' . $search_query . '%';

try {
    // SQLベース
    $sql = "
        SELECT 
            OM.*, 
            CM.name AS customer_name,
            CM.email AS customer_email,
            P.product_name, 
            P.price,
            P.product_id,
            PM.accessories,
            S.status_name,
            PM.category_id,
            OM.cancelled_at,
            OM.email_sent
        FROM 
            order_management OM
        INNER JOIN 
            customer_management CM ON OM.customer_management_id = CM.customer_management_id
        LEFT JOIN
            order_detail_management ODM ON OM.order_management_id = ODM.order_management_id
        LEFT JOIN
            product_management PM ON ODM.product_management_id = PM.product_management_id
        LEFT JOIN
            product P ON PM.product_id = P.product_id
        LEFT JOIN
            status S ON PM.status_id = S.status_id
        WHERE 
            (CM.name LIKE :q_name OR 
             P.product_name LIKE :q_product OR  
             OM.order_management_id LIKE :q_orderid)
    ";

    $params = [
        ':q_name'    => $like_query,
        ':q_product' => $like_query,
        ':q_orderid' => $like_query
    ];

    // ▼ カテゴリ絞り込み（メール送信状況もここに含める）
    if ($category !== 'all') {
      switch ($category) {
          case 'pc':
              $sql .= " AND PM.category_id = 2";
              break;
          case 'smartphone':
              $sql .= " AND PM.category_id = 1";
              break;
          case 'paid':
              $sql .= " AND OM.payment_confirmation = '入金済み'";
              break;
          case 'pending':
              $sql .= " AND OM.payment_confirmation = '未入金'";
              break;
          case 'email_sent':
              $sql .= " AND OM.email_sent = 1 
                        AND OM.cancelled_at IS NULL";
              break;
          case 'email_pending':
              $sql .= " AND OM.email_sent = 0
                        AND OM.cancelled_at IS NULL";
              break;
            
      }
  }

  // ▼ キャンセル済みのみ表示
  if ($filter === 'cancelled') {
      $sql .= " AND OM.cancelled_at IS NOT NULL ";
  } else {
      // 通常はキャンセル済み除外
      $sql .= " AND OM.cancelled_at IS NULL ";
  }

  // ▼ 7年以上前のキャンセル注文は非表示
  // ※ 上で cancelled_at IS NULL としたので安全に発動
  $sql .= " AND (OM.cancelled_at IS NULL OR OM.cancelled_at > DATE_SUB(NOW(), INTERVAL 7 YEAR)) ";

  // ▼ GROUP BY
  $sql .= " GROUP BY OM.order_management_id";

    // ▼ 並び替え
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

    // 総注文数
    $total_orders = $pdo->query("SELECT COUNT(*) FROM order_management")->fetchColumn();

} catch (PDOException $e) {
    echo "<div class='notification is-danger'>SQLエラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
?>

<div class="columns">
  <?php require '../config/left-menu.php'; ?>

  <div class="column" style="padding: 2rem;">
    <h1 class="title is-4">注文管理／注文マスター</h1>
    <h2 class="subtitle is-6">注文一覧</h2>

    <h3 class="subtitle is-4 mb-3">総注文数：<?= number_format($total_orders) ?> 件</h3>

    <?php if (isset($_GET['message']) && $_GET['message'] === 'deleted'): ?>
        <div class="notification is-success">注文をキャンセルしました。</div>
    <?php endif; ?>

    <!-- ▼ 検索フォーム -->
    <form method="GET" class="mb-5">
      <div class="field is-grouped is-grouped-multiline">

        <div class="control">
          <label class="label" style="margin-top: 8px;">注文検索：</label>
        </div>

        <div class="control is-expanded">
          <input class="input" type="text" name="q" placeholder="ワード検索"
            value="<?= htmlspecialchars($search_query) ?>">
        </div>

        <div class="control">
          <button type="submit" class="button is-info">検索</button>
        </div>

        <!-- ▼ 検索結果クリアボタン -->
        <?php if ($search_query !== '' || $category !== 'all' || $order !== 'default' || $filter !== ''): ?>
        <div class="control">
          <a href="K5-order_master.php" class="button is-light">検索結果をクリア</a>
        </div>
        <?php endif; ?>

        <!-- カテゴリ -->
        <div class="control">
          <div class="select">
            <select name="category" onchange="this.form.submit()">
              <option value="all" <?= $category === 'all' ? 'selected' : '' ?>>すべての状態</option>
              <option value="smartphone" <?= $category === 'smartphone' ? 'selected' : '' ?>>スマートフォン</option>
              <option value="pc" <?= $category === 'pc' ? 'selected' : '' ?>>パソコン</option>
              <option value="paid" <?= $category === 'paid' ? 'selected' : '' ?>>入金済み</option>
              <option value="pending" <?= $category === 'pending' ? 'selected' : '' ?>>未入金</option>
              <option value="email_sent" <?= $category === 'email_sent' ? 'selected' : '' ?>>メール送信済み</option>
              <option value="email_pending" <?= $category === 'email_pending' ? 'selected' : '' ?>>メール未送信</option>
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

        <!-- キャンセル済み絞り込みボタン -->
        <div class="control">
          <?php
            $query_params = [];
            if ($search_query !== '') $query_params['q'] = $search_query;
            if ($order !== 'default') $query_params['order'] = $order;
            $query_params['filter'] = 'cancelled';
            $cancel_url = 'K5-order_master.php?' . http_build_query($query_params);
          ?>
          <a href="<?= $cancel_url ?>" class="button is-danger is-light">キャンセル済み</a>
        </div>

      </div>
    </form>

    <!-- 件数表示 -->
    <?php if ($filter === 'cancelled' && $result_count === 0): ?>
        <div class="notification is-warning">キャンセル済みの注文がありません。</div>
    <?php elseif ($search_query !== '' && $result_count === 0): ?>
        <div class="notification is-warning">該当する注文は見つかりませんでした。</div>
    <?php elseif ($search_query !== '' && $result_count > 0): ?>
        <h3 class="subtitle is-5 mt-5">検索結果：<?= $result_count ?>件の注文が見つかりました</h3>
    <?php endif; ?>

    <!-- 注文カード一覧 -->
    <div class="columns is-multiline">
      <?php if (!empty($orders)): ?>
        <?php foreach ($orders as $order): ?>
          <div class="column is-one-third">
            <div class="card">
              <div class="card-content">

                <!-- ★ キャンセル済み表示 -->
                <?php if (!empty($order['cancelled_at'])): ?>
                  <p class="has-text-danger" style="font-weight:bold;">キャンセル済み</p>
                <?php endif; ?>

                <a href="K6-order_detail.php?id=<?= htmlspecialchars($order['order_management_id']); ?>">
                  <p class="title is-6"><?= htmlspecialchars($order['product_name'] ?? '商品情報なし'); ?></p>
                  <p class="subtitle is-7 has-text-danger">¥<?= number_format($order['price'] ?? 0); ?> 円</p>
                  <p class="subtitle is-7">商品番号：<strong><?= htmlspecialchars($order['product_id'] ?? '―'); ?></strong></p>
                  <p>付属品：<?= htmlspecialchars($order['accessories'] ?? '―'); ?></p>
                  <p>状態：<?= htmlspecialchars($order['status_name'] ?? '―'); ?></p>
                  <hr style="margin: 10px 0;">
                  <p>入金状況：
                    <span class="<?= ($order['payment_confirmation'] === '入金済み') ? 'has-text-success' : 'has-text-danger'; ?>">
                      <?= htmlspecialchars($order['payment_confirmation']); ?>
                    </span>
                  </p>
                  <p>メール送信状況：
                    <span class="<?= ($order['email_sent'] == 1) ? 'has-text-success' : 'has-text-danger'; ?>">
                      <?= ($order['email_sent'] == 1) ? '送信済み' : '未送信'; ?>
                    </span>
                  </p>
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
