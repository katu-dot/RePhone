<?php
// セッション開始
session_start();
require '../config/db-connect.php';

// すでにログインしている場合はリダイレクト
if (isset($_SESSION['admin_id'])) {
    header('Location: K2-home.php');
    exit();
}

$error_message = "";

// フォーム送信時の処理
if (isset($_POST['admin_id']) && isset($_POST['password'])) {
    $admin_id = $_POST['admin_id'];
    $password = $_POST['password'];

    // DB接続と照合
    $sql = $pdo->prepare('SELECT * FROM admin WHERE admin_id = ?');
    $sql->execute([$admin_id]);
    $admin = $sql->fetch();

    // パスワード照合
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        header('Location: K2-home.php');
        exit();
    } else {
        $error_message = "管理者IDまたはパスワードが正しくありません。";
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RePhone_staff ログイン</title>
  <link rel="stylesheet" href="../config/login-style.css">
</head>
<body>
  <div class="login-container">
    <img src="../img/admin-logo.png" alt="">
    <h2>ログイン</h2>

    <!-- エラーメッセージ -->
    <?php if (!empty($error_message)): ?>
      <p class="error"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form action="login-admin.php" method="post" class="login-form">
      <label for="admin_id">管理者ID</label>
      <input type="text" id="admin_id" name="admin_id" required><br>

      <label for="password">パスワード</label>
      <div class="password-wrapper">
        <input type="password" id="password" name="password" required>
        <button type="button" id="togglePassword" class="toggle-password">👁</button>
      </div>
      <button type="submit">ログイン</button>
    </form>
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
</body>
</html>
