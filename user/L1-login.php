<?php
session_start();
require '../config/db-connect.php';

// すでにログインしている場合
/*if (isset($_SESSION['user_id'])) {
    header('Location: user-home.php');
    exit();
}*/

$pdo = new PDO($connect, USER, PASS);
$error_message = "";

if (isset($_POST['user_id']) && isset($_POST['password'])) {
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];

    $sql = $pdo->prepare('SELECT * FROM user WHERE user_id = ?');
    $sql->execute([$user_id]);
    $user = $sql->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['user_name'];
        // 戻るURLがある場合はそこへ、なければマイページへ
        if (isset($_SESSION['return_to'])) {
            $redirect = $_SESSION['return_to'];
            unset($_SESSION['return_to']); // 一度使ったら消す
            header("Location: $redirect");
        } else {
            header('Location: L4-mypage.php'); // デフォルト
        }
        exit();
    } else {
        $error_message = "ログインIDまたはパスワードが正しくありません。";
    }
}

// header.php はここで読み込む（HTML出力は最後）
require 'header.php';

if ($user && $password === $user['password']) {
    $_SESSION['customer'] = [
        'id' => $user['user_id'],
        'name' => $user['user_name']
    ];

    
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>RePhone ユーザーログイン</title>
  <!--<link rel="stylesheet" href="../config/login-style.css">-->
  <style>
    /* 全体設定 */
body {
  font-family: 'Helvetica Neue', 'Arial', 'メイリオ', sans-serif;
  background-color: #fff;
  color: #000;
  margin: 0;
  padding: 0;
  text-align: center;
}

/* ロゴ */
.login-container img {
  width: 160px;
  margin: 30px auto 10px;
  display: block;
}

/* ログインボックス */
.login-container {
  width: 90%;
  max-width: 350px;
  margin: 0 auto;
  padding: 20px 15px 40px;
  background-color: #fff;
  border-radius: 10px;
}

/* タイトル */
.login-container h2 {
  font-size: 1.8em;
  font-weight: bold;
  margin-bottom: 25px;
}

/* ラベル */
.login-form label {
  display: block;
  text-align: left;
  font-weight: bold;
  margin-bottom: 5px;
}

/* 入力フォーム */
.login-form input[type="text"],
.login-form input[type="email"],
.login-form input[type="password"] {
  width: 94%;
  padding: 10px;
  margin-bottom: 20px;
  border: none;
  border-radius: 5px;
  background-color: #ddd;
}

/* パスワード切替ボタン */
.password-wrapper {
  position: relative;
}

.toggle-password {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-90%);
  background: none;
  border: none;
  font-size: 1.2em;
  cursor: pointer;
}

/* ログインボタン */
.login-form button[type="submit"] {
  width: 60%;
  padding: 10px;
  background-color: #a19ae0;
  border: none;
  border-radius: 5px;
  color: white;
  font-weight: bold;
  font-size: 1em;
  cursor: pointer;
}

.login-form button[type="submit"]:hover {
  background-color: #8b84d8;
}

/* 下部リンク */
.login-container a {
  display: block;
  margin-top: 15px;
  font-weight: bold;
  text-decoration: none;
}

.login-container a:nth-of-type(1) {
  color: #4a2dbd;
}

.login-container a:nth-of-type(2) {
  color: #2a0098;
}

/* エラーメッセージ */
.error {
  color: red;
  font-weight: bold;
  margin-bottom: 15px;
}
    </style>
</head>
<body>
  <div class="login-container">
    <!--<img src="../img/user-logo.jpg" alt="">-->
    <h2>ログイン</h2>

    <?php if (!empty($error_message)): ?>
      <p class="error"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form action="login-user.php" method="post" class="login-form">
      <label for="email">ログインID</label>
      <input type="text" id="email" name="email" required><br>

      <label for="password">パスワード</label>
      <div class="password-wrapper">
        <input type="password" id="password" name="password" required>
        <button type="button" id="togglePassword" class="toggle-password">👁</button>
      </div>
      <button type="submit">ログイン</button>
    </form>
    <div>
      <a href="G1-top.php">ホームに戻る</a>
      <a href="L2-register_input.php">新規会員登録はこちら</a>
      <!--<a href="L3-forgot-password.php">パスワードをお忘れの方はこちら</a>-->
  </div>

  <script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');

    togglePassword.addEventListener('click', () => {
      const type = passwordField.type === 'password' ? 'text' : 'password';
      passwordField.type = type;
      togglePassword.textContent = type === 'password' ? '👁' : '🙈';
    });
  </script>
  <?php require 'footer.php'; ?>
</body>
</html>
