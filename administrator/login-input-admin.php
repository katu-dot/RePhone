<?php require './header.php'; ?>
<?php require '../db-connect.php'; ?>
<?php session_start(); ?>
<?php
// 修正中
?>
  <div class="login-container">
    <img src="./img/admin-logo.png" alt="">

    <h2>ログイン</h2>

    <form action="login.php" method="post" class="login-form">
      <label for="admin_id">管理者ID</label>
      <input type="text" id="admin_id" name="admin_id" required>

      <label for="password">パスワード</label>
      <input type="password" id="password" name="password" required>

      <button type="submit">ログイン</button>
    </form>
  </div>
<?php require './footer.php'; ?>
