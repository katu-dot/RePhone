<?php
require '../config/db-connect.php';
require 'header.php';

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 検索キーワードを取得
    $keyword = $_POST['keyword'] ?? '';

    // SQLを動的に変更
    if ($keyword !== '') {
        $sql = "SELECT * FROM customer_management 
                WHERE name LIKE :keyword 
                OR email LIKE :keyword 
                OR phone LIKE :keyword 
                OR address LIKE :keyword
                ORDER BY registration_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
        $stmt->execute();
    } else {
        $sql = "SELECT * FROM customer_management ORDER BY registration_date DESC";
        $stmt = $pdo->query($sql);
    }
} catch (PDOException $e) {
    echo '<div class="notification is-danger">接続エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}
?>

<div class="columns">

  <!-- 右メインコンテンツ -->
  <div class="column" style="padding: 2rem;">
  <div class="container mt-5">
    <h1 class="title is-4">顧客管理／顧客マスター</h1>
    <h2 class="subtitle is-6">顧客一覧</h2>

    <!-- 検索フォーム -->
    <form action="customer-management.php" method="post" class="mb-5">
        <div class="field has-addons">
            <div class="control is-expanded">
                <input class="input" type="text" name="keyword" placeholder="顧客検索（氏名・メール・電話・住所）" value="<?= htmlspecialchars($keyword) ?>">
            </div>
            <div class="control">
                <button class="button is-link" type="submit">検索</button>
            </div>
            <?php if ($keyword !== ''): ?>
                <div class="control">
                    <a href="customer-list.php" class="button is-light">クリア</a>
                </div>
            <?php endif; ?>
        </div>
    </form>

    <!-- 検索結果表示 -->
    <?php if ($keyword !== ''): ?>
        <p class="has-text-grey mb-3">
            検索キーワード：「<?= htmlspecialchars($keyword) ?>」
        </p>
    <?php endif; ?>

    <?php if ($stmt->rowCount() === 0): ?>
        <div class="notification is-warning">
            該当する顧客情報が見つかりませんでした。
        </div>
    <?php else: ?>
        <table class="table is-striped is-fullwidth">
            <thead>
                <tr>
                    <th>会員登録日</th>
                    <th>氏名</th>
                    <th>メールアドレス</th>
                    <th>電話番号</th>
                    <th>住所</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stmt as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['registration_date']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td><?= htmlspecialchars($row['address']) ?></td>
                        <td><a href="customer-detail.php?id=<?= $row['customer_management_id'] ?>" class="button is-small is-info">詳細</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
  </div>
</div>
<?php require 'footer.php'; ?>
