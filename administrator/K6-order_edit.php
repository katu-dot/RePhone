<?php
// --- ▼ デバッグ用 ▼ ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
// --- ▲ デバッグ用 ▼ ---

session_start();
require '../config/db-connect.php'; // DB接続はHTML出力前に

// --- ▼ DB接続 ▼ ---
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<div class='notification is-danger'>DB接続エラー: " . htmlspecialchars($e->getMessage()) . "</div>");
}

// --- ▼ GET検証 ▼ ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<div class='has-text-danger'>無効な注文IDです。</div>");
}
$order_id = intval($_GET['id']);

// --- ▼ 更新処理 POST ▼ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = intval($_POST['customer_id']);
    $product_management_id = intval($_POST['product_management_id']);
    $delivery_date = $_POST['delivery_date'] ?? null;
    $delivery_time = $_POST['delivery_time'] ?? null;
    $payment_confirmation = $_POST['payment_confirmation'] ?? '未入金';
    $payment_method = $_POST['payment_method'] ?? '未設定';

    try {
        $pdo->beginTransaction();

        // order_management 更新
        $sql1 = "
            UPDATE order_management
            SET customer_management_id = :customer_id,
                delivery_date = :delivery_date,
                delivery_time = :delivery_time,
                payment_confirmation = :payment_confirmation,
                payment_method = :payment_method
            WHERE order_management_id = :order_id
        ";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute([
            ':customer_id' => $customer_id,
            ':delivery_date' => $delivery_date,
            ':delivery_time' => $delivery_time,
            ':payment_confirmation' => $payment_confirmation,
            ':payment_method' => $payment_method,
            ':order_id' => $order_id
        ]);

        // order_detail_management 更新（1件のみ想定）
        $sql2 = "
            UPDATE order_detail_management
            SET product_management_id = :product_management_id
            WHERE order_management_id = :order_id
        ";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([
            ':product_management_id' => $product_management_id,
            ':order_id' => $order_id
        ]);

        $pdo->commit();

        // 更新後は詳細ページにリダイレクト + メッセージ
        header("Location: K6-order_detail.php?id={$order_id}&message=updated");
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("<div class='notification is-danger'>更新エラー: " . htmlspecialchars($e->getMessage()) . "</div>");
    }
}

// --- ▼ 注文情報取得（フォーム初期値用） ▼ ---
try {
    $sql = "
        SELECT 
            OM.customer_management_id,
            OM.delivery_date,
            OM.delivery_time,
            OM.payment_confirmation,
            OM.payment_method,
            ODM.product_management_id
        FROM order_management OM
        LEFT JOIN order_detail_management ODM ON OM.order_management_id = ODM.order_management_id
        WHERE OM.order_management_id = ?
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("<div class='has-text-danger'>注文情報が見つかりません。</div>");
    }

} catch (PDOException $e) {
    die("<div class='notification is-danger'>取得エラー: " . htmlspecialchars($e->getMessage()) . "</div>");
}

// --- ▼ 顧客一覧取得 ▼ ---
$customers = $pdo->query("SELECT customer_management_id, name FROM customer_management ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- ▼ 商品一覧取得 ▼ ---
$products = $pdo->query("
    SELECT PM.product_management_id, P.product_name, P.price
    FROM product_management PM
    INNER JOIN product P ON PM.product_id = P.product_id
    ORDER BY P.product_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// --- ▼ HTML開始 ▼ ---
require './header.php';
?>

<div class="columns">
    <?php require '../config/left-menu.php'; ?>

    <div class="column" style="padding: 2rem;">
        <h1 class="title is-4">注文管理 / 注文編集</h1>
        <h2 class="subtitle is-6">注文内容を編集してください</h2>

        <!-- 更新完了メッセージ -->
        <?php if (isset($_GET['message']) && $_GET['message'] === 'updated'): ?>
            <div class="notification is-success">
                注文情報の編集が完了しました。
            </div>
        <?php endif; ?>

        <div class="box">
            <form method="post">

                <table class="table is-fullwidth">

                    <tr>
                        <th>顧客選択</th>
                        <td>
                            <div class="select is-fullwidth">
                                <select name="customer_id" required>
                                    <option value="">選択してください</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?= $c['customer_management_id']; ?>" <?= $c['customer_management_id']==$order['customer_management_id']?'selected':''; ?>>
                                            <?= htmlspecialchars($c['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>商品選択</th>
                        <td>
                            <div class="select is-fullwidth">
                                <select name="product_management_id" required>
                                    <option value="">選択してください</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['product_management_id']; ?>" <?= $p['product_management_id']==$order['product_management_id']?'selected':''; ?>>
                                            <?= htmlspecialchars($p['product_name']); ?>（<?= number_format($p['price']); ?>円）
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>配達希望日</th>
                        <td><input class="input" type="date" name="delivery_date" value="<?= htmlspecialchars($order['delivery_date']); ?>"></td>
                    </tr>

                    <tr>
                        <th>配達希望時間</th>
                        <td>
                            <div class="select is-fullwidth">
                                <select name="delivery_time">
                                    <option value="">指定なし</option>
                                    <option value="08:00-10:00" <?= $order['delivery_time']=='08:00-10:00'?'selected':''; ?>>8時〜10時</option>
                                    <option value="11:00-13:00" <?= $order['delivery_time']=='11:00-13:00'?'selected':''; ?>>11時〜13時</option>
                                    <option value="14:00-16:00" <?= $order['delivery_time']=='14:00-16:00'?'selected':''; ?>>14時〜16時</option>
                                    <option value="17:00-19:00" <?= $order['delivery_time']=='17:00-19:00'?'selected':''; ?>>17時〜19時</option>
                                </select>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>入金状況</th>
                        <td>
                            <div class="select is-fullwidth">
                                <select name="payment_confirmation">
                                    <option value="未入金" <?= $order['payment_confirmation']=='未入金'?'selected':''; ?>>未入金</option>
                                    <option value="入金済み" <?= $order['payment_confirmation']=='入金済み'?'selected':''; ?>>入金済み</option>
                                </select>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>支払方法</th>
                        <td>
                            <div class="select is-fullwidth">
                                <select name="payment_method" required>
                                    <option value="">選択してください</option>
                                    <?php 
                                    $methods = ["クレジットカード決済","コンビニ決済","代金引換","キャリア決済","銀行振込","電子マネー決済","後払い決済","ID決済"];
                                    foreach ($methods as $m): ?>
                                        <option value="<?= $m; ?>" <?= $order['payment_method']==$m?'selected':''; ?>><?= $m; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </td>
                    </tr>

                </table>

                <div class="buttons mt-4">
                    <a href="K6-order_detail.php?id=<?= $order_id; ?>" class="button is-light">詳細に戻る</a>
                    <button class="button is-info is-medium">更新する</button>
                </div>

            </form>
        </div>

    </div>
</div>

<?php require './footer.php'; ?>
