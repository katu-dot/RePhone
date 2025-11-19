<?php
require '../config/db-connect.php';

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo '<div class="notification is-danger">不正なアクセスです。</div>';
        exit;
    }
    $customer_management_id = (int)$_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM customer_management WHERE customer_management_id = :id");
    $stmt->bindValue(':id', $customer_management_id, PDO::PARAM_INT);
    $stmt->execute();
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        echo '<div class="notification is-warning">該当する顧客情報が見つかりません。</div>';
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $postal_code = $_POST['postal_code'] ?? '';
        $address = $_POST['address'] ?? '';

        $stmt = $pdo->prepare("
            UPDATE customer_management 
            SET name = :name, email = :email, phone = :phone, postal_code = :postal_code, address = :address
            WHERE customer_management_id = :id
        ");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':postal_code' => $postal_code,
            ':address' => $address,
            ':id' => $customer_management_id
        ]);

        header("Location: K3-customer_master.php?message=顧客情報の編集が完了しました");
        exit;
    }

} catch (PDOException $e) {
    echo '<div class="notification is-danger">接続エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}

require 'header.php';
?>

<div class="columns">
<?php require '../config/left-menu.php'; ?>

<div class="column" style="padding: 2rem;">
    <h1 class="title is-4">顧客管理／顧客マスター／顧客編集</h1>
    <h2 class="subtitle is-6">顧客情報を編集してください</h2>

    <form method="POST">
        <div class="field">
            <label class="label">氏名</label>
            <div class="control">
                <input class="input" type="text" name="name" value="<?= htmlspecialchars($customer['name']) ?>" required>
            </div>
        </div>

        <div class="field">
            <label class="label">メールアドレス</label>
            <div class="control">
                <input class="input" type="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>" required>
            </div>
        </div>

        <div class="field">
            <label class="label">電話番号</label>
            <div class="control">
                <input class="input" type="text" name="phone" value="<?= htmlspecialchars($customer['phone']) ?>">
            </div>
        </div>

        <div class="field">
            <label class="label">郵便番号</label>
            <div class="control">
                <!-- ▼ ここだけ追記（id と onkeyup） -->
                <input class="input" type="text" id="postal_code" name="postal_code"
                       value="<?= htmlspecialchars($customer['postal_code']) ?>"
                       onkeyup="fetchAddress()">
            </div>
        </div>

        <div class="field">
            <label class="label">住所</label>
            <div class="control">
                <!-- ▼ ここだけ追記（id を追加） -->
                <input class="input" type="text" id="address" name="address"
                       value="<?= htmlspecialchars($customer['address']) ?>">
            </div>
        </div>

        <div class="field is-grouped mt-4">
            <div class="control">
                <button type="submit" class="button is-success">更新</button>
            </div>
            <div class="control">
                <a href="K4-customer_detail.php?id=<?= $customer_management_id ?>" class="button is-light">戻る</a>
            </div>
        </div>
    </form>
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
        });
}
</script>

<?php require 'footer.php'; ?>
