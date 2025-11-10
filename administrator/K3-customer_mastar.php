<?php
//作者：勝原優太郎

require '../config/db-connect.php';
require 'header.php';

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 検索キーワードと並び替え条件を取得
    $keyword = $_POST['keyword'] ?? '';
    $order = $_POST['order'] ?? 'new'; // デフォルトは新しい順

    // ベースSQL
    $base_sql = "SELECT * FROM customer_management";
    $where = '';
    $order_sql = '';

    //検索条件
    if ($keyword !== '') {
        $where = " WHERE name LIKE :keyword 
                OR email LIKE :keyword 
                OR phone LIKE :keyword 
                OR address LIKE :keyword";
    }

    //並び替え条件
    switch ($order) {
        case 'old':
            $order_sql = " ORDER BY registration_date ASC";
            break;
        case 'user_asc':
            $order_sql = " ORDER BY user_id ASC";
            break;
        case 'user_desc':
            $order_sql = " ORDER BY user_id DESC";
            break;
        default:
            $order_sql = " ORDER BY registration_date DESC"; // new
    }

    // 最終SQL組み立て
    $sql = $base_sql . $where . $order_sql;

    // SQL実行
    if ($keyword !== '') {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
        $stmt->execute();
    } else {
        $stmt = $pdo->query($sql);
    }

} catch (PDOException $e) {
    echo '<div class="notification is-danger">接続エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}
?>

<div class="columns">
<?php require '../config/left-menu.php'; ?>

  <div class="column" style="padding: 2rem;">
  <div class="container mt-5">
    <h1 class="title is-4">顧客管理／顧客マスター</h1>
    <h2 class="subtitle is-6">顧客一覧</h2>

    <!-- 検索フォーム＋並び替えフォーム -->
    <form action="K3-customer_mastar.php" method="post" class="mb-5">
        <div class="field has-addons">
            <div class="control is-expanded">
                <input class="input" type="text" name="keyword" placeholder="顧客検索（氏名・メール・電話・住所）" value="<?= htmlspecialchars($keyword) ?>">
            </div>
            <div class="control">
                <button class="button is-link" type="submit">検索</button>
            </div>
            <?php if ($keyword !== ''): ?>
                <div class="control">
                    <a href="K3-customer_mastar.php" class="button is-light">クリア</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- 並び替え -->
        <div class="field mt-3">
            <div class="control">
                <div class="select">
                    <select name="order" onchange="this.form.submit()">
                        <option value="new" <?= $order === 'new' ? 'selected' : '' ?>>新しい順</option>
                        <option value="old" <?= $order === 'old' ? 'selected' : '' ?>>古い順</option>
                    </select>
                </div>
            </div>
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
                    <th>住所</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stmt as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['registration_date']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['address']) ?></td>
                        <td><a href="K4-customer_detail.php?id=<?= $row['customer_management_id'] ?>" class="button is-small is-info">詳細</a></td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
  </div>
</div>
<?php require 'footer.php'; ?>
