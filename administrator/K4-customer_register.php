<?php
require '../config/db-connect.php';
session_start();

// --- DB接続 ---
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo '<div class="notification is-danger">接続エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}

$message = '';
$message_class = 'is-info';

// --- フォーム送信処理 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $postal_code = $_POST['postal_code'] ?? '';
    $street_address = $_POST['street_address'] ?? ''; // ★追加
    $address = $_POST['address'] ?? '';
    $registration_date = date('Y-m-d H:i:s'); // 登録日時

    if ($name && $email && $phone && $postal_code && $address) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO customer_management 
                    (name, email, phone, postal_code, street_address, address, registration_date)
                VALUES
                    (:name, :email, :phone, :postal_code, :street_address, :address, :registration_date)
            ");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':postal_code' => $postal_code,
                ':street_address' => $street_address, // ★追加
                ':address' => $address,
                ':registration_date' => $registration_date
            ]);

            $customer_id = $pdo->lastInsertId();
            header("Location: K4-customer_detail.php?id={$customer_id}&message=registered");
            exit;

        } catch (PDOException $e) {
            $message = '登録に失敗しました。エラー: ' . $e->getMessage();
            $message_class = 'is-danger';
        }
    } else {
        $message = 'すべての項目を入力してください。';
        $message_class = 'is-danger';
    }
}

require 'header.php';
?>

<div class="columns">
<?php require '../config/left-menu.php'; ?>

<div class="column" style="padding: 2rem;">
    <h1 class="title is-4">顧客管理／顧客マスター／新規登録</h1>
    <h2 class="subtitle is-6">顧客登録フォーム</h2>

    <?php if ($message): ?>
        <div class="notification <?= $message_class; ?>"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="box">
            <div class="field">
                <label class="label">氏名</label>
                <div class="control">
                    <input class="input" type="text" name="name" placeholder="氏名を入力" required>
                </div>
            </div>

            <div class="field">
                <label class="label">メールアドレス</label>
                <div class="control">
                    <input class="input" type="email" name="email" placeholder="メールアドレスを入力">
                </div>
            </div>

            <div class="field">
                <label class="label">電話番号</label>
                <div class="control">
                    <input class="tel input" type="text" name="phone" placeholder="電話番号を入力" required>
                </div>
            </div>

            <div class="field">
                <label class="label">郵便番号</label>
                <div class="control">
                    <input class="input" type="text" id="postal_code" name="postal_code"
                           placeholder="郵便番号を入力" onkeyup="fetchAddress()" required>
                </div>
            </div>

            <div class="field">
                <label class="label">住所</label>
                <div class="control">
                    <textarea class="textarea" id="address" name="address" rows="2"
                              placeholder="住所を入力" required></textarea>
                </div>
            </div>

            <!-- 番地 -->
            <div class="field">
                    <label class="label">番地</label>
                    <div class="control">
                        <input class="input" type="text" name="street_address"
                            placeholder="◯丁目◯番◯号 など" >
                    </div>
            </div>
        </div>

        <div class="buttons mt-3">
            <a href="K3-customer_master.php" class="button is-light">顧客一覧へ戻る</a>
            <button type="submit" class="button is-info">登録</button>
        </div>
    </form>
</div>
</div>

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
