<?php
// --- ▼ デバッグ用 ▼ ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
// --- ▲ デバッグ用 ▲ ---

session_start();

require '../config/db-connect.php'; // ← DB接続はHTML出力前に呼び出す

// --- ▼ DB接続 ▼ ---
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<div class='notification is-danger'>DB接続エラー: " . htmlspecialchars($e->getMessage()) . "</div>");
}

// -----------------------------------------------------------
// ▼ 注文登録 POST 処理
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $customer_id = intval($_POST['customer_id']);
    $product_management_id = intval($_POST['product_management_id']);
    $delivery_date = $_POST['delivery_date'] ?? null;
    $delivery_time = $_POST['delivery_time'] ?? null;
    $payment_confirmation = $_POST['payment_confirmation'] ?? '未入金';
    $payment_method = $_POST['payment_method'] ?? '未設定';

    try {
        $pdo->beginTransaction();

        // ▼ order_management 登録
        $sql1 = "
            INSERT INTO order_management
            (customer_management_id, order_date, payment_confirmation, delivery_date, delivery_time, payment_method)
            VALUES (:customer_id, NOW(), :payment_confirmation, :delivery_date, :delivery_time, :payment_method)
        ";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute([
            ':customer_id'          => $customer_id,
            ':payment_confirmation' => $payment_confirmation,
            ':delivery_date'        => $delivery_date,
            ':delivery_time'        => $delivery_time,
            ':payment_method'       => $payment_method
        ]);
        $order_management_id = $pdo->lastInsertId();

        // ▼ order_detail_management 登録
        $sql2 = "
            INSERT INTO order_detail_management
            (order_management_id, product_management_id)
            VALUES (:order_management_id, :product_management_id)
        ";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([
            ':order_management_id'  => $order_management_id,
            ':product_management_id'=> $product_management_id
        ]);

        $pdo->commit();

        // ▼ 完了後リダイレクト
        header("Location: K6-order_detail.php?id={$order_management_id}&message=registered");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("<div class='notification is-danger'>登録エラー: " . htmlspecialchars($e->getMessage()) . "</div>");
    }
}

// -----------------------------------------------------------
// ▼ 表示処理（HTML出力）
// -----------------------------------------------------------

// 商品一覧
try {
    $sqlProduct = "
        SELECT 
            PM.product_management_id,
            P.product_name,
            P.price
        FROM product_management PM
        INNER JOIN product P ON PM.product_id = P.product_id
        ORDER BY P.product_name ASC
    ";
    $productStmt = $pdo->query($sqlProduct);
    $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<div class='notification is-danger'>商品取得エラー: " . htmlspecialchars($e->getMessage()) . "</div>");
}

// 顧客一覧
try {
    $sqlCustomer = "SELECT customer_management_id, name FROM customer_management ORDER BY name ASC";
    $customerStmt = $pdo->query($sqlCustomer);
    $customers = $customerStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<div class='notification is-danger'>顧客取得エラー: " . htmlspecialchars($e->getMessage()) . "</div>");
}

// HTML開始
require './header.php';
?>

<div class="columns">
<?php require '../config/left-menu.php'; ?>

<div class="column" style="padding: 2rem;">

<h1 class="title is-4">注文管理／注文登録</h1>
<h2 class="subtitle is-6">新規注文の登録</h2>

<div class="box">
<form action="" method="post">

<table class="table is-fullwidth">

<tr>
    <th>顧客選択</th>
    <td>
        <div class="select is-fullwidth">
            <select name="customer_id" required>
                <option value="">選択してください</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['customer_management_id']; ?>">
                        <?= htmlspecialchars($c['name']); ?>
                    </option>
                <?php endforeach ?>
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
                    <option value="<?= $p['product_management_id']; ?>">
                        <?= htmlspecialchars($p['product_name']); ?>（<?= number_format($p['price']); ?>円）
                    </option>
                <?php endforeach ?>
            </select>
        </div>
    </td>
</tr>

<tr>
    <th>配達希望日</th>
    <td><input class="input" type="date" name="delivery_date"></td>
</tr>

<tr>
    <th>配達希望時間</th>
    <td>
        <div class="select is-fullwidth">
            <select name="delivery_time">
                <option value="">未設定</option>
                <option value="08:00-10:00">8時〜10時</option>
                <option value="11:00-13:00">11時〜13時</option>
                <option value="14:00-16:00">14時〜16時</option>
                <option value="17:00-19:00">17時〜19時</option>
            </select>
        </div>
    </td>
</tr>




<tr>
    <th>入金状況</th>
    <td>
        <div class="select is-fullwidth">
            <select name="payment_confirmation">
                <option value="未入金">未入金</option>
                <option value="入金済み">入金済み</option>
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
                <option value="クレジットカード決済">クレジットカード決済</option>
                <option value="コンビニ決済">コンビニ決済</option>
                <option value="代金引換">代金引換</option>
                <option value="キャリア決済">キャリア決済</option>
                <option value="銀行振込">銀行振込</option>
                <option value="電子マネー決済">電子マネー決済</option>
                <option value="後払い決済">後払い決済</option>
                <option value="ID決済">ID決済</option>
            </select>
        </div>
    </td>
</tr>

</table>

<div class="buttons mt-4">
    <a href="K5-order_master.php" class="button is-light">注文一覧へ戻る</a>
    <button class="button is-info is-medium">注文を登録する</button>
</div>

</form>
</div>

</div>
</div>

<?php require './footer.php'; ?>
