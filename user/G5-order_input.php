<?php
session_start();

require './header.php'; 
require '../config/db-connect.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 入力値取得
    $_SESSION['order'] = [
        'name'          => $_POST['name'] ?? '',
        'email'         => $_POST['email'] ?? '',
        'phone'         => $_POST['phone'] ?? '',
        'postal_code'   => $_POST['postal_code'] ?? '',
        'address'       => $_POST['address'] ?? '',
        'delivery_date' => $_POST['delivery_date'] ?? '',
        'delivery_time' => $_POST['delivery_time'] ?? '',
        'payment_method'=> $_POST['payment_method'] ?? '',
    ];

    // 確認画面へ
    header("Location: confirm.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>ご注文情報入力</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h2 class="title">お客様情報</h2>

    <form method="post">

        <!-- お名前 -->
        <h3>お名前（姓名）</h3>
        <input type="text" name="name" placeholder="例：リフォン 太郎" required>

        <!-- メールアドレス -->
        <br><h3>メールアドレス</h3>
        <input type="email" name="email" placeholder="例：sales@rephone.co.jp" required>

        <!-- 電話番号 -->
        <br><h3>電話番号</h3>
        <input type="text" name="phone" placeholder="例：0820123456" required>

        <hr><h2 class="title">配送情報</h2>

        <!-- 郵便番号 -->
        <h3>郵便番号</h3>
        <input type="text" name="postal_code" placeholder="例：8120016" required>

        <!-- 住所 -->
        <br><h3>発送先住所</h3>
        <input type="text" name="address" placeholder="例：福岡市博多区博多駅前2丁目12-32" required>

        <!-- 配達希望日 -->
        <h3>配達希望日</h3>
        <select name="delivery_date">
            <option value="">希望日無し</option>
            <option value="today">今日</option>
            <option value="tomorrow">明日</option>
        </select>

        <!-- 配達希望時間 -->
        <br><h3>配達希望時間</h3>
        <select name="delivery_time">
            <option value="">希望時間無し</option>
            <option value="08:00-12:00">08:00-12:00</option>
            <option value="12:00-14:00">12:00-14:00</option>
            <option value="14:00-16:00">14:00-16:00</option>
            <option value="16:00-18:00">16:00-18:00</option>
        </select>

        <hr><h2 class="title">お支払方法</h2>

        <select name="payment_method" required>
            <option value="">お選びください</option>
            <option value="クレジットカード">クレジットカード</option>
            <option value="コンビニ支払い">コンビニ支払い</option>
            <option value="代金引換">代金引換</option>
        </select>
        <br>
<p><a href="G6-order_confilm.php" class="btn">確認画面へ ▶</a></p>
        <br>
    </form>
</div>

<style>
body {
    background: #ffffff;
    margin: 0;
    font-family: "Yu Gothic", sans-serif;
    text-align: center;
}

/* ここでデカ文字を全部黒に統一！ */
h2, h3, .title, .section-title {
    color: #000 !important;
}

.form-wrapper {
    width: 100%;
    display: flex;
    justify-content: center;
}

.form-area {
    width: 92%;
    max-width: 480px;
    background: #fff;
    padding: 18px 20px;
    border-radius: 8px;
    margin-top: 10px;
}

.section-title {
    font-size: 18px;
    font-weight: bold;
    border-bottom: 1px solid #ddd;
    padding-bottom: 6px;
    margin-top: 22px;
}

.input-label {
    display: block;
    margin-top: 20px;
    font-size: 15px;
    font-weight: bold;
}

.input-field {
    width: 100%;
    margin-top: 6px;
    padding: 12px;
    border: 1.6px solid #c5c5c5;
    border-radius: 6px;
    font-size: 15px;
}

.select-box {
    width: 100%;
    margin-top: 10px;
    padding: 12px;
    border: 1.6px solid #c5c5c5;
    border-radius: 6px;
    font-size: 15px;
    background: #fff;
}

.note {
    font-size: 12px;
    color: #555;
    margin-top: 5px;
}

.btn {
    width: 40%;
    padding: 10px;
    background: #8c88c3;
    color: #fff;
    border: none;
    border-radius: 8px;
    margin-top: 30px;
    font-size: 16px;
    font-weight: bold;
}
</style>

</body>
</html>
