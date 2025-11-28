<?php
// 作者：勝原優太郎
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require '../config/db-connect.php';
require 'header.php';

// --- DB接続 ---
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
    $sql = "
        SELECT 
            OM.*,
            CM.name AS customer_name,
            CM.email AS customer_email,
            P.product_name,
            P.price,
            P.product_id,
            A.accessories_name,
            S.status_name,
            PM.category_id,
            OM.cancelled_at,
            OM.cancel_request_status,
            OM.shipping_mail_sent
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
        LEFT JOIN
            accessories A ON PM.accessories_id = A.accessories_id
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

    // ▼ カテゴリ絞り込み
    if ($category !== 'all') {
        switch ($category) {
            case 'pc':
                $sql .= " AND PM.category_id = 2";
                break;
            case 'smartphone':
                $sql .= " AND PM.category_id = 1";
                break;
            case 'paid':
                $sql .= " AND OM.payment_status = '入金済み'";
                break;
            case 'pending':
                $sql .= " AND OM.payment_status = '未入金'";
                break;
            case 'shipping_mail_sent':
                $sql .= " AND OM.shipping_mail_sent = 1"; // 発送完了メール送信済み
                break;
            case 'shipping_mail_pending':
                $sql .= " AND OM.shipping_mail_sent = 0"; // 発送完了メール未送信
                break;
            case 'cancel_request':
                $sql .= " AND OM.cancel_request_status = '申請中'"; // キャンセル申請あり
                break;
        }
    }

    // ▼ キャンセル済みのみ表示
    if ($filter === 'cancelled') {
        $sql .= " AND OM.cancelled_at IS NOT NULL";
    }

    // ▼ GROUP BY 注文ごと
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
            $sql .= " ORDER BY S.status_id ASC";
            break;
        case 'rank_desc':
            $sql .= " ORDER BY S.status_id DESC";
            break;
        default:
            $sql .= " ORDER BY OM.order_date DESC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $raw_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ▼ 注文データ整形
    $orders = [];
    foreach ($raw_orders as $row) {
        $oid = $row['order_management_id'];
        if (!isset($orders[$oid])) {
            $orders[$oid] = $row;
            $orders[$oid]['products'] = [];
        }
        if (!empty($row['product_id'])) {
            $orders[$oid]['products'][] = [
                'name' => $row['product_name'],
                'price' => $row['price'],
                'product_id' => $row['product_id'],
                'accessories' => $row['accessories_name'],
                'status' => $row['status_name'],
                'cancelled' => !empty($row['cancelled_at'])
            ];
        }
    }
    $orders = array_values($orders);
    $result_count = count($orders);

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
    <h3 class="subtitle is-5">総注文数：<?= number_format($total_orders) ?> 件</h3>

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

        <?php if ($search_query !== '' || $category !== 'all' || $order !== 'default' || $filter !== ''): ?>
        <div class="control">
          <a href="K5-order_master.php" class="button is-light">検索結果をクリア</a>
        </div>
        <?php endif; ?>

        <div class="control">
          <div class="select">
            <select name="category" onchange="this.form.submit()">
              <option value="all" <?= $category === 'all' ? 'selected' : '' ?>>すべての状態</option>
              <option value="smartphone" <?= $category === 'smartphone' ? 'selected' : '' ?>>スマートフォン</option>
              <option value="pc" <?= $category === 'pc' ? 'selected' : '' ?>>パソコン</option>
              <option value="paid" <?= $category === 'paid' ? 'selected' : '' ?>>入金済み</option>
              <option value="pending" <?= $category === 'pending' ? 'selected' : '' ?>>未入金</option>
              <option value="shipping_mail_sent" <?= $category === 'shipping_mail_sent' ? 'selected' : '' ?>>発送完了メール送信済み</option>
              <option value="shipping_mail_pending" <?= $category === 'shipping_mail_pending' ? 'selected' : '' ?>>発送完了メール未送信</option>
              <option value="cancel_request" <?= $category === 'cancel_request' ? 'selected' : '' ?>>キャンセル申請あり</option>
            </select>
          </div>
        </div>

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

    <!-- ▼ 注文一覧 -->
    <?php if ($result_count === 0): ?>
        <div class="notification is-warning">該当する注文はありません。</div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="box">

                <?php if (!empty($order['cancelled_at'])): ?>
                    <p class="has-text-danger" style="font-weight:bold;">キャンセル済み</p>
                <?php elseif (!empty($order['cancel_request_status']) && $order['cancel_request_status'] === '申請中'): ?>
                    <p class="has-text-danger" style="font-weight:bold;">※キャンセルの申請が来ています</p>
                <?php endif; ?>

                <p>注文ID：<?= htmlspecialchars($order['order_management_id']); ?></p>
                <p>氏名：<?= htmlspecialchars($order['customer_name']); ?> 様</p>
                <p>入金状況：
                    <span class="<?= ($order['payment_status'] === '入金済み') ? 'has-text-success' : 'has-text-danger'; ?>">
                        <?= htmlspecialchars($order['payment_status']); ?>
                    </span>
                </p>
                <p>発送完了メール送信状況：
                    <span class="<?= ($order['shipping_mail_sent'] == 1) ? 'has-text-success' : 'has-text-danger'; ?>">
                        <?= ($order['shipping_mail_sent'] == 1) ? '送信済み' : '未送信'; ?>
                    </span>
                </p>
                <p>注文日：<?= date('Y/m/d', strtotime($order['order_date'])); ?></p>

                <?php if (!empty($order['products'])): ?>
                    <table class="table is-fullwidth is-striped mt-3">
                        <thead>
                            <tr>
                                <th>商品名</th>
                                <th>価格</th>
                                <th>商品番号</th>
                                <th>付属品</th>
                                <th>状態</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['products'] as $prod): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($prod['name']); ?>
                                        <?php if ($prod['cancelled']): ?>
                                            <br><span class="has-text-danger" style="font-weight:bold;">キャンセル済み</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>¥<?= number_format($prod['price'] ?? 0); ?></td>
                                    <td><?= htmlspecialchars($prod['product_id'] ?? '―'); ?></td>
                                    <td><?= htmlspecialchars($prod['accessories'] ?? '―'); ?></td>
                                    <td><?= htmlspecialchars($prod['status'] ?? '―'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>商品情報なし</p>
                <?php endif; ?>

                <a href="K6-order_detail.php?id=<?= htmlspecialchars($order['order_management_id']); ?>" class="button is-link mt-3">詳細へ</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

  </div>
</div>

<?php require 'footer.php'; ?>
