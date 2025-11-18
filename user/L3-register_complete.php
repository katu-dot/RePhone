<?php
session_start();
require '../config/db-connect.php';
require 'header.php';

// POSTで受け取ったデータを取得
$user_name = $_POST['user_name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$phone = $_POST['phone'] ?? '';
$postal_code = $_POST['postal_code'] ?? '';
$address = $_POST['address'] ?? '';

// エラーチェック（ここは前ページで済んでる想定）
if (empty($user_name) || empty($email) || empty($password)) {
    header('Location: L2-register.php');
    exit();
}

// パスワードハッシュ化
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// データベース登録
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = $pdo->prepare('INSERT INTO user (user_name, email, password, phone, postal_code, address) VALUES (?, ?, ?, ?, ?, ?)');
    $sql->execute([$user_name, $email, $hashed_password, $phone, $postal_code, $address]);

    // 登録したユーザーIDを取得
    $user_id = $pdo->lastInsertId();

} catch (PDOException $e) {
    exit('データベースエラー: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RePhone 登録完了</title>
<style>
    body {
        font-family: 'Helvetica Neue', 'メイリオ', sans-serif;
        background-color: #fff;
        color: #000;
        margin: 0;
        padding: 0;
        text-align: center;
    }
    .complete-container {
        max-width: 400px;
        margin: 0 auto;
        padding: 40px 20px;
    }
    h2 {
        font-size: 1.4em;
        font-weight: bold;
        margin-bottom: 25px;
    }
    .info {
        text-align: left;
        margin-bottom: 50px;
    }
    .info p {
        margin: 8px 0;
        line-height: 1.5;
    }
    .info span {
        font-weight: bold;
        display: inline-block;
        margin-right: 10px;
    }
    a {
        display: block;
        font-weight: bold;
        text-decoration: none;
        color: #4a3bdc;
        margin: 20px 0;
    }
    a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>
    <div class="complete-container">
        <h2>登録が完了しました。</h2>

        <div class="info">
            <p><span>ログインID</span><?= htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8') ?></p>
            <p><span>お名前（氏名）</span><?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?></p>
            <p><span>メールアドレス</span><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <a href="G1-top.php">ホームに戻る</a>
        <a href="L1-login.php">ログインはこちらから</a>
    </div>
    <?php require 'footer.php'; ?>
</body>
</html>
