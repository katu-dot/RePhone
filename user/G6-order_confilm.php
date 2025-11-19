<?php
session_start();
require './header.php'; 

// 入力していないで直接アクセスされた場合
if (!isset($_SESSION['order'])) {
    header("Location: entry.php"); // 入力画面へ戻す
    exit;
}

$order = $_SESSION['order'];
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>ご注文内容確認</title>
</head>

<body>

<div class="container">

    <h2>ご注文内容確認</h2>

    <div class="data-box">
        <p><strong>お名前：</strong> <?= htmlspecialchars($order['name'], ENT_QUOTES) ?></p>
        <p><strong>メールアドレス：</strong> <?= htmlspecialchars($order['email'], ENT_QUOTES) ?></p>
        <p><strong>電話番号：</strong> <?= htmlspecialchars($order['phone'], ENT_QUOTES) ?></p>
        <p><strong>郵便番号：</strong> <?= htmlspecialchars($order['postal_code'], ENT_QUOTES) ?></p>
        <p><strong>発送先住所：</strong> <?= htmlspecialchars($order['address'], ENT_QUOTES) ?></p>
        <p><strong>配達希望日：</strong>
            <?= $order['delivery_date'] === "" ? "希望なし" : htmlspecialchars($order['delivery_date'], ENT_QUOTES) ?>
        </p>
        <p><strong>配達希望時間：</strong>
            <?= $order['delivery_time'] === "" ? "希望なし" : htmlspecialchars($order['delivery_time'], ENT_QUOTES) ?>
        </p>
        <p><strong>お支払方法：</strong> <?= htmlspecialchars($order['payment_method'], ENT_QUOTES) ?></p>
    </div>

    <div class="btn-area">
        <button class="btn-back" onclick="history.back();">戻る</button>

        <form action="complete.php" method="post" style="display:inline;">
            <button class="btn-submit">注文確定 ▶</button>
        </form>
    </div>

</div>

<style>
body {
    background: #ffffff;
    margin: 0;
    font-family: "Yu Gothic", sans-serif;
    text-align: center;
}

.container {
    width: 92%;
    max-width: 480px;
    margin: 0 auto;
    padding-top: 20px;
}

h2 {
    font-weight: bold;
    color: #000;
    border-bottom: 1px solid #ccc;
    padding-bottom: 8px;
    margin-bottom: 20px;
}

.data-box {
    text-align: left;
    background: #fff;
    padding: 15px 18px;
    border-radius: 8px;
    border: 1px solid #e2e2e2;
    margin-bottom: 18px;
}

.data-box p {
    font-size: 15px;
    margin: 10px 0;
    border-bottom: 1px dashed #ddd;
    padding-bottom: 6px;
}

.btn-area {
    margin-top: 30px;
}

.btn-back,
.btn-submit {
    width: 43%;
    padding: 14px;
    border-radius: 6px;
    border: none;
    font-size: 15px;
    font-weight: bold;
}

.btn-back {
    background: #ddd;
}

.btn-submit {
    background: #8c88c3;
    color: #fff;
}
</style>

</body>
</html>
