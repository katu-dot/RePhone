<?php
require './header.php';
require '../config/db-connect.php';

// ▼ ログインチェック
if (empty($_SESSION['user_id'])) {
    echo "<script>alert('ログインが必要です'); window.location.href='L1-login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// ▼ POSTチェック
if (empty($_POST['order_id']) || empty($_POST['reason'])) {
    echo "<script>alert('不正なアクセスです'); window.location.href='G8-purchase_history.php';</script>";
    exit();
}

$order_id = intval($_POST['order_id']);
$reason   = trim($_POST['reason']);

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ▼ 注文がログインユーザーのものか確認
    $stmt = $pdo->prepare("
        SELECT OM.order_management_id, CM.name AS customer_name, CM.email AS customer_email
        FROM order_management OM
        INNER JOIN customer_management CM 
            ON OM.customer_management_id = CM.customer_management_id
        WHERE OM.order_management_id = ? 
        AND CM.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);

    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        echo "<script>alert('不正な注文IDです'); window.location.href='G8-purchase_history.php';</script>";
        exit();
    }

    $customer_name  = $order['customer_name'];
    $customer_email = $order['customer_email'];

    // ▼ トランザクション開始
    $pdo->beginTransaction();

    // ▼ cancel_request に記録
    $stmt = $pdo->prepare("
        INSERT INTO cancel_request (
            order_management_id, 
            user_id, 
            reason, 
            request_date, 
            status
        )
        VALUES (?, ?, ?, NOW(), '申請中')
    ");
    $stmt->execute([$order_id, $user_id, $reason]);

    // ▼ order_management にも反映
    $stmt = $pdo->prepare("
        UPDATE order_management
        SET 
            cancel_reason = ?, 
            cancel_request_status = '申請中',
            cancel_requested_at = NOW()
        WHERE order_management_id = ?
    ");
    $stmt->execute([$reason, $order_id]);

    // ▼ トランザクション完了
    $pdo->commit();

    // ▼ メール送信
    $subject = "【RePhone】キャンセル申請の受付完了";
    $body = "{$customer_name} 様\n\n"
          . "ご注文番号：{$order_id}\n"
          . "キャンセル申請を受け付けました。\n"
          . "申請理由：{$reason}\n\n"
          . "内容を確認後、管理者よりご連絡いたします。\n\n"
          . "――――――――――\nRePhone株式会社";
    
    // 文字コード指定
    mb_language("Japanese");
    mb_internal_encoding("UTF-8");

    @mb_send_mail($customer_email, $subject, $body);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    exit("キャンセル申請エラー：" . $e->getMessage());
}
?>

<section class="section">
    <div class="container">
        <h2 class="title is-4 has-text-centered">キャンセル申請が完了しました</h2>

        <div class="box has-text-centered">
            <p>注文番号：<strong><?= htmlspecialchars($order_id) ?></strong></p>
            <p class="mt-3">キャンセル申請を受け付けました。</p>
            <p>内容を確認後、管理者よりご連絡いたします。</p>

            <a href="G8-purchase_history.php" class="button is-link mt-4">購入履歴へ戻る</a>
        </div>
    </div>
</section>

<?php require './footer.php'; ?>
