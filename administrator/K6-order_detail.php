<?php
// --- ▼ デバッグ用 ▼ ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
// --- ▲ デバッグ用 ▲ ---

session_start();
require '../config/db-connect.php'; // DB接続は先に

// --- ▼ DB接続 ▼ ---
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<div class='notification is-danger'>DB接続エラー: " . htmlspecialchars($e->getMessage()) . "</div>");
}

// --- ▼ 注文削除処理（header.phpより前に実行） ▼ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'], $_POST['order_id'])) {
    $delete_id = intval($_POST['order_id']);
    if ($delete_id > 0) {
        try {
            $pdo->beginTransaction();

            // 明細削除
            $stmt1 = $pdo->prepare("DELETE FROM order_detail_management WHERE order_management_id = ?");
            $stmt1->execute([$delete_id]);

            // 注文削除
            $stmt2 = $pdo->prepare("DELETE FROM order_management WHERE order_management_id = ?");
            $stmt2->execute([$delete_id]);

            $pdo->commit();

            // 削除後は注文一覧へリダイレクト
            header("Location: K5-order_master.php?message=deleted");
            exit();

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            die("<div class='notification is-danger'>削除エラー: " . htmlspecialchars($e->getMessage()) . "</div>");
        }
    } else {
        header("Location: K5-order_master.php?error=invalid_id");
        exit();
    }
}

// --- ▼ GET検証 ▼ ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<div class='main-content'><p class='has-text-danger'>無効な注文IDです。</p></div>");
}

$order_id = intval($_GET['id']);

// --- ▼ 注文詳細取得 ▼ ---
try {
    $sql = "
        SELECT 
            OM.order_date,
            OM.payment_confirmation,
            OM.payment_method,
            OM.delivery_date,
            OM.delivery_time,
            CM.name AS customer_name,
            CM.customer_management_id,
            CM.phone AS customer_phone,
            CM.address AS customer_address,
            CM.postal_code,
            P.product_name,
            P.price,
            P.product_id,
            P.image AS product_image,
            S.shipping_date
        FROM order_management OM
        INNER JOIN customer_management CM ON OM.customer_management_id = CM.customer_management_id
        LEFT JOIN order_detail_management ODM ON OM.order_management_id = ODM.order_management_id
        LEFT JOIN product_management PM ON ODM.product_management_id = PM.product_management_id
        LEFT JOIN product P ON PM.product_id = P.product_id
        LEFT JOIN shipping S ON P.shipping_id = S.shipping_id
        WHERE OM.order_management_id = ?
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("<div class='main-content'><p class='has-text-danger'>指定された注文ID ({$order_id}) の詳細情報が見つかりませんでした。</p></div>");
    }

} catch (PDOException $e) {
    die("<div class='main-content'><p class='has-text-danger'>データベースエラー: " . htmlspecialchars($e->getMessage()) . "</p></div>");
}

// --- ▼ header.php 読み込み（ここから HTML 出力開始 OK） ▼ ---
require './header.php';
?>

<!-- ▼ メッセージ表示 -->
<?php if (isset($_GET['message']) && $_GET['message'] === 'registered'): ?>
    <div class="notification is-success">
        注文情報を登録しました。
    </div>
<?php endif; ?>


<div class="columns">

    <?php require '../config/left-menu.php'; ?>

    <div class="column" style="padding: 2rem;">

        <h2 class="title is-4">注文管理 / 注文詳細</h2>
        <hr>
        <h3 class="subtitle is-5">注文詳細:</h3>

        <div class="columns">

            <!-- 商品画像カード -->
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
                        <p class="subtitle is-6 has-text-danger">¥<?= number_format($order['price']); ?> 円</p>
                        <p class="subtitle is-7">商品番号：<strong><?= htmlspecialchars($order['product_id'] ?? '―'); ?></strong></p>
                        <p class="mt-2">発送日：<strong><?= htmlspecialchars($order['shipping_date'] ?? '―'); ?></strong></p>
                    </div>
                </div>
            </div>

            <!-- 注文情報テーブル -->
            <div class="column is-two-thirds">
                <table class="table is-fullwidth is-striped">
                    <tbody>
                        <tr><th>注文日</th><td><?= date('Y/m/d', strtotime($order['order_date'])); ?></td></tr>
                        <tr><th>顧客名</th><td><?= htmlspecialchars($order['customer_name']); ?>様</td></tr>
                        <tr><th>顧客番号</th><td><?= htmlspecialchars($order['customer_management_id']); ?></td></tr>
                        <tr><th>電話番号</th><td><?= htmlspecialchars($order['customer_phone']); ?></td></tr>
                        <tr><th>住所</th><td><?= htmlspecialchars($order['customer_address']); ?></td></tr>
                        <tr><th>郵便番号</th><td><?= htmlspecialchars($order['postal_code'] ?? '未設定'); ?></td></tr>
                        <tr><th>配達希望日</th><td><?= htmlspecialchars($order['delivery_date'] ?? '未設定'); ?></td></tr>
                        <tr><th>配達希望時間</th><td><?= htmlspecialchars($order['delivery_time'] ?? '未設定'); ?></td></tr>
                        <tr>
                            <th>入金状況</th>
                            <td>
                                <span class="tag <?= ($order['payment_confirmation'] === '入金済み') ? 'is-success' : 'is-danger'; ?>">
                                    <?= htmlspecialchars($order['payment_confirmation']); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>支払方法</th>
                            <td><?= htmlspecialchars($order['payment_method'] ?? '未設定'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

        <!-- 戻るボタン -->
        <a href="K5-order_master.php" class="button is-light mt-4">注文一覧へ戻る</a>

        <!-- 編集ボタン -->
        <a href="K6-order_edit.php?id=<?= $order_id; ?>" class="button is-warning mt-4" style="margin-right: 10px;">
            注文内容を編集する
        </a>

        <!-- 削除ボタン -->
        <form action="" method="post" onsubmit="return confirm('本当に削除しますか？');" style="margin-top: 20px;">
            <input type="hidden" name="delete" value="1">
            <input type="hidden" name="order_id" value="<?= $order_id; ?>">
            <button class="button is-danger">注文を削除する</button>
        </form>

    </div>
</div>

<?php require './footer.php'; ?>
