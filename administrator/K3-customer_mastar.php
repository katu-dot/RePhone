<?php
// 作者：勝原優太郎

require '../config/db-connect.php';
require 'header.php';

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 検索キーワード（GETパラメータから取得）
    $search_query = $_GET['q'] ?? '';
    $order = $_GET['order'] ?? 'new'; // 並び替え（新しい順がデフォルト）

    // ベースSQL
    $base_sql = "SELECT * FROM customer_management";
    $where = '';
    $order_sql = '';

    // 検索条件
    if ($search_query !== '') {
        $where = " WHERE name LIKE :keyword 
                OR email LIKE :keyword 
                OR phone LIKE :keyword 
                OR address LIKE :keyword";
    }

    // 並び替え条件
    switch ($order) {
        case 'old':
            $order_sql = " ORDER BY registration_date ASC";
            break;
        case 'user_asc':
            $order_sql = " ORDER BY customer_management_id ASC";
            break;
        case 'user_desc':
            $order_sql = " ORDER BY customer_management_id DESC";
            break;
        default:
            $order_sql = " ORDER BY registration_date DESC";
    }

    // 最終SQL組み立て
    $sql = $base_sql . $where . $order_sql;

    // SQL実行
    if ($search_query !== '') {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':keyword', '%' . $search_query . '%', PDO::PARAM_STR);
        $stmt->execute();
    } else {
        $stmt = $pdo->query($sql);
    }

    $result_count = $stmt->rowCount(); // 件数取得

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

    <!-- 検索フォーム -->
    <form action="" method="GET">
        <div class="field is-grouped">
            <p class="control">
                <label class="label">顧客検索：</label>
            </p>
            <p class="control is-expanded">
                <input class="input" type="text" name="q" placeholder="ワード検索" value="<?= htmlspecialchars($search_query) ?>">
            </p>
            <p class="control">
                <button type="submit" class="button is-info">検索</button>
            </p>

            <!-- 検索中のみクリアボタンを表示 -->
            <?php if ($search_query !== ''): ?>
                <p class="control">
                    <a href="K3-customer_mastar.php" class="button is-light">検索結果をクリア</a>
                </p>
            <?php endif; ?>

            <!-- 並び替え -->
            <p class="control">
                <div class="select">
                    <select name="order" onchange="this.form.submit()">
                        <option value="new" <?= $order === 'new' ? 'selected' : '' ?>>新しい順</option>
                        <option value="old" <?= $order === 'old' ? 'selected' : '' ?>>古い順</option>
                    </select>
                </div>
            </p>
        </div>
    </form>

    <!-- 件数表示 -->
    <?php if ($search_query !== '' && $result_count > 0): ?>
        <h3 class="subtitle is-5 mt-5">検索結果：<?= $result_count ?>件が該当しました</h3>
    <?php elseif ($search_query !== '' && $result_count === 0): ?>
        <div class="notification is-warning">該当する顧客情報が見つかりませんでした。</div>
    <?php endif; ?>

    <!-- 検索結果テーブル -->
    <?php if ($result_count > 0): ?>
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
                        <td><a href="K4-customer_detail.php?id=<?= htmlspecialchars($row['customer_management_id']) ?>" class="button is-small is-info">詳細</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($search_query === ''): ?>
        <div class="notification is-light">検索ワードを入力して顧客を検索してください。</div>
    <?php endif; ?>

</div>
  </div>
</div>

<?php require 'footer.php'; ?>
