<?php
session_start();
require '../config/db-connect.php';

// DB接続
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<p style='color:red; text-align:center;'>DB接続エラーが発生しました。</p>");
}

// ログアウト処理
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    session_destroy();
    header('Location: L1-login.php');
    exit();
}

// ログイン確認
if (!isset($_SESSION['user_id'])) {
    header('Location: L1-login.php');
    exit();
}

// 会員情報を取得
$stmt = $pdo->prepare('SELECT * FROM user WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// デバッグ用（表示は後で消せます）
$debug_user_id = $_SESSION['user_id'] ?? '未設定';

if (!$user) {
    // 見た目は変えずにエラー表示
    echo '<div style="max-width:400px;margin:50px auto;padding:20px;text-align:center;font-family:メイリオ;">';
    echo '<p style="color:red;font-weight:bold;">ユーザー情報が見つかりません。</p>';
    echo '<p style="font-size:0.9em;color:#555;">SESSION user_id: ' . htmlspecialchars($debug_user_id, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</div>';
    exit();
}

require 'header.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RePhone マイページ</title>
<style>
/* ===== ベースレイアウト ===== */
body {
    font-family: "Helvetica Neue", "メイリオ", sans-serif;
    margin: 0;
    padding: 0;
    background: #fff;
    color: #000;
}
.header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 15px;
    border-bottom: 1px solid #ccc;
}
.header img { height: 35px; }
.header-right { display: flex; align-items: center; gap: 15px; }

/* ===== マイページ部分 ===== */
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
.info { text-align: left; line-height: 1.8; }
.info p { margin: 10px 0; }
.info span { display: block; font-weight: bold; margin-bottom: 2px; }
.password { display: flex; align-items: center; gap: 10px; }
button.change-btn {
    border: 1px solid #ccc;
    background: #fff;
    cursor: pointer;
    padding: 5px 10px;
    border-radius: 4px;
}
button.change-btn:hover { background: #f2f2f2; }

/* ===== ログアウトリンク ===== */
.logout-link {
    text-align: right;
    margin-bottom: 10px;
}
.logout-link a {
    color: #c00;
    font-weight: bold;
    text-decoration: none;
}
.logout-link a:hover { text-decoration: underline; }

/* ===== フッターリンク ===== */
.footer {
    text-align: center;
    margin-top: 40px;
}
.footer a { text-decoration: none; font-weight: bold; margin: 0 20px; }
.footer a.home { color: #3c00c9; }
.footer a.cart { color: #3c00c9; }
</style>
</head>
<body>

<div class="container0">

  <!-- ログアウトリンク -->
  <div class="logout-link">
      <a href="?logout=1">ログアウト</a>
  </div>

  <h2>マイページ</h2>

  <div class="info">
    <p><span>ログインID</span><?= htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8') ?></p>

    <p><span>パスワード</span>
      <div class="password">
        <span>●●●●●●</span>
        <form action="change-password.php" method="get">
          <button class="change-btn">変更</button>
        </form>
      </div>
    </p>

    <p><span>お名前（姓名）</span><?= htmlspecialchars($user['user_name'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><span>メールアドレス</span><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><span>電話番号</span><?= htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><span>郵便番号</span><?= htmlspecialchars($user['postal_code'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><span>発送先住所</span><?= htmlspecialchars($user['address'], ENT_QUOTES, 'UTF-8') ?></p>
  </div>
</div>

<div class="footer">
    <a href="G1-top.php" class="home">ホームに戻る</a>
    <a href="G4-cart.php" class="cart">カートに戻る</a>
</div>

</body>
</html>
<?php require 'footer.php'; ?>
