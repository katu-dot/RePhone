<?php
session_start();
require '../config/db-connect.php';
require 'header.php';

// 未ログインならログインへ
if (!isset($_SESSION['user_id'])) {
    header('Location: L1-login.php');
    exit();
}

$pdo = new PDO($connect, USER, PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = "";    // 成功メッセージ
$error = "";      // エラーメッセージ

// ▼ POST送信されたときだけパスワード更新処理を実行
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $current_pass  = $_POST['current_password'] ?? '';
    $new_pass      = $_POST['new_password'] ?? '';
    $confirm_pass  = $_POST['confirm_password'] ?? '';
    $user_id       = $_SESSION['user_id'];

    // 入力チェック
    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $error = "すべての項目を入力してください。";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "新しいパスワードが一致しません。";
    } else {
        // 現在のパスワード確認
        $sql = $pdo->prepare("SELECT password FROM user WHERE user_id = ?");
        $sql->execute([$user_id]);
        $user = $sql->fetch();

        if (!$user || !password_verify($current_pass, $user['password'])) {
            $error = "現在のパスワードが正しくありません。";
        } else {
            // 更新処理
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE user SET password = ? WHERE user_id = ?");
            $update->execute([$hashed, $user_id]);

            $message = "パスワードの変更が完了しました。";
        }
    }
}
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
.success {
    color: green;
    font-weight: bold;
    margin-bottom: 20px;
}
.error {
    color: red;
    font-weight: bold;
    margin-bottom: 20px;
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
.pass_button {
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
a {
    color: #fff;
    text-decoration: none;
}
.toggle-password {
  position: absolute;
  right: 20px;
  top: 50%;
  transform: translateY(-90%);
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
}
</style>
</head>
<body>
<div class="container0">
    <h2>パスワード変更</h2>

    <!-- 成功メッセージ -->
    <?php if (!empty($message)): ?>
        <p class="success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <!-- エラーメッセージ -->
    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <!-- フォーム（成功時も表示のまま） -->
    <form action="" method="POST">
        <label for="current-password">現在のパスワード</label>
        <input type="password" id="current-password" name="current_password" required>
        <button type="button" id="togglePassword1" class="toggle-password">
          <img src="../img/icon_show_pwd.png" alt="パスワード表示切替" style="width: 20px;" id="toggleIcon1">
        </button>
        
        <label for="new-password">新しいパスワード</label>
        <input type="password" id="new-password" name="new_password" required>
        <button type="button" id="togglePassword2" class="toggle-password">
          <img src="../img/icon_show_pwd.png" alt="パスワード表示切替" style="width: 20px;" id="toggleIcon2">
        </button>
        
        <label for="confirm-password">新しいパスワード（確認）</label>
        <input type="password" id="confirm-password" name="confirm_password" required>
        <button type="button" id="togglePasswor3" class="toggle-password">
          <img src="../img/icon_show_pwd.png" alt="パスワード表示切替" style="width: 20px;" id="toggleIcon3">
        </button>
        
        <button type="submit" class="pass_button">パスワードを変更する</button>
        <?php if (!empty($message)): ?>
            <button type="submit" class="pass_button"><a href="L4-mypage.php">マイページへ戻る</a></button>
        <?php endif; ?>
    </form>
</div>
<script>
  const togglePassword = document.getElementById('togglePassword1');
  const togglePassword = document.getElementById('togglePassword2');
  const togglePassword = document.getElementById('togglePassword3');
  const passwordField = document.getElementById('current-password');
  const passwordField = document.getElementById('new-password');
  const passwordField = document.getElementById('confirm-password');
  const toggleIcon = document.getElementById('toggleIcon1');
  const toggleIcon = document.getElementById('toggleIcon2');
  const toggleIcon = document.getElementById('toggleIcon3');

  togglePassword.addEventListener('click', () => {
    const isPassword = passwordField.type === 'password';

    passwordField.type = isPassword ? 'text' : 'password';

    toggleIcon.src = isPassword
      ? '../img/icon_hide_pwd.png'  // パスワードを“表示中” → 隠すアイコンに切り替え
      : '../img/icon_show_pwd.png'; // パスワードを“非表示中” → 見せるアイコンに戻す
  });
</script>
<?php require 'footer.php'; ?>
</body>
</html>
