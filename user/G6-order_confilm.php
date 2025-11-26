<?php
session_start();
require './header.php';
require '../config/db-connect.php';

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
    background: #f6f6f6;
}

.header-title {
    background: #ffffff;
    padding: 15px;
    font-size: 18px;
    font-weight: bold;
    text-align: center;
    border-bottom: 1px solid #ddd;
    color: #000;
}

.container {
    width: 92%;
    max-width: 480px;
    margin: 15px auto;
    padding-bottom: 120px;
}

/* ボックス */
.data-box {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 10px;
    overflow: hidden;
}

/* 行デザイン */
.row {
    display: flex;
    justify-content: space-between;
    padding: 14px 12px;
    font-size: 15px;
    border-bottom: 1px solid #eee;
    background: #fafafa;
    color: #000;
}

.row:nth-child(even) {
    background: #fff;
}

.row strong {
    font-weight: bold;
    color: #000;   /* ←追加 */
}
.row span {
    color: #000;   /* ←追加 */
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
    text-align: center;
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

<div class="header-title">入力内容に誤りがないか、ご確認ください</div>

<div class="container">

    <div class="data-box">
        <div class="row"><strong>お名前</strong><span><?= htmlspecialchars($order['name'], ENT_QUOTES) ?></span></div>
        <div class="row"><strong>メールアドレス</strong><span><?= htmlspecialchars($order['email'], ENT_QUOTES) ?></span></div>
        <div class="row"><strong>電話番号</strong><span><?= htmlspecialchars($order['phone'], ENT_QUOTES) ?></span></div>
        <div class="row"><strong>郵便番号</strong><span><?= htmlspecialchars($order['postal_code'], ENT_QUOTES) ?></span></div>
        <div class="row"><strong>発送先住所</strong><span><?= htmlspecialchars($order['address'], ENT_QUOTES) ?></span></div>
        <div class="row"><strong>配達希望日</strong><span><?= $delivery_date_label[$order['delivery_date']] ?></span></div>
        <div class="row"><strong>配達希望時間</strong><span><?= $delivery_time_label[$order['delivery_time']] ?></span></div>
        <div class="row"><strong>お支払方法</strong><span><?= htmlspecialchars($order['payment_method'], ENT_QUOTES) ?></span></div>
    </div>

</div>

<div class="bottom-area">
    <form action="complete.php" method="post">
        <button class="btn-submit">注文を確定する</button>
    </form>
</div>

</body>
</html>
