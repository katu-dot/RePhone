<?php
// --- デバッグ用 ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require '../config/db-connect.php';

// --- DB接続 ---
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<div class='notification is-danger'>DB接続エラー: " . htmlspecialchars($e->getMessage()) . "</div>");
}

// --- GET検証 ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<div class='has-text-danger'>無効な注文IDです。</div>");
}
$order_id = intval($_GET['id']);

// --- POST更新処理 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $customer_name = $_POST['customer_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    $street_address = $_POST['street_address'] ?? '';
    $postal_code = $_POST['postal_code'] ?? '';
    $delivery_date = $_POST['delivery_date'] ?? null;
    $delivery_time = $_POST['delivery_time'] ?? null;
    $payment_confirmation = $_POST['payment_confirmation'] ?? '未入金';
    $payment_method = $_POST['payment_method'] ?? '未設定';

    try {
        $pdo->beginTransaction();

        // --- 顧客情報更新 ---
        $sql_c = "
            UPDATE customer_management
            SET name = :name,
                phone = :phone,
                email = :email,
                address = :address,
                street_address = :street_address,
                postal_code = :postal_code
            WHERE customer_management_id = (
                SELECT customer_management_id 
                FROM order_management 
                WHERE order_management_id = :order_id
            )
        ";
        $stmt_c = $pdo->prepare($sql_c);
        $stmt_c->execute([
            ':name' => $customer_name,
            ':phone' => $phone,
            ':email' => $email,
            ':address' => $address,
            ':street_address' => $street_address,
            ':postal_code' => $postal_code,
            ':order_id' => $order_id
        ]);

        // --- 注文情報更新 ---
        $sql1 = "
            UPDATE order_management
            SET delivery_date = :delivery_date,
                delivery_time = :delivery_time,
                payment_confirmation = :payment_confirmation,
                payment_method = :payment_method
            WHERE order_management_id = :order_id
        ";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute([
            ':delivery_date' => $delivery_date,
            ':delivery_time' => $delivery_time,
            ':payment_confirmation' => $payment_confirmation,
            ':payment_method' => $payment_method,
            ':order_id' => $order_id
        ]);

        $pdo->commit();

        header("Location: K6-order_detail.php?id={$order_id}&updated=1");
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("<div class='notification is-danger'>更新エラー: " . htmlspecialchars($e->getMessage()) . "</div>");
    }
}

// --- 注文情報取得 ---
try {
    $sql = "
        SELECT 
            OM.customer_management_id,
            OM.delivery_date,
            OM.delivery_time,
            OM.payment_confirmation,
            OM.payment_method,
            C.name,
            C.phone,
            C.email,
            C.address,
            C.street_address,
            C.postal_code
        FROM order_management OM
        LEFT JOIN customer_management C
            ON OM.customer_management_id = C.customer_management_id
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

require './header.php';
?>

<script>
function fetchAddress() {
    const postal = document.getElementById("postal_code").value.replace(/[^0-9]/g, "");
    if (postal.length !== 7) return;
    fetch("https://zipcloud.ibsnet.co.jp/api/search?zipcode=" + postal)
        .then(res => res.json())
        .then(data => {
            if (data.results) {
                const r = data.results[0];
                document.getElementById("address").value =
                    r.address1 + r.address2 + r.address3;
            }
        });
}
</script>

<div class="columns">
    <?php require '../config/left-menu.php'; ?>

    <div class="column" style="padding: 2rem;">
        <h1 class="title is-4">注文管理 / 注文編集</h1>

        <div class="box">
            <form method="post">
                <table class="table is-fullwidth">

                    <tr>
                        <th>顧客名</th>
                        <td><input class="input" type="text" name="customer_name"
                                   value="<?= htmlspecialchars($order['name']) ?>" required></td>
                    </tr>

                    <tr>
                        <th>電話番号</th>
                        <td><input class="input" type="text" name="phone"
                                   value="<?= htmlspecialchars($order['phone']) ?>" required></td>
                    </tr>

                    <tr>
                        <th>メールアドレス</th>
                        <td><input class="input" type="email" name="email"
                                   value="<?= htmlspecialchars($order['email']) ?>"></td>
                    </tr>

                    <tr>
                        <th>郵便番号</th>
                        <td><input class="input" type="text" id="postal_code" name="postal_code"
                                   value="<?= htmlspecialchars($order['postal_code']) ?>"
                                   onkeyup="fetchAddress()"></td>
                    </tr>

                    <tr>
                        <th>住所</th>
                        <td><input class="input" type="text" id="address" name="address"
                                   value="<?= htmlspecialchars($order['address']) ?>" required></td>
                    </tr>

                    <tr>
                        <th>番地</th>
                        <td><input class="input" type="text" name="street_address"
                                   value="<?= htmlspecialchars($order['street_address']) ?>" required></td>
                    </tr>

                    <tr>
                        <th>配達希望日</th>
                        <td><input class="input" type="date" name="delivery_date"
                                   value="<?= htmlspecialchars($order['delivery_date']) ?>"></td>
                    </tr>

                    <tr>
                        <th>配達希望時間</th>
                        <td>
                            <div class="select is-fullwidth">
                                <select name="delivery_time">
                                    <option value="">指定なし</option>
                                    <?php $times = ["08:00-10:00","11:00-13:00","14:00-16:00","17:00-19:00"];
                                    foreach ($times as $t): ?>
                                        <option value="<?= $t ?>" <?= $order['delivery_time']==$t?'selected':''; ?>>
                                            <?= $t ?>
                                        </option>
                                    <?php endforeach; ?>
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
                                    <?php
                                    $methods = ["クレジットカード決済","コンビニ決済","代金引換","キャリア決済","銀行振込","電子マネー決済","後払い決済","ID決済"];
                                    foreach ($methods as $m): ?>
                                        <option value="<?= $m ?>" <?= $order['payment_method']==$m?'selected':''; ?>>
                                            <?= $m ?>
                                        </option>
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
