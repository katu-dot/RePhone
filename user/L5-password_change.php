<?php
require '../config/db-connect.php';
require 'header.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RePhone パスワード変更</title>
<style>
/* ===== ベースレイアウト ===== */
body {
    font-family: "Helvetica Neue", "メイリオ", sans-serif;
    margin: 0;
    padding: 0;
    background: #fff;
    color: #000;
}
/* ===== パスワード変更部分 ===== */
.container0 {
    max-width: 400px;
    margin: 0 10%;
    padding: 30px 20px;
}
h2 {
    font-size: 1.5em;
    font-weight: bold;
    border-bottom: 3px solid #000;
    display: inline-block;
    margin-bottom: 30px;
}
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}
label {
    font-weight: bold;
}
input[type="password"] {
    padding: 8px;
    font-size: 1em;
    border: 1px solid #ccc;
    border-radius: 4px;
}
button {
    padding: 10px;
    font-size: 1em;
    background-color: #000;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
button:hover {
    background-color: #333;
}

</style>
</head>
<body>
<div class="container0">
    <h2>パスワード変更</h2>
    <form action="L4-mypage.php" method="POST">
        <label for="current-password">現在のパスワード</label>
        <input type="password" id="current-password" name="current_password" required>
        
        <label for="new-password">新しいパスワード</label>
        <input type="password" id="new-password" name="new_password" required>
        
        <label for="confirm-password">新しいパスワード（確認）</label>
        <input type="password" id="confirm-password" name="confirm_password" required>
        
        <button type="submit">パスワードを変更する</button>
    </form>
</div>
<?php require 'footer.php'; ?>
</body>
</html>