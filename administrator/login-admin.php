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

    <form action="login.php" method="post" class="login-form">
      <label for="admin_id">管理者ID</label>
      <input type="text" id="admin_id" name="admin_id" required><br>

      <label for="password">パスワード</label>
      <input type="password" id="password" name="password" required><br>

      <button type="submit">ログイン</button>
    </form>
  </div>
</body>
</html>