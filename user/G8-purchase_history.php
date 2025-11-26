<?php
session_start();
require './header.php';
require '../config/db-connect.php';

// ▼ ログインチェック（user_id を使用）
if (empty($_SESSION['user_id'])) {
    echo "<script>alert('購入履歴を見るにはログインが必要です'); window.location.href='L1-login.php';</script>";
    exit();
}

$customer_id = $_SESSION['user_id'];  // ← こちらを使用

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ▼ 購入履歴取得
    $stmt = $pdo->prepare("
        SELECT 
            OM.order_management_id,
            OM.order_date,
            OM.payment_method,
            OM.payment_status,
            ODM.quantity,
            PM.product_id,
            P.product_name,
            P.price,
            P.image AS product_image,
            A.accessories_name,
            S.status_name,
            SH.shipping_date
        FROM order_management OM
        INNER JOIN order_detail_management ODM ON OM.order_management_id = ODM.order_management_id
        INNER JOIN product_management PM ON ODM.product_management_id = PM.product_management_id
        INNER JOIN product P ON PM.product_id = P.product_id
        LEFT JOIN accessories A ON PM.accessories_id = A.accessories_id
        LEFT JOIN status S ON PM.status_id = S.status_id
        LEFT JOIN shipping SH ON PM.shipping_id = SH.shipping_id
        WHERE OM.customer_management_id = ?
        ORDER BY OM.order_date DESC, OM.order_management_id DESC
    ");
    $stmt->execute([$customer_id]);
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
        ];
    }

    $history[$order_id]['products'][] = [
        'name' => $row['product_name'],
        'price' => $row['price'],
        'quantity' => $row['quantity'],
        'subtotal' => $row['price'] * $row['quantity'],
        'product_id' => $row['product_id'],
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
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="has-text-centered">購入履歴はありません。</p>
        <?php endif; ?>
    </div>
</section>

<?php require './footer.php'; ?>
