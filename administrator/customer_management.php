<?php
require 'header.php'; // ヘッダー呼び出し

// ▼ データベース接続（例：MySQLの場合）
try {
    $pdo = new PDO('mysql:host=mysql326.phy.lolipop.lan;dbname=LAA1607576-rephone;charset=utf8',
    'LAA1607576',
    'te621128');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'DB接続エラー: ' . $e->getMessage();
    exit;
}

// ▼ 検索キーワード取得
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// ▼ データ取得処理
if ($keyword !== '') {
    $sql = "SELECT register_date, customer_name, customer_number FROM customers
            WHERE customer_name LIKE :keyword OR customer_number LIKE :keyword";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
    $stmt->execute();
} else {
    $sql = "SELECT register_date, customer_name, customer_number FROM customers ORDER BY register_date DESC";
    $stmt = $pdo->query($sql);
}
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="section" style="background-color: #f5f5f5; min-height: 100vh;">
  <div class="columns is-gapless">
    <!-- サイドメニュー -->
    <div class="column is-narrow" style="width: 220px; background-color: #fff; border-right: 1px solid #ccc;">
      <aside class="menu p-4">
        <p class="menu-label">メニュー</p>
        <ul class="menu-list">
          <li><a href="#">ホーム</a></li>
          <li><a href="#">商品管理</a></li>
          <li>
            <a class="is-active">顧客管理</a>
            <ul>
              <li><a href="#">顧客マスター</a></li>
            </ul>
          </li>
          <li><a href="#">注文管理</a></li>
          <li><a href="#">業務管理</a></li>
        </ul>
      </aside>
    </div>

    <!-- メインエリア -->
    <div class="column" style="padding: 2rem;">
      <div class="box">
        <h1 class="title is-5">
          顧客管理 / 顧客マスター<?= $keyword ? ' / 顧客マスター検索結果' : '' ?>
        </h1>

        <form method="get" action="customer_master.php" class="mb-4">
          <div class="field has-addons">
            <div class="control is-expanded">
              <input class="input" type="text" name="keyword" placeholder="キーワード検索" value="<?= htmlspecialchars($keyword) ?>">
            </div>
            <div class="control">
              <button class="button is-info" type="submit">検索</button>
            </div>
          </div>
        </form>

        <?php if ($keyword !== ''): ?>
          <p>「<?= htmlspecialchars($keyword) ?>」の検索結果を表示しました。</p>
          <hr>
        <?php endif; ?>

        <table class="table is-fullwidth is-striped is-hoverable">
          <thead>
            <tr>
              <th>会員登録日</th>
              <th>顧客名</th>
              <th>顧客番号</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($customers) > 0): ?>
              <?php foreach ($customers as $c): ?>
                <tr>
                  <td><?= htmlspecialchars($c['register_date']) ?></td>
                  <td><?= htmlspecialchars($c['customer_name']) ?></td>
                  <td><?= htmlspecialchars($c['customer_number']) ?></td>
                  <td><a href="customer_detail.php?id=<?= urlencode($c['customer_number']) ?>" class="button is-small is-link">詳細</a></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="4">該当する顧客は見つかりませんでした。</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<?php require 'footer.php'; ?>
