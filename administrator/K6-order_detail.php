<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require '../config/db-connect.php';

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<div class='notification is-danger'>DB接続エラー: " . htmlspecialchars($e->getMessage()) . "</div>");
}

// GETチェック
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<div class='main-content'><p class='has-text-danger'>無効な注文IDです。</p></div>");
}

$order_id = intval($_GET['id']);


// ▼ 注文キャンセル処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {

    try {
        $stmt = $pdo->prepare("UPDATE order_management SET cancelled_at = NOW() WHERE order_management_id = ?");
        $stmt->execute([$order_id]);

        // ▼ メール送信
        $to = $_POST['email'];
        $subject = "【RePhone】ご注文キャンセルのお知らせ";
        $body = $_POST['name'] . " 様\n\n"
              . "ご注文ID：" . $order_id . "\n"
              . "ご注文はキャンセルされました。\n\n"
              . "ご不明点がございましたらお問い合わせください。";
        @mb_send_mail($to, $subject, $body);

        header("Location: K6-order_detail.php?id=$order_id");
        exit;

    } catch (PDOException $e) {
        echo "<div class='notification is-danger'>キャンセル処理エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}


// ▼ キャンセル解除処理（cancelled_at を NULL に戻す）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reset'])) {

    try {
        $stmt = $pdo->prepare("UPDATE order_management SET cancelled_at = NULL WHERE order_management_id = ?");
        $stmt->execute([$order_id]);

        // ▼ メール送信
        $to = $_POST['email'];
        $subject = "【RePhone】ご注文キャンセル解除のお知らせ";
        $body = $_POST['name'] . " 様\n\n"
              . "ご注文ID：" . $order_id . "\n"
              . "キャンセルを解除しました。\n\n"
              . "引き続きよろしくお願いいたします。";
        @mb_send_mail($to, $subject, $body);

        header("Location: K6-order_detail.php?id=$order_id");
        exit;

    } catch (PDOException $e) {
        echo "<div class='notification is-danger'>キャンセル解除エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}


// ▼ 削除処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {

    $delete_id = intval($_POST['delete_id']);

    try {
        $pdo->beginTransaction();

        $pdo->prepare("DELETE FROM order_detail WHERE order_id = ?")->execute([$delete_id]);
        $pdo->prepare("DELETE FROM order_management WHERE order_management_id = ?")->execute([$delete_id]);

        $pdo->commit();

        echo "<script>alert('注文を削除しました'); window.location.href='K5-order_master.php';</script>";
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "<div class='notification is-danger'>削除エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}


// ▼ 注文詳細取得
try {
    $stmt = $pdo->prepare("
    SELECT 
        OM.order_management_id,
        OM.order_date,
        OM.payment_confirmation,
        OM.payment_method,
        OM.delivery_date,
        OM.delivery_time,
        OM.cancelled_at,
        OM.email_sent,
        CM.name AS customer_name,
        CM.customer_management_id,
        CM.phone AS customer_phone,
        CM.email AS customer_email,
        CM.street_address,
        CM.address AS customer_address,
        CM.postal_code,
        ODM.product_management_id,
        ODM.quantity,
        PM.product_id,
        P.product_name,
        P.price,
        P.image AS product_image,
        A.accessories_name,
        S.status_name,
        SH.shipping_date
    FROM order_management OM
    INNER JOIN customer_management CM 
        ON OM.customer_management_id = CM.customer_management_id
    LEFT JOIN order_detail_management ODM 
        ON ODM.order_management_id = OM.order_management_id
    LEFT JOIN product_management PM 
        ON PM.product_management_id = ODM.product_management_id
    LEFT JOIN product P 
        ON P.product_id = PM.product_id
    LEFT JOIN accessories A
        ON PM.accessories_id = A.accessories_id
    LEFT JOIN status S
        ON PM.status_id = S.status_id
    LEFT JOIN shipping SH
        ON P.shipping_id = SH.shipping_id
    WHERE OM.order_management_id = ?
    ");

    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$order_items) {
        die("<div class='main-content'><p class='has-text-danger'>指定された注文ID ({$order_id}) の詳細が見つかりません。</p></div>");
    }

    $order = $order_items[0];

} catch (PDOException $e) {
    die("<div class='main-content'><p class='has-text-danger'>データベースエラー: " . htmlspecialchars($e->getMessage()) . "</p></div>");
}


require './header.php';
?>

<div class="columns">
    <?php require '../config/left-menu.php'; ?>
    <div class="column" style="padding: 2rem;">

        <h2 class="title is-4">注文管理 / 注文詳細</h2>

        <?php if (!empty($order['cancelled_at'])): ?>
            <div class="notification is-danger">
                <strong>この注文はキャンセル済です（<?= htmlspecialchars($order['cancelled_at']); ?>）</strong>
            </div>
        <?php endif; ?>

        <hr>

        <div class="columns">
            <!-- 商品情報 -->
            <div class="column is-one-third">

            <?php foreach ($order_items as $item): ?>
                <?php
                    $qty = intval($item['quantity'] ?? 1);
                    $product_name = $item['product_name'] ?? '―';
                    $imageBaseUrl = '../uploads/';
                    $imagePath = !empty($item['product_image'])
                        ? $imageBaseUrl . basename($item['product_image'])
                        : '../img/noimage.png';
                ?>

                <div class="card mb-4">
                    <div class="card-image">
                        <figure class="image is-4by3">
                            <img src="<?= htmlspecialchars($imagePath, ENT_QUOTES); ?>" 
                                 alt="<?= htmlspecialchars($product_name, ENT_QUOTES); ?>">
                        </figure>
                    </div>

                    <div class="card-content">
                        <p class="title is-5"><?= htmlspecialchars($product_name); ?></p>

                        <p class="subtitle is-6 has-text-danger">
                            小計：¥<?= number_format($item['price']); ?><br>
                            送料：¥970<br>
                            合計：¥<?= number_format($item['price'] + 970); ?><br>
                            数量：<?= $qty ?>
                        </p>

                        <p>商品番号：<strong><?= htmlspecialchars($item['product_id'] ?? '―'); ?></strong></p>
                        <p>発送日：<strong><?= htmlspecialchars($item['shipping_date'] ?? '―'); ?></strong></p>
                        <p>付属品：<strong><?= htmlspecialchars($item['accessories_name'] ?? '―'); ?></strong></p>
                        <p>状態：<strong><?= htmlspecialchars($item['status_name'] ?? '―'); ?></strong></p>
                    </div>
                </div>

            <?php endforeach; ?>

            <!-- 編集ボタン（キャンセル時は非表示） -->
            <?php if (empty($order['cancelled_at'])): ?>
                <a href="K6-order_edit.php?id=<?= htmlspecialchars($order['order_management_id']); ?>" 
                class="button is-warning is-small is-fullwidth">
                編集
                </a>
            <?php endif; ?>

            <!-- ▼ キャンセル or キャンセル解除ボタン -->
            <?php if (empty($order['cancelled_at'])): ?>
                <form method="POST"  onsubmit="return confirm('本当にキャンセルしますか？');">
                    <input type="hidden" name="cancel_order" value="1">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($order['customer_email']); ?>">
                    <input type="hidden" name="name"  value="<?= htmlspecialchars($order['customer_name']); ?>">
                    <button class="button is-danger is-small is-fullwidth"  style="margin-top:8px;">キャンセル</button>
                </form>
            <?php else: ?>
                <form method="POST" onsubmit="return confirm('本当にキャンセル解除しますか？');">
                    <input type="hidden" name="cancel_reset" value="1">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($order['customer_email']); ?>">
                    <input type="hidden" name="name"  value="<?= htmlspecialchars($order['customer_name']); ?>">
                    <button class="button is-info is-small is-fullwidth">キャンセル解除</button>
                </form>
            <?php endif; ?>

            <!-- 削除ボタン（キャンセル時は非表示） -->
            <?php if (empty($order['cancelled_at'])): ?>
                <form method="POST" onsubmit="return confirm('本当にこの注文を削除しますか？');" style="margin-top:10px;">
                    <input type="hidden" name="delete_id" value="<?= htmlspecialchars($order['order_management_id']); ?>">
                    <button type="submit" class="button is-danger is-small is-fullwidth">削除</button>
                </form>
            <?php endif; ?>

            <a href="K5-order_master.php" class="button is-light mt-4">注文一覧へ戻る</a>

            </div>


            <!-- 注文情報テーブル -->
            <div class="column is-two-thirds">
                <table class="table is-fullwidth is-striped">
                    <tbody>
                        <tr><th>注文日</th><td><?= htmlspecialchars($order['order_date'] ?? '―'); ?></td></tr>
                        <tr><th>顧客名</th><td><?= htmlspecialchars($order['customer_name'] ?? '―'); ?> 様</td></tr>
                        <tr><th>顧客番号</th><td><?= htmlspecialchars($order['customer_management_id'] ?? '―'); ?></td></tr>
                        <tr><th>電話番号</th><td>
                            <?php 
                                $phone = $order['customer_phone'] ?? '';
                                $formatted_phone = preg_replace('/(\d{3})(\d{4})(\d{4})/', '$1-$2-$3', $phone);
                                echo htmlspecialchars($formatted_phone); 
                            ?>
                        </td></tr>
                        <tr><th>メールアドレス</th><td><?= htmlspecialchars($order['customer_email'] ?? '―'); ?></td></tr>
                        <tr><th>住所</th><td><?= htmlspecialchars($order['customer_address'] ?? '―'); ?> <?= htmlspecialchars($order['street_address'] ?? ''); ?></td></tr>
                        <tr><th>郵便番号</th><td>
                            <?php
                                $postal = $order['postal_code'] ?? '';
                                $postal = str_replace('-', '', $postal);
                                $formatted_postal = preg_replace('/(\d{3})(\d{4})/', '$1-$2', $postal);
                                echo htmlspecialchars($formatted_postal);
                            ?>
                        </td></tr>
                        <tr><th>配達希望日</th><td><?= htmlspecialchars($order['delivery_date'] ?: '指定なし'); ?></td></tr>
                        <tr><th>配達希望時間</th><td><?= htmlspecialchars($order['delivery_time'] ?: '指定なし'); ?></td></tr>
                        <tr>
                            <th>入金状況</th>
                            <td>
                                <span class="tag <?= ($order['payment_confirmation'] === '入金済み') ? 'is-success' : 'is-danger'; ?>">
                                    <?= htmlspecialchars($order['payment_confirmation'] ?? '未確認'); ?>
                                </span>
                            </td>
                        </tr>
                        <tr><th>支払方法</th><td><?= htmlspecialchars($order['payment_method'] ?? '未設定'); ?></td></tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<?php require './footer.php'; ?>
