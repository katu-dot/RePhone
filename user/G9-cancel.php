<?php
require './header.php';
require '../config/db-connect.php';

// ▼ ログインチェック
if (empty($_SESSION['user_id'])) {
    echo "<script>alert('ログインが必要です'); window.location.href='L1-login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// ▼ 注文IDチェック
if (empty($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    echo "<script>alert('不正なアクセスです'); window.location.href='G8-purchase_history.php';</script>";
    exit();
}

$order_id = intval($_GET['order_id']);

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ▼ 注文が本人のものかチェック
    $stmt = $pdo->prepare("
        SELECT 
            OM.order_management_id,
            OM.order_date,
            OM.payment_method,
            OM.payment_status
        FROM order_management OM
        INNER JOIN customer_management CM 
            ON OM.customer_management_id = CM.customer_management_id
        WHERE OM.order_management_id = ?
        AND CM.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo "<script>alert('注文情報が見つかりません'); window.location.href='G8-purchase_history.php';</script>";
        exit();
    }

    // ▼ 商品情報取得
    $stmt = $pdo->prepare("
        SELECT 
            ODM.quantity,
            PM.product_management_id,
            P.product_id AS product_number,
            P.product_name,
            P.image,
            A.accessories_name,
            S.status_name,
            SH.shipping_date
        FROM order_detail_management ODM
        INNER JOIN product_management PM ON ODM.product_management_id = PM.product_management_id
        INNER JOIN product P ON PM.product_id = P.product_id
        LEFT JOIN accessories A ON PM.accessories_id = A.accessories_id
        LEFT JOIN status S ON PM.status_id = S.status_id
        LEFT JOIN shipping SH ON PM.shipping_id = SH.shipping_id
        WHERE ODM.order_management_id = ?
    ");
    $stmt->execute([$order_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    exit("注文商品情報取得エラー：" . $e->getMessage());
}
?>

<section class="section">
    <div class="container">
        <h2 class="title is-4 has-text-centered">キャンセル申請</h2>

        <div class="box">
            <p><strong>注文番号：</strong><?= htmlspecialchars($order_id) ?></p>
            <p><strong>注文日：</strong><?= date('Y/m/d H:i', strtotime($order['order_date'])) ?></p>
            <p><strong>支払方法：</strong><?= htmlspecialchars($order['payment_method']) ?></p>
            <p><strong>支払状況：</strong><?= htmlspecialchars($order['payment_status']) ?></p>
        </div>

        <h3 class="title is-5">注文商品</h3>

        <table class="table is-fullwidth is-striped">
            <thead>
                <tr>
                    <th>商品画像</th>
                    <th>商品名</th>
                    <th>商品番号</th>
                    <th>数量</th>
                    <th>付属品</th>
                    <th>状態</th>
                    <th>発送日</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                        <?php
                        $imagePath = '../' . ltrim($p['image'], '/');
                        if (!empty($p['image']) && file_exists($imagePath)) {
                            echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($p['product_name']) . '" style="object-fit: contain;">';
                        } else {
                            echo '<img src="../img/noimage.png" alt="画像なし" style="object-fit: contain;">';
                        }
                        ?>
                        </td>
                        <td><?= htmlspecialchars($p['product_name']) ?></td>
                        <td><?= htmlspecialchars($p['product_number']) ?></td>
                        <td><?= intval($p['quantity']) ?></td>
                        <td><?= htmlspecialchars($p['accessories_name'] ?? '―') ?></td>
                        <td><?= htmlspecialchars($p['status_name'] ?? '―') ?></td>
                        <td><?= htmlspecialchars($p['shipping_date'] ?? '―') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- ▼ キャンセル申請フォーム -->
        <form method="POST" action="G10-cancel_submit.php" onsubmit="return confirmCancel();">
            <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id) ?>">

            <div class="field mt-4">
                <label class="label">キャンセル理由（必須）</label>
                <div class="control">
                    <textarea name="reason" class="textarea" required placeholder="キャンセル理由を入力してください"></textarea>
                </div>
            </div>

            <button type="submit" class="button is-danger is-medium mt-3">キャンセル申請する</button>
        </form>
    </div>
</section>

<script>
function confirmCancel() {
    return confirm("本当にキャンセル申請を送信しますか？");
}
</script>

<?php require './footer.php'; ?>
