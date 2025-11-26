<?php
session_start();
require './header.php';

// 入力画面を通らずに来た場合は戻す
if (!isset($_SESSION['order'])) {
    header("Location: G6-order_input.php");
    exit;
}

$order = $_SESSION['order'];

// 表示変換
$delivery_date_label = [
    "" => "希望無し",
    "today" => "今日",
    "tomorrow" => "明日"
];

$delivery_time_label = [
    "" => "希望時間無し",
    "08:00-12:00" => "08:00〜12:00",
    "12:00-14:00" => "12:00〜14:00",
    "14:00-16:00" => "14:00〜16:00",
    "16:00-18:00" => "16:00〜18:00",
];
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>ご注文確認</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>

body {
    margin: 0;
    font-family: "Yu Gothic", sans-serif;
    background: #fff;
    text-align: center;
}

.container {
    width: 92%;
    max-width: 480px;
    margin: 0 auto;
    padding-bottom: 120px;
}

/* 上部テキスト */
.notice {
    margin-top: 20px;
    font-size: 14px;
    color: #333;
    font-weight: bold;
    text-align: left;
}

/* データ表示ブロック */
.data-box {
    text-align: left;
    background: #fff;
    margin-top: 10px;
}

.data-box p {
    font-size: 15px;
    margin: 15px 0;
    border-bottom: 1px dashed #ddd;
    padding-bottom: 6px;
}

.data-box strong {
    font-weight: bold;
    display: block;
    margin-bottom: 3px;
}

/* 下部固定ボタン */
.bottom-area {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background: #fff;
    padding: 15px 0;
    border-top: 1px solid #ddd;
}

.btn-submit {
    width: 90%;
    max-width: 420px;
    padding: 14px;
    background: #e53935;
    color: #fff;
    border-radius: 8px;
    border: none;
    font-size: 16px;
    font-weight: bold;
}

</style>
</head>

<body>

<div class="container">

    <p class="notice">入力内容に誤りが無いか、ご確認ください。</p>

    <div class="data-box">
        <p><strong>お名前</strong><?= htmlspecialchars($order['name'], ENT_QUOTES) ?></p>
        <p><strong>メールアドレス</strong><?= htmlspecialchars($order['email'], ENT_QUOTES) ?></p>
        <p><strong>電話番号</strong><?= htmlspecialchars($order['phone'], ENT_QUOTES) ?></p>
        <p><strong>郵便番号</strong><?= htmlspecialchars($order['postal_code'], ENT_QUOTES) ?></p>
        <p><strong>発送先住所</strong><?= htmlspecialchars($order['address'], ENT_QUOTES) ?></p>
        <p><strong>配達希望日</strong><?= $delivery_date_label[$order['delivery_date']] ?></p>
        <p><strong>配達希望時間</strong><?= $delivery_time_label[$order['delivery_time']] ?></p>
        <p><strong>お支払方法</strong><?= htmlspecialchars($order['payment_method'], ENT_QUOTES) ?></p>
    </div>

</div>

<div class="bottom-area">
    <form action="complete.php" method="post">
        <button class="btn-submit">注文を確定する</button>
    </form>
</div>

</body>
</html>
