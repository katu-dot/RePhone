<?php
//作者：勝原優太郎
//作成中

require '../config/db-connect.php';
require 'header.php';

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // URLパラメータの id（customer_management_id）取得
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo '<div class="notification is-danger">不正なアクセスです。</div>';
        exit;
    }

    $customer_management_id = (int)$_GET['id'];

    // 顧客情報を取得（customer_managementテーブルから）
    $sql = "SELECT * FROM customer_management WHERE customer_management_id = :customer_management_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':customer_management_id', $customer_management_id, PDO::PARAM_INT);
    $stmt->execute();

    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        echo '<div class="notification is-warning">該当する顧客情報が見つかりません。</div>';
        exit;
    }

} catch (PDOException $e) {
    echo '<div class="notification is-danger">接続エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}
?>

<div class="columns">

<!-- 左サイドメニュー -->
<?php require '../config/left-menu.php'; ?>

<!-- 右側メインコンテンツ -->
<div class="column" style="padding: 2rem;">

    <h1 class="title is-4">顧客管理／顧客マスター／顧客マスター詳細</h1>
    <h2 class="subtitle is-6">顧客詳細</h2>

    <div class="box">
        <p><strong>会員登録日：</strong> <?= htmlspecialchars($customer['registration_date']) ?></p>
        <p><strong>氏名：</strong> <?= htmlspecialchars($customer['name']) ?> 様</p>
        <p><strong>メールアドレス：</strong> <?= htmlspecialchars($customer['email']) ?></p>
        <p><strong>電話番号：</strong> <?= htmlspecialchars($customer['phone']) ?></p>
        <p><strong>郵便番号：</strong> <?= htmlspecialchars($customer['postal_code']) ?></p>
        <p><strong>住所：</strong> <?= htmlspecialchars($customer['address']) ?></p>
    </div>

    <a href="K3-customer_mastar.php" class="button is-light">顧客一覧へ戻る</a>

</div>
</div>

<?php require 'footer.php'; ?>
