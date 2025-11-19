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

$email_error = "";

// ▼ メール送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email']) && isset($_POST['order_id'])) {
    $order_id_post = intval($_POST['order_id']);
    if ($order_id_post > 0) {
        try {
            // 注文情報取得
            $stmt = $pdo->prepare("
                SELECT 
                    OM.order_date,
                    OM.delivery_date,
                    OM.delivery_time,
                    CM.name AS customer_name,
                    CM.email AS customer_email,
                    P.product_name,
                    P.price,
                    ODM.quantity
                FROM order_management OM
                INNER JOIN customer_management CM ON OM.customer_management_id = CM.customer_management_id
                LEFT JOIN order_detail_management ODM ON OM.order_management_id = ODM.order_management_id
                LEFT JOIN product_management PM ON ODM.product_management_id = PM.product_management_id
                LEFT JOIN product P ON PM.product_id = P.product_id
                WHERE OM.order_management_id = ?
                LIMIT 1
            ");
            $stmt->execute([$order_id_post]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order && !empty($order['customer_email'])) {

                $qty = $order['quantity'] ?? 1;
                $subtotal = $order['price'] * $qty;

                // メール本文
                $subject = "ご注文ありがとうございます";
                $body =
                    "{$order['customer_name']}様\n\n" .
                    "ご注文ありがとうございます。\n" .
                    "以下の内容でご注文を承りました。\n\n" .
                    "商品名：{$order['product_name']}\n" .
                    "数量：{$qty}\n" .
                    "価格：¥" . number_format($subtotal) . "\n" .
                    "配達希望時間：" . ($order['delivery_time'] ?: '指定なし') . "\n" .
                    "配達希望日：" . ($order['delivery_date'] ?: '指定なし') . "\n\n" .
                    "------------------------\n" .
                    "注文日：" . date('Y/m/d', strtotime($order['order_date']));

                // ヘッダー（UTF-8 文字化け防止）
                $headers = "From: RePhone株式会社<no-reply@versus.jp>\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                // mail() 送信
                if (mail($order['customer_email'], $subject, $body, $headers)) {
                    // email_sent を更新
                    $pdo->prepare("UPDATE order_management SET email_sent = 1 WHERE order_management_id = ?")
                        ->execute([$order_id_post]);

                    header("Location: K6-order_detail.php?id={$order_id_post}&email_sent=1");
                    exit();
                } else {
                    $email_error = "メール送信に失敗しました。";
                }
            }
        } catch (PDOException $e) {
            $email_error = "メール送信処理エラー: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ▼ キャンセル / 取り消し処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id_post = intval($_POST['order_id']);
    if ($order_id_post > 0) {
        try {
            if (isset($_POST['delete'])) {
                $stmt = $pdo->prepare("UPDATE order_management SET cancelled_at = NOW() WHERE order_management_id = ?");
                $stmt->execute([$order_id_post]);
                header("Location: K5-order_master.php?message=deleted");
                exit();
            } elseif (isset($_POST['restore'])) {
                $stmt = $pdo->prepare("UPDATE order_management SET cancelled_at = NULL WHERE order_management_id = ?");
                $stmt->execute([$order_id_post]);
                header("Location: K6-order_detail.php?id={$order_id_post}&restored=1");
                exit();
            }
        } catch (PDOException $e) {
            die("<div class='notification is-danger'>処理エラー: " . htmlspecialchars($e->getMessage()) . "</div>");
        }
    }
}

// ▼ GETチェック
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<div class='main-content'><p class='has-text-danger'>無効な注文IDです。</p></div>");
}

$order_id = intval($_GET['id']);

// ▼ 注文詳細取得（★数量を追加）
try {
    $stmt = $pdo->prepare("
        SELECT 
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
            CM.address AS customer_address,
            CM.postal_code,
            P.product_name,
            P.price,
            P.product_id,
            P.image AS product_image,
            S.shipping_date,
            ODM.quantity
        FROM order_management OM
        INNER JOIN customer_management CM ON OM.customer_management_id = CM.customer_management_id
        LEFT JOIN order_detail_management ODM ON OM.order_management_id = ODM.order_management_id
        LEFT JOIN product_management PM ON ODM.product_management_id = PM.product_management_id
        LEFT JOIN product P ON PM.product_id = P.product_id
        LEFT JOIN shipping S ON P.shipping_id = S.shipping_id
        WHERE OM.order_management_id = ?
        LIMIT 1
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("<div class='main-content'><p class='has-text-danger'>指定された注文ID ({$order_id}) の詳細が見つかりません。</p></div>");
    }

} catch (PDOException $e) {
    die("<div class='main-content'><p class='has-text-danger'>データベースエラー: " . htmlspecialchars($e->getMessage()) . "</p></div>");
}

require './header.php';
?>

<!-- メッセージ表示 -->
<?php if (!empty($_GET['registered'])): ?>
    <div class="notification is-success mt-4">注文の登録が完了しました。</div>
<?php endif; ?>

<?php if (!empty($_GET['updated'])): ?>
    <div class="notification is-success mt-4">編集が完了しました。</div>
<?php endif; ?>

<?php if (!empty($order['cancelled_at'])): ?>
    <div class="notification is-danger mt-4">この注文は <strong>キャンセル済み</strong> です。</div>
<?php endif; ?>
<?php if (!empty($_GET['restored'])): ?>
    <div class="notification is-success mt-4">キャンセルの取り消しが完了しました</div>
<?php endif; ?>
<?php if (!empty($_GET['email_sent'])): ?>
    <div class="notification is-success mt-4">メール送信が完了しました</div>
<?php endif; ?>
<?php if (!empty($email_error)): ?>
    <div class="notification is-danger mt-4"><?= htmlspecialchars($email_error); ?></div>
<?php endif; ?>

<div class="columns">
    <?php require '../config/left-menu.php'; ?>
    <div class="column" style="padding: 2rem;">
        <h2 class="title is-4">注文管理 / 注文詳細</h2>
        <hr>

        <div class="columns">
            <!-- 商品画像 -->
            <div class="column is-one-third">
                <div class="card">
                    <div class="card-image">
                        <figure class="image is-4by3">
                        <?php
                            $imageBaseUrl = '../uploads/';
                            $imageFilename = basename($order['product_image'] ?? '');
                            $imagePath = !empty($imageFilename) ? $imageBaseUrl . $imageFilename : '../img/noimage.png';
                            echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($order['product_name']) . '">';
                        ?>
                        </figure>
                    </div>
                    <div class="card-content">
                        <p class="title is-5"><?= htmlspecialchars($order['product_name']); ?></p>

                        <?php 
                            $qty = $order['quantity'] ?? 1;
                            $subtotal = $order['price'] * $qty;
                        ?>
                        <p class="subtitle is-6 has-text-danger">
                            ¥<?= number_format($order['price']); ?> 円
                            <?php if ($qty > 1): ?>
                                （数量：<?= $qty ?> / 合計：¥<?= number_format($subtotal); ?>）
                            <?php endif; ?>
                        </p>

                        <p>商品番号：<strong><?= htmlspecialchars($order['product_id'] ?? '―'); ?></strong></p>
                        <p>発送日：<strong><?= htmlspecialchars($order['shipping_date'] ?? '―'); ?></strong></p>
                    </div>
                </div>
            </div>

            <!-- 注文情報 -->
            <div class="column is-two-thirds">
                <table class="table is-fullwidth is-striped">
                    <tbody>
                        <tr><th>注文日</th><td><?= date('Y/m/d', strtotime($order['order_date'])); ?></td></tr>
                        <tr><th>顧客名</th><td><?= htmlspecialchars($order['customer_name']); ?>様</td></tr>
                        <tr><th>顧客番号</th><td><?= htmlspecialchars($order['customer_management_id']); ?></td></tr>
                        <tr><th>電話番号</th><td><?= htmlspecialchars($order['customer_phone']); ?></td></tr>

                        <tr>
                            <th>メールアドレス</th>
                            <td>
                                <?= htmlspecialchars($order['customer_email'] ?? '未設定'); ?>
                                <?php if (!empty($order['customer_email']) && empty($order['email_sent'])): ?>
                                    <form action="" method="post" style="display:inline-block; margin-left:5px;">
                                        <input type="hidden" name="order_id" value="<?= $order_id; ?>">
                                        <input type="hidden" name="send_email" value="1">
                                        <button class="button is-small is-info">メールを送る</button>
                                    </form>
                                <?php elseif(!empty($order['customer_email']) && !empty($order['email_sent'])): ?>
                                    <span class="tag is-success ml-2">送信済み</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <tr><th>住所</th><td><?= htmlspecialchars($order['customer_address']); ?></td></tr>
                        <tr><th>郵便番号</th><td><?= htmlspecialchars($order['postal_code'] ?? '未設定'); ?></td></tr>
                        <tr><th>配達希望日</th><td><?= htmlspecialchars($order['delivery_date'] ?: '指定なし'); ?></td></tr>
                        <tr><th>配達希望時間</th><td><?= htmlspecialchars($order['delivery_time'] ?: '指定なし'); ?></td></tr>

                        <tr>
                            <th>入金状況</th>
                            <td>
                                <span class="tag <?= ($order['payment_confirmation'] === '入金済み') ? 'is-success' : 'is-danger'; ?>">
                                    <?= htmlspecialchars($order['payment_confirmation']); ?>
                                </span>
                            </td>
                        </tr>

                        <tr><th>支払方法</th><td><?= htmlspecialchars($order['payment_method'] ?? '未設定'); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 戻るボタン -->
        <a href="K5-order_master.php" class="button is-light mt-4">注文一覧へ戻る</a>

        <?php if (empty($order['cancelled_at'])): ?>
            <a href="K6-order_edit.php?id=<?= $order_id; ?>" class="button is-warning mt-4" style="margin-left: 10px;">注文内容を編集する</a>
            <form action="" method="post" onsubmit="return confirm('本当にキャンセルしますか？');" style="margin-top: 10px;">
                <input type="hidden" name="delete" value="1">
                <input type="hidden" name="order_id" value="<?= $order_id; ?>">
                <button class="button is-danger">注文をキャンセルする</button>
            </form>
        <?php else: ?>
            <form action="" method="post" onsubmit="return confirm('キャンセルを取り消しますか？');" style="margin-top: 10px;">
                <input type="hidden" name="restore" value="1">
                <input type="hidden" name="order_id" value="<?= $order_id; ?>">
                <button class="button is-success">キャンセルの取り消し</button>
            </form>
        <?php endif; ?>

    </div>
</div>

<?php require './footer.php'; ?>
