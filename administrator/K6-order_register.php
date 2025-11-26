<?php
// --- ▼ デバッグ用 ▼ ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
// --- ▲ デバッグ用 ▲ ---

session_start();
require '../config/db-connect.php';

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

    // 顧客情報
    $name           = $_POST['customer_name'] ?? '';
    $phone          = $_POST['phone'] ?? '';
    $email          = $_POST['email'] ?? '';
    $postal_code    = $_POST['postal_code'] ?? '';
    $address        = $_POST['address'] ?? '';
    $street_address = $_POST['street_address'] ?? '';

    // 商品情報
    $product_management_id = intval($_POST['product_management_id']);

    // 配送情報
    $delivery_date  = $_POST['delivery_date'] ?? null;
    $delivery_time  = $_POST['delivery_time'] ?? null;

    // 支払い情報
    $payment_confirmation = $_POST['payment_confirmation'] ?? '未入金';
    $payment_method       = $_POST['payment_method'] ?? '未設定';

    try {
        $pdo->beginTransaction();

        // ▼ 新規顧客を customer_management に登録
        $stmt_cust = $pdo->prepare("
            INSERT INTO customer_management
            (name, phone, email, postal_code, address, street_address)
            VALUES (:name, :phone, :email, :postal_code, :address, :street_address)
        ");
        $stmt_cust->execute([
            ':name'        => $name,
            ':phone'       => $phone,
            ':email'       => $email,
            ':postal_code' => $postal_code,
            ':address'     => $address,
            ':street_address' => $street_address
        ]);
        $customer_management_id = $pdo->lastInsertId();

        // ▼ order_management 登録
        $stmt1 = $pdo->prepare("
            INSERT INTO order_management
            (customer_management_id, order_date, payment_confirmation, delivery_date, delivery_time, payment_method)
            VALUES (:customer_id, NOW(), :payment_confirmation, :delivery_date, :delivery_time, :payment_method)
        ");
        $stmt1->execute([
            ':customer_id'          => $customer_management_id,
            ':payment_confirmation' => $payment_confirmation,
            ':delivery_date'        => $delivery_date,
            ':delivery_time'        => $delivery_time,
            ':payment_method'       => $payment_method
        ]);
        $order_management_id = $pdo->lastInsertId();

        // ▼ order_detail_management 登録
        $stmt2 = $pdo->prepare("
            INSERT INTO order_detail_management
            (order_management_id, product_management_id)
            VALUES (:order_management_id, :product_management_id)
        ");
        $stmt2->execute([
            ':order_management_id'  => $order_management_id,
            ':product_management_id'=> $product_management_id
        ]);

        $pdo->commit();

        // ▼ ここでリダイレクトして「登録完了メッセージ」を表示
        header("Location: K6-order_detail.php?id={$order_management_id}&registered=1");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("<div class='notification is-danger'>登録エラー: " . htmlspecialchars($e->getMessage()) . "</div>");
    }
}

// -----------------------------------------------------------
// ▼ 商品一覧取得（検索対応）
// -----------------------------------------------------------
$keyword_product = $_GET['keyword_product'] ?? '';
try {
    $stmt = $pdo->prepare("
        SELECT 
            PM.product_management_id,
            PM.stock,
            P.product_name,
            P.price,
            S.shipping_date
        FROM product_management PM
        INNER JOIN product P ON PM.product_id = P.product_id
        LEFT JOIN shipping S ON PM.shipping_id = S.shipping_id
        WHERE P.product_name LIKE :keyword
        ORDER BY P.product_name ASC
    ");
    $stmt->execute([':keyword' => "%{$keyword_product}%"]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<div class='notification is-danger'>商品取得エラー: " . htmlspecialchars($e->getMessage()) . "</div>");
}

// HTML開始
require './header.php';
?>

<div class="columns">
<?php require '../config/left-menu.php'; ?>

<div class="column" style="padding: 2rem;">
<h1 class="title is-4">注文管理／注文登録</h1>

<!-- ▼ 登録完了メッセージ表示 -->
<?php if (!empty($_GET['registered'])): ?>
    <div class="notification is-success mt-4">注文の登録が完了しました。</div>
<?php endif; ?>

<div class="box">

<!-- ▼ 商品検索フォーム -->
<form method="get" class="mb-4">
    <label class="label">商品検索</label>
    <div class="field has-addons">
        <div class="control is-expanded">
            <input class="input" 
                   type="text" 
                   name="keyword_product" 
                   value="<?= htmlspecialchars($keyword_product); ?>"
                   placeholder="商品名で検索">
        </div>
        <div class="control">
            <button class="button is-info">検索</button>
        </div>
    </div>
</form>

<!-- ▼ 注文登録フォーム -->
<form action="" method="post">

<table class="table is-fullwidth">

<tr>
    <th>顧客名</th>
    <td><input class="input" type="text" name="customer_name" placeholder="氏名を入力" required></td>
</tr>

<tr>
    <th>電話番号</th>
    <td><input class="input" type="text" name="phone" placeholder="電話番号を入力" required></td>
</tr>

<tr>
    <th>メールアドレス</th>
    <td><input class="input" type="email" name="email" placeholder="メールアドレスを入力"></td>
</tr>

<tr>
    <th>郵便番号</th>
    <td>
        <input 
            class="input" 
            type="text" 
            name="postal_code" 
            id="postal_code"
            placeholder="郵便番号を入力"
            onkeyup="fetchAddress()"
        >
    </td>
</tr>

<tr>
    <th>住所</th>
    <td>
        <input 
            class="input"
            type="textarea"
            name="address"
            id="address"
            placeholder="住所を入力"
            required
        >
    </td>
</tr>

<tr>
    <th>番地</th>
    <td>
        <input 
            class="input"
            type="text"
            name="street_address"
            placeholder="番地を入力"
            required
        >
    </td>
</tr>

<tr>
    <th>商品選択</th>
    <td>
        <div class="select is-fullwidth">
            <select name="product_management_id" required>
                <option value="">選択してください</option>
                <?php foreach ($products as $p): ?>
                    <?php 
                        $stock = (int)($p['stock'] ?? 0);
                        $label = htmlspecialchars($p['product_name']) .
                                 " (" . number_format($p['price']) . "円)" .
                                 " | " . ($p['shipping_date'] ?? '発送日未設定') .
                                 " | 在庫: {$stock}";
                    ?>
                    <option value="<?= $p['product_management_id']; ?>" <?= $stock === 0 ? 'disabled style="color:red;"' : ''; ?>>
                        <?= $label; ?>
                    </option>
                <?php endforeach; ?>
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

<!-- ▼ 住所自動補完スクリプト（ZipCloud） -->
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
        })
        .catch(err => console.log(err));
}
</script>

<?php require './footer.php'; ?>
