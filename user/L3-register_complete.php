<?php
session_start();
require '../config/db-connect.php'; 
require 'header.php';

$error_message = [];
$success_message = "";
$user_id = "";

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<p style='color:red; text-align:center;'>DB接続エラーが発生しました。</p>");
}

// POST値取得
$user_name = $_POST['user_name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$phone = $_POST['phone'] ?? '';
$postal_code = $_POST['postal_code'] ?? '';
$address = $_POST['address'] ?? '';
$street_address = $_POST['street_address'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // バリデーション
    if (empty($user_name)) $error_message[] = "氏名を入力してください。";
    if (empty($email)) $error_message[] = "メールアドレスを入力してください。";
    if (empty($password)) $error_message[] = "パスワードを入力してください。";
    if (empty($phone)) $error_message[] = "電話番号を入力してください。";
    if (empty($postal_code)) $error_message[] = "郵便番号を入力してください。";
    if (empty($address)) $error_message[] = "住所を入力してください。";
    if (empty($street_address)) $error_message[] = "番地を入力してください。";

    if ($password !== $password_confirm) {
        $error_message[] = "パスワードが確認用と一致しません。";
    } elseif (strlen($password) < 4) {
        $error_message[] = "パスワードは半角4文字以上で入力してください。";
    }

    // メール重複チェック
    if (empty($error_message)) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM user WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $error_message[] = "このメールアドレスは既に登録されています。";
        }
    }

    // データ登録
    if (empty($error_message)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // userテーブル登録
            $stmt = $pdo->prepare(
                'INSERT INTO user (user_name,email,password,phone,postal_code,address,street_address)
                 VALUES (?,?,?,?,?,?,?)'
            );
            $stmt->execute([$user_name, $email, $hashed_password, $phone, $postal_code, $address, $street_address]);

            // 登録された user_id の取得
            $user_id = $pdo->lastInsertId();

            // セッションに user_id を保存
            $_SESSION['user_id'] = $user_id;

            // customer_management に登録（存在する場合のみ）
            $stmt2 = $pdo->prepare(
                'INSERT INTO customer_management
                (name,email,phone,address,street_address,postal_code,registration_date)
                 VALUES (?,?,?,?,?,?,NOW())'
            );
            $stmt2->execute([$user_name,$email,$phone,$address,$street_address,$postal_code]);

            $success_message = "会員登録が完了しました。ログインページへ移動します。";

        } catch (PDOException $e) {
            $error_message[] = "登録中にエラーが発生しました: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RePhone 会員登録完了</title>
<style>
body {
    font-family: 'Helvetica Neue', 'Arial', 'メイリオ', sans-serif;
    background-color: #fff;
    color: #000;
    margin: 0;
    padding: 0;
    text-align: center;
}
.register-container {
    width: 90%;
    max-width: 400px;
    margin: 0 auto;
    padding-top: 30px; 
    padding-bottom: 50px;
    text-align: left;
}
.register-container h2 {
    font-size: 1.8em;
    font-weight: bold;
    margin-bottom: 5px;
    text-align: center;
}
.error { color: red; font-weight: bold; margin-bottom: 15px; text-align: center; }
.success { color: green; font-weight: bold; margin-bottom: 15px; text-align: center; }
.bottom-link {
    display: block;
    font-weight: bold;
    text-decoration: none;
    color: #000;
    text-align: center;
    margin-top: 20px;
}
</style>
</head>
<body>

<div class="register-container">
    <h2>会員登録完了</h2>

    <?php if (!empty($error_message)): ?>
        <?php foreach ($error_message as $msg): ?>
            <p class="error">⚠️ <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <p class="success">✅ <?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="box" style="margin-top:20px;">
            <p><strong>ログインID：</strong> 
                <span style="color:red; font-weight:bold;">
                    <?= htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8') ?><br>
                    ※ログインIDは忘れないようにメモしておいてください
                </span>
            </p>

            <p><strong>氏名：</strong> <?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>メール：</strong> <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>電話番号：</strong> <?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>郵便番号：</strong> <?= htmlspecialchars($postal_code, ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>住所：</strong> <?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>番地：</strong> <?= htmlspecialchars($street_address, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php endif; ?>

    <a href="L1-login.php" class="bottom-link">ログインページへ</a>
    <a href="G1-top.php" class="bottom-link">ホームに戻る</a>
</div>

<?php require 'footer.php'; ?>
</body>
</html>
