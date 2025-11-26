<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('default_charset', 'UTF-8');

require '../config/db-connect.php';
require './header.php';

$data = $_POST;

// ------------------------
// POSTに商品情報があるか確認
// ------------------------
if (empty($data['items']) || !is_array($data['items'])) {
    echo "<script>alert('商品情報が正しく送信されていません'); window.location.href='G1-top.php';</script>";
    exit();
}

// ------------------------
// 注文商品情報をPOSTから復元
// ------------------------
$order_products = [];
$total_price = 0;
$shipping_fee = $data['shipping_fee'] ?? 970;

foreach ($data['items'] as $item) {
    $quantity = intval($item['qty'] ?? 1);
    $price = floatval($item['price'] ?? 0);
    $subtotal = $price * $quantity;

    $order_products[] = [
        'product_management_id' => intval($item['product_management_id'] ?? 0),
        'product_id' => intval($item['product_id'] ?? 0),
        'name' => $item['name'] ?? '―',
        'price' => $price,
        'quantity' => $quantity,
        'subtotal' => $subtotal,
        'accessories' => $item['accessories'] ?? '―',
        'status' => $item['status'] ?? '―',
        'shipping_date' => $item['shipping_date'] ?? '―',
        'stock' => intval($item['stock'] ?? 0)
    ];

    $total_price += $subtotal;
}

$total_price += $shipping_fee;

// ------------------------
// DB登録
// ------------------------
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->beginTransaction();

    // 顧客登録
    $stmt = $pdo->prepare("
        INSERT INTO customer_management
        (name, email, phone, address, street_address, postal_code, registration_date)
        VALUES (:name, :email, :phone, :address, :street_address, :postal_code, NOW())
    ");
    $stmt->execute([
        ':name' => $data['name'],
        ':email' => $data['email'],
        ':phone' => $data['phone'],
        ':address' => $data['address'],
        ':street_address' => $data['street_address'],
        ':postal_code' => $data['postal_code'],
    ]);
    $customer_management_id = $pdo->lastInsertId();

    // 注文管理登録
    $stmt = $pdo->prepare("
        INSERT INTO order_management
        (customer_management_id, order_date, cancelled_at, payment_method, payment_status, payment_confirmation,
         delivery_date, delivery_time, email_sent)
        VALUES (:customer_management_id, NOW(), NULL, :payment_method, '未入金', '未確認',
                :delivery_date, :delivery_time, 0)
    ");
    $stmt->execute([
        ':customer_management_id' => $customer_management_id,
        ':payment_method' => $data['payment_method'],
        ':delivery_date' => $data['delivery_date'] ?: null,
        ':delivery_time' => $data['delivery_time'] ?: null,
    ]);
    $order_id = $pdo->lastInsertId();

    // 注文詳細登録
    $stmt_detail = $pdo->prepare("
        INSERT INTO order_detail_management
        (order_management_id, product_management_id, quantity)
        VALUES (:order_id, :product_management_id, :qty)
    ");

    foreach ($order_products as $prod) {
        $stmt_detail->execute([
            ':order_id' => $order_id,
            ':product_management_id' => $prod['product_management_id'],
            ':qty' => $prod['quantity']
        ]);
    }

    $pdo->commit();

} catch (PDOException $e) {
    $pdo->rollBack();
    exit("注文登録エラー：" . $e->getMessage());
}

// ------------------------
// 自動メール送信
// ------------------------
try {
    if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $to = $data['email'];
        $subject = "【RePhone】ご注文完了のお知らせ";

        $body  = $data['name'] . " 様\r\n\r\n";
        $body .= "ご注文ありがとうございます。（注文番号：{$order_id}）\r\n";
        $body .= "以下の内容でご注文を承りました。\r\n\r\n";

        foreach ($order_products as $prod) {
            $body .= "———————————————\r\n";
            $body .= "商品名：{$prod['name']}\r\n";
            $body .= "数量：{$prod['quantity']}\r\n";
            $body .= "小計：¥" . number_format($prod['subtotal']) . "\r\n";
            $body .= "商品番号：{$prod['product_id']}\r\n";
            $body .= "付属品：{$prod['accessories']}\r\n";
            $body .= "状態：{$prod['status']}\r\n";
            $body .= "発送日：{$prod['shipping_date']}\r\n\r\n";
        }

        $body .= "送料：¥" . number_format($shipping_fee) . "\r\n";
        $body .= "合計金額：¥" . number_format($total_price) . "\r\n\r\n";

        $body .= "■ お客様情報\r\n";
        $body .= "氏名：{$data['name']} 様\r\n";
        $body .= "電話番号：{$data['phone']}\r\n";
        $body .= "住所：{$data['address']} {$data['street_address']}\r\n";
        $body .= "郵便番号：{$data['postal_code']}\r\n\r\n";
        $body .= "支払方法：{$data['payment_method']}\r\n";
        $body .= "注文日時：" . date('Y/m/d H:i') . "\r\n\r\n";
        $body .= "またのご利用を心よりお待ちしております。\r\n";
        $body .= "-------------------------------------------------\r\n";
        $body .= "RePhone株式会社";

        $headers  = "From: RePhone株式会社 <info@versus.jp>\r\n";
        $headers .= "Reply-To: info@versus.jp\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        mb_send_mail($to, $subject, $body, $headers, "-finfo@versus.jp");

        // メール送信済みに更新
        $stmtUpdate = $pdo->prepare("UPDATE order_management SET email_sent = 1 WHERE order_management_id = ?");
        $stmtUpdate->execute([$order_id]);
    }
} catch (Exception $e) {
    error_log("メール送信例外: " . $e->getMessage());
}

// ------------------------
// 注文完了ページ表示
// ------------------------
?>
<section class="section">
<div class="container has-text-centered" style="max-width:800px; margin:0 auto;">
    <h2 class="title is-4 has-text-success">ご注文ありがとうございます！</h2>
    <p class="mb-5">ご注文が正常に完了しました。</p>

    <div class="box has-text-left">
        <p class="is-size-5"><strong>注文番号：</strong><?= htmlspecialchars($order_id) ?></p>
        <p><strong>氏名：</strong><?= htmlspecialchars($data['name']) ?> 様</p>
        <p><strong>電話番号：</strong><?= htmlspecialchars($data['phone']) ?></p>
        <p><strong>メール：</strong><?= htmlspecialchars($data['email']) ?></p>
        <p><strong>住所：</strong><?= htmlspecialchars($data['address'] . ' ' . $data['street_address']) ?></p>
        <p><strong>支払方法：</strong><?= htmlspecialchars($data['payment_method']) ?></p>
        <p><strong>注文日：</strong><?= date('Y/m/d H:i') ?></p>

        <?php
        // 支払い期限（コンビニ決済 or 銀行振込）
        $payment_deadline = '';
        if (in_array($data['payment_method'], ['コンビニ決済', '銀行振込'])) {
            $deadline = strtotime('+14 days');
            $payment_deadline = date('Y/m/d', $deadline) . '（' . ['日','月','火','水','木','金','土'][date('w', $deadline)] . '）';
        }
        ?>
        <?php if ($payment_deadline): ?>
            <p><strong>お支払い期限：</strong><span style="color:red;"><?= htmlspecialchars($payment_deadline) ?></span></p>
        <?php endif; ?>

        <hr>
        <h3 class="title is-5">ご注文商品</h3>
        <table class="table is-fullwidth is-striped">
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
                <?php foreach ($order_products as $prod): ?>
                    <tr>
                        <td><?= $prod['quantity'] ?></td>
                        <td>¥<?= number_format($prod['subtotal']) ?></td>
                        <td><?= htmlspecialchars($prod['product_id']) ?></td>
                        <td><?= htmlspecialchars($prod['accessories'] ?? '―') ?></td>
                        <td><?= htmlspecialchars($prod['status'] ?? '―') ?></td>
                        <td><?= htmlspecialchars($prod['shipping_date'] ?? '―') ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="2"><strong>送料</strong></td>
                    <td colspan="5">¥<?= number_format($shipping_fee) ?></td>
                </tr>
                <tr>
                    <td colspan="2"><strong>合計金額</strong></td>
                    <td colspan="5">¥<?= number_format($total_price) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <a href="G1-top.php" class="button is-danger is-medium mt-4">トップへ戻る</a>
</div>
</section>

<?php require './footer.php'; ?>
