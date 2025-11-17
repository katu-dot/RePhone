<?php
// 作者：勝原優太郎
require '../config/db-connect.php';
require 'header.php';

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 検索キーワード（GETパラメータ）
    $search_query = $_GET['q'] ?? '';
    $order = $_GET['order'] ?? 'new'; 

    // ベースSQL
    $sql = "SELECT * FROM customer_management";
    $where = '';
    $order_sql = '';

    if ($search_query !== '') {
        $where = " WHERE name LIKE :keyword OR email LIKE :keyword OR phone LIKE :keyword OR address LIKE :keyword";
    }

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

    $sql .= $where . $order_sql;

    $stmt = $pdo->prepare($sql);
    if ($search_query !== '') {
        $stmt->bindValue(':keyword', '%' . $search_query . '%', PDO::PARAM_STR);
    }
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result_count = count($customers);

    // 総顧客数を取得
    $total_customers = $pdo->query("SELECT COUNT(*) FROM customer_management")->fetchColumn();

} catch (PDOException $e) {
    echo '<div class="notification is-danger">接続エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}
?>

<div class="columns">
<?php require '../config/left-menu.php'; ?>

<div class="column" style="padding: 2rem;">
    <h1 class="title is-4">顧客管理／顧客マスター</h1>
    <h2 class="subtitle is-6">顧客一覧</h2>

    <!-- 総顧客数 -->
    <h3 class="subtitle is-4 mb-3">総顧客数：<?= number_format($total_customers) ?> 名</h3>

    <!-- メッセージ表示 -->
    <?php if (!empty($_GET['message'])): ?>
        <div class="notification is-success"><?= htmlspecialchars($_GET['message']); ?></div>
    <?php endif; ?>

    <!-- 検索フォーム -->
    <form action="" method="GET">
        <div class="field is-grouped">
            <p class="control"><label class="label">顧客検索：</label></p>
            <p class="control is-expanded">
                <input class="input" type="text" name="q" placeholder="ワード検索" value="<?= htmlspecialchars($search_query) ?>">
            </p>
            <p class="control"><button type="submit" class="button is-info">検索</button></p>

            <?php if ($search_query !== ''): ?>
                <p class="control">
                    <a href="K3-customer_master.php" class="button is-light">検索結果をクリア</a>
                </p>
            <?php endif; ?>

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

    <!-- 顧客一覧テーブル -->
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
                <?php foreach ($customers as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['registration_date']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['address']) ?></td>
                        <td>
                            <a href="K4-customer_detail.php?id=<?= htmlspecialchars($row['customer_management_id']) ?>" class="button is-small is-info">詳細</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="notification is-light">検索ワードを入力して顧客を検索してください。</div>
    <?php endif; ?>

</div>
</div>

<?php require 'footer.php'; ?>
