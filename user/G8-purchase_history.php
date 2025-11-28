<?php
require './header.php';
require '../config/db-connect.php';
session_start();

// ▼ ログインチェック
if (empty($_SESSION['user_id'])) {
    echo "<script>alert('購入履歴を見るにはログインが必要です'); window.location.href='L1-login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ▼ 購入履歴取得（キャンセルステータスも取得）
    $stmt = $pdo->prepare("
        SELECT 
            OM.order_management_id,
            OM.order_date,
            OM.payment_method,
            OM.payment_status,
            OM.cancel_request_status,  
            OM.cancelled_at,            -- 管理者によるキャンセル追加
            ODM.quantity,
            PM.product_management_id,
            PM.product_id,
            P.product_name,
            P.price,
            P.image AS product_image,
            A.accessories_name,
            S.status_name,
            SH.shipping_date
        FROM order_management OM
        INNER JOIN customer_management CM ON OM.customer_management_id = CM.customer_management_id
        INNER JOIN order_detail_management ODM ON OM.order_management_id = ODM.order_management_id
        INNER JOIN product_management PM ON ODM.product_management_id = PM.product_management_id
        INNER JOIN product P ON PM.product_id = P.product_id
        LEFT JOIN accessories A ON PM.accessories_id = A.accessories_id
        LEFT JOIN status S ON PM.status_id = S.status_id
        LEFT JOIN shipping SH ON PM.shipping_id = SH.shipping_id
        WHERE CM.user_id = ?
        ORDER BY OM.order_date DESC, OM.order_management_id DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    exit("購入履歴取得エラー：" . $e->getMessage());
}

// ▼ 注文一覧を order_id ごとにグループ化
$history = [];
foreach ($orders as $row) {
    $order_id = $row['order_management_id'];

    if (!isset($history[$order_id])) {
        $history[$order_id]['info'] = [
            'order_date' => $row['order_date'],
            'payment_method' => $row['payment_method'],
            'payment_status' => $row['payment_status'],
            'cancel_status' => $row['cancel_request_status'],  
            'cancelled_at' => $row['cancelled_at'],          // 管理者キャンセル日時
        ];
    }

    $history[$order_id]['products'][] = [
        'name' => $row['product_name'],
        'price' => $row['price'],
        'quantity' => $row['quantity'],
        'subtotal' => $row['price'] * $row['quantity'],
        'product_id' => $row['product_id'],
        'product_management_id' => $row['product_management_id'],
        'accessories' => $row['accessories_name'] ?? '―',
        'status' => $row['status_name'] ?? '―',
        'shipping_date' => $row['shipping_date'] ?? '―'
    ];
}
?>

<section class="section">
    <div class="container">
        <h2 class="title is-4 has-text-centered">購入履歴</h2>

        <?php if (!empty($history)): ?>
            <?php foreach ($history as $order_id => $data): ?>
                <div class="box mb-5">
                    <p><strong>注文番号：</strong><?= htmlspecialchars($order_id) ?></p>
                    <p><strong>注文日：</strong><?= date('Y/m/d H:i', strtotime($data['info']['order_date'])) ?></p>
                    <p><strong>支払方法：</strong><?= htmlspecialchars($data['info']['payment_method']) ?></p>
                    <p><strong>支払状況：</strong><?= htmlspecialchars($data['info']['payment_status']) ?></p>

                    <table class="table is-fullwidth is-striped mt-3">
                        <thead>
                            <tr>
                                <th>商品名</th>
                                <th>数量</th>
                                <th>小計</th>
                                <th>商品番号</th>
                                <th>付属品</th>
                                <th>状態</th>
                                <th>発送日</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['products'] as $prod): ?>
                                <tr>
                                    <td><?= htmlspecialchars($prod['name']) ?></td>
                                    <td><?= $prod['quantity'] ?></td>
                                    <td>¥<?= number_format($prod['subtotal']) ?></td>
                                    <td><?= htmlspecialchars($prod['product_id']) ?></td>
                                    <td><?= htmlspecialchars($prod['accessories']) ?></td>
                                    <td><?= htmlspecialchars($prod['status']) ?></td>
                                    <td><?= htmlspecialchars($prod['shipping_date']) ?></td>
                                    <td>
                                        <?php if (!empty($prod['product_management_id'])): ?>
                                            <a href="G3-product_detail.php?id=<?= intval($prod['product_management_id']) ?>" 
                                               class="button is-small is-info mb-1">
                                                再度購入する
                                            </a>
                                        <?php else: ?>
                                            <span class="has-text-grey">再購入不可</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- ▼ キャンセルステータス表示 -->
                    <?php if (!empty($data['info']['cancelled_at'])): ?>
                        <button class="button is-small is-danger is-light mt-2" disabled>
                            キャンセル済み
                        </button>
                    <?php elseif ($data['info']['cancel_status'] === '申請中'): ?>
                        <button class="button is-small is-danger is-light mt-2" disabled>
                            キャンセル申請中
                        </button>
                    <?php else: ?>
                        <form method="GET" action="G9-cancel.php" class="mt-2">
                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id) ?>">
                            <button type="submit" class="button is-small is-danger">キャンセル申請</button>
                        </form>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="has-text-centered">購入履歴はありません。</p>
        <?php endif; ?>
    </div>
</section>

<?php require './footer.php'; ?>
