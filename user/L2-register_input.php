<?php
session_start();
// データベース接続設定ファイルは別途必要です
require '../config/db-connect.php'; 
require 'header.php';

$pdo = new PDO($connect, USER, PASS);
$error_message = [];
$success_message = "";

// フォームの入力値を保持するための変数
$user_name = $_POST['user_name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$phone = $_POST['phone'] ?? '';
$postal_code = $_POST['postal_code'] ?? '';
$address = $_POST['address'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. バリデーション
    
    // 必須項目チェック
    if (empty($user_name)) $error_message[] = "氏名を入力してください。";
    if (empty($email)) $error_message[] = "メールアドレスを入力してください。";
    if (empty($password)) $error_message[] = "パスワードを入力してください。";
    if (empty($phone)) $error_message[] = "電話番号を入力してください。";
    if (empty($postal_code)) $error_message[] = "郵便番号を入力してください。";
    if (empty($address)) $error_message[] = "住所を入力してください。";

    // パスワードチェック (L2-2相当のチェック)
    if ($password !== $password_confirm) {
        // パスワードが異なる場合、特定のメッセージとエラーフラグをセット
        $error_message[] = "パスワードが確認用と一致しません。";
    } elseif (strlen($password) < 4) {
        $error_message[] = "パスワードは半角4文字以上で入力してください。";
    }

    // メールアドレスの重複チェック
    if (empty($error_message)) {
        $sql = $pdo->prepare('SELECT COUNT(*) FROM user WHERE email = ?');
        $sql->execute([$email]);
        if ($sql->fetchColumn() > 0) {
            $error_message[] = "このメールアドレスは既に登録されています。";
        }
    }

    // 2. データベース登録
    if (empty($error_message)) {
        try {
            // パスワードをハッシュ化
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = $pdo->prepare('INSERT INTO user (user_name, email, password, phone, postal_code, address) VALUES (?, ?, ?, ?, ?, ?)');
            $sql->execute([$user_name, $email, $hashed_password, $phone, $postal_code, $address]);

            $success_message = "会員登録が完了しました。ログインページへ移動します。";
            // 登録成功後、入力値をクリア
            $user_name = $email = $password = $password_confirm = $phone = $postal_code = $address = '';
            
            // 実際の運用ではここでリダイレクトすることが多い
            // header('Location: login-user.php?registration=success');
            // exit();

        } catch (PDOException $e) {
            // データベースエラー
            $error_message[] = "登録中にエラーが発生しました: DBエラー";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RePhone 新規会員登録</title>
    <style>
        /* CSSは前回のコードから微調整し、一つの画面に全ての要素を収めます */
        body {
            font-family: 'Helvetica Neue', 'Arial', 'メイリオ', sans-serif;
            background-color: #fff;
            color: #000;
            margin: 0;
            padding: 0;
            text-align: center;
        }

        /* ヘッダーの調整 (前回のコードからそのまま使用) */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid #ccc;
        }
        .logo-image { height: 30px; width: auto; }
        .header-icons { display: flex; align-items: center; }
        .icon { font-size: 1.5em; margin-left: 15px; cursor: pointer; position: relative; }
        .cart-count {
            position: absolute; top: -5px; right: -10px; background-color: red; color: white;
            border-radius: 50%; padding: 0 5px; font-size: 0.7em; line-height: 1.2;
        }
        
        /* フォームコンテナ */
        .register-container {
            width: 90%;
            max-width: 400px;
            margin: 0 auto;
            padding-top: 30px; 
            padding-bottom: 50px;
            text-align: left;
        }

        /* タイトル */
        .register-container h2 {
            font-size: 1.8em;
            font-weight: bold;
            margin-bottom: 5px;
            text-align: center;
        }
        .subtitle {
            font-size: 1em;
            margin-bottom: 30px;
            text-align: center;
        }

        /* フォームグループ */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        /* パスワードの注意書き */
        .password-note {
            font-size: 0.9em;
            color: #555;
            margin-bottom: 5px;
        }

        /* 入力フォーム */
        .register-form input[type="text"],
        .register-form input[type="email"],
        .register-form input[type="password"] {
            width: 100%; 
            padding: 12px 10px;
            border: 1px solid #ccc; 
            border-radius: 5px;
            background-color: #fff; 
            box-sizing: border-box; 
            font-size: 1em;
        }
        /* パスワードのエラー線 (L2-2の赤線再現) */
        .password-error-input {
            border: 1px solid red !important;
        }

        /* パスワード切替ボタンのラッパー */
        .password-wrapper {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 1.2em;
            cursor: pointer;
        }

        /* 認証メールのテキスト (L2-1の注釈再現) */
        .info-text {
            font-size: 0.85em;
            color: #555;
            margin-top: 5px;
            margin-bottom: 10px;
            line-height: 1.4;
            text-align: left;
        }
        /* パスワード不一致のエラーメッセージ (L2-2の赤字再現) */
        .password-mismatch-error {
            color: red;
            font-weight: bold;
            font-size: 0.9em;
            margin-top: 5px;
        }

        /* 送信ボタン (L2-2のボタンを再現) */
        .register-form button[type="submit"] {
            display: block;
            width: 60%; 
            margin: 30px auto 20px;
            padding: 15px;
            background-color: #a19ae0; 
            border: none;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            font-size: 1.2em;
            cursor: pointer;
        }

        /* 下部リンク (L2-2のリンクを再現) */
        .bottom-link {
            display: block;
            font-weight: bold;
            text-decoration: none;
            color: #000;
            text-align: center;
        }

        /* エラー・成功メッセージ */
        .error { color: red; font-weight: bold; margin-bottom: 15px; text-align: center; }
        .success { color: green; font-weight: bold; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>

    <div class="register-container">
        <h2>新規会員登録</h2>
        <p class="subtitle">お客様の情報を入力してください</p>

        <?php if (!empty($error_message)): ?>
            <?php foreach ($error_message as $msg): ?>
                <p class="error">⚠️ <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <p class="success">✅ <?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form action="L3-register_complete.php" method="post" class="register-form">
            
            <div class="form-group">
                <label for="user_name">お名前（氏名）</label>
                <input type="text" id="user_name" name="user_name" required placeholder="例：リフォン 太郎" value="<?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input type="email" id="email" name="email" required placeholder="例：sales@rephone.co.jp" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                <!--<p class="info-text">送信を押すと、入力したメールアドレスに認証メールが送信されます。届いたメールに記載されているリンクから登録を続けてください。</p>-->
            </div>

            <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">

            <div class="form-group">
                <label for="password">新規パスワード</label>
                <p class="password-note">新規パスワード（半角4文字以上）</p>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" required value="<?= htmlspecialchars($password, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="button" class="toggle-password" data-target="password">👁</button>
                </div>
            </div>

            <div class="form-group">
                <label for="password_confirm">新規パスワード（確認）</label>
                <div class="password-wrapper">
                    <input type="password" id="password_confirm" name="password_confirm" required value="<?= htmlspecialchars($password_confirm, ENT_QUOTES, 'UTF-8') ?>"
                           class="<?= ($password !== $password_confirm && !empty($password_confirm)) ? 'password-error-input' : '' ?>">
                    <button type="button" class="toggle-password" data-target="password_confirm">👁</button>
                </div>
                <?php if ($password !== $password_confirm && !empty($password_confirm)): ?>
                    <p class="password-mismatch-error">パスワードが異なっています。</p>
                <?php endif; ?>
                <p class="info-text">登録したパスワードはログイン時に必要です。忘れないように保存してください。</p>
            </div>
            
            <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">
            <p class="subtitle" style="margin-bottom: 15px;">その他の情報</p>

            <div class="form-group">
                <label for="phone">電話番号</label>
                <input type="text" id="phone" name="phone" required placeholder="例：09012345678" value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <div class="form-group">
                <label for="postal_code">郵便番号</label>
                <input type="text" id="postal_code" name="postal_code" required placeholder="例：8100001" value="<?= htmlspecialchars($postal_code, ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label for="address">住所</label>
                <input type="text" id="address" name="address" required placeholder="例：福岡県福岡市中央区天神1-1-1" value="<?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <button type="submit">送信</button>
        </form>

        <a href="G1-top.php" class="bottom-link">ホームに戻る</a>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-target');
                const passwordField = document.getElementById(targetId);
                
                const type = passwordField.type === 'password' ? 'text' : 'password';
                passwordField.type = type;
                button.textContent = type === 'password' ? '👁' : '🙈';
            });
        });
    </script>
    <?php require 'footer.php'; ?>
</body>
</html>