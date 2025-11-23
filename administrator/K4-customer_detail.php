<?php
require '../config/db-connect.php';

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo '<div class="notification is-danger">不正なアクセスです。</div>';
        exit;
    }
    $customer_management_id = (int)$_GET['id'];

    // 削除処理
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
        try {
            $pdo->beginTransaction();

            // 注文削除
            $stmt = $pdo->prepare("DELETE FROM order_management WHERE customer_management_id = :id");
            $stmt->bindValue(':id', $customer_management_id, PDO::PARAM_INT);
            $stmt->execute();

            // 顧客削除
            $stmt = $pdo->prepare("DELETE FROM customer_management WHERE customer_management_id = :id");
            $stmt->bindValue(':id', $customer_management_id, PDO::PARAM_INT);
            $stmt->execute();

            $pdo->commit();

            // 削除後リダイレクト
            $message = urlencode('顧客情報を削除しました。');
            header("Location: K3-customer_master.php?message={$message}");
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            echo '<div class="notification is-danger">削除エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
            exit;
        }
    }

    // 顧客情報取得
    $stmt = $pdo->prepare("SELECT * FROM customer_management WHERE customer_management_id = :id");
    $stmt->bindValue(':id', $customer_management_id, PDO::PARAM_INT);
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

require 'header.php';
?>
<!-- 登録完了メッセージ -->
<?php if (isset($_GET['message']) && $_GET['message'] === 'registered'): ?>
    <div class="notification is-success mt-3">
        顧客情報を登録しました。
    </div>
<?php endif; ?>

<!-- 編集完了メッセージ -->
<?php if (isset($_GET['message']) && $_GET['message'] === 'edited'): ?>
    <div class="notification is-success mt-3">
        顧客情報の編集が完了しました。
    </div>
<?php endif; ?>


<div class="columns">
<?php require '../config/left-menu.php'; ?>

<div class="column" style="padding: 2rem;">
    <h1 class="title is-4">顧客管理／顧客マスター／顧客マスター詳細</h1>
    <h2 class="subtitle is-6">顧客詳細</h2>

    <!-- 顧客情報表示 -->
    <div class="box">
        <p><strong>会員登録日：</strong> <?= htmlspecialchars($customer['registration_date']) ?></p>
        <p><strong>氏名：</strong> <?= htmlspecialchars($customer['name']) ?> 様</p>
        <p><strong>メールアドレス：</strong> <?= htmlspecialchars($customer['email']) ?></p>
        <p><strong>電話番号：</strong> <?= htmlspecialchars($customer['phone']) ?></p>
        <p><strong>郵便番号：</strong> <?= htmlspecialchars($customer['postal_code']) ?></p>
        <p><strong>住所：</strong> <?= htmlspecialchars($customer['address']) ?></p>
        <p><strong>番地：</strong> <?= htmlspecialchars($customer['street_address']) ?></p>
    </div>


    <div class="buttons mt-3">
        <a href="K3-customer_master.php" class="button is-light">顧客一覧へ戻る</a>
        <a href="K4-customer_edit.php?id=<?= htmlspecialchars($customer['customer_management_id']); ?>" class="button is-warning">編集</a>

        <!-- 削除ボタン -->
        <form action="" method="POST" style="display:inline;" onsubmit="return confirm('この顧客と関連する注文もすべて削除されます。本当に削除しますか？');">
            <button type="submit" name="delete" class="button is-danger">削除</button>
        </form>
    </div>

    <!-- 編集完了メッセージ -->
    <?php if (isset($_GET['message']) && $_GET['message'] === 'edited'): ?>
        <div class="notification is-success mt-3">
            顧客情報の編集が完了しました。
        </div>
    <?php endif; ?>
</div>
</div>

<?php require 'footer.php'; ?>
