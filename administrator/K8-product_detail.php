<?php
// 作者：勝原優太郎
// 商品詳細ページ（K8-product_detail.php）

session_start();
require '../config/db-connect.php';

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ▼ 削除処理
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
      $delete_id = (int)$_POST['delete_id'];
  
      try {
          $pdo->beginTransaction();
  
          $stmt = $pdo->prepare("DELETE FROM stock_management WHERE product_management_id = :id");
          $stmt->execute([':id' => $delete_id]);
  
          $stmt = $pdo->prepare("SELECT product_id FROM product_management WHERE product_management_id = :id");
          $stmt->execute([':id' => $delete_id]);
          $product = $stmt->fetch(PDO::FETCH_ASSOC);
          $product_id = $product['product_id'] ?? null;
  
          $stmt = $pdo->prepare("DELETE FROM product_management WHERE product_management_id = :id");
          $stmt->execute([':id' => $delete_id]);
  
          if ($product_id) {
              $stmt = $pdo->prepare("DELETE FROM product WHERE product_id = :pid");
              $stmt->execute([':pid' => $product_id]);
          }
  
          $pdo->commit();
  
          $message = urlencode('商品を削除しました。');
          header("Location: K7-product_master.php?message={$message}");
          exit;
  
      } catch (PDOException $e) {
          $pdo->rollBack();
          echo '<div class="notification is-danger">削除エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
          exit;
      }
  }  

    // ▼ URL確認
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo '<div class="notification is-danger">不正なアクセスです。</div>';
        exit;
    }

    $product_management_id = (int)$_GET['id'];

    // ▼ 商品情報取得（shipping_date 追加）
    // ▼ 商品情報取得（shipping_date 取得版）
$sql = "
SELECT 
    pm.product_management_id,
    pm.admin_id,
    pm.product_id,
    pm.status_id,
    pm.accessories,
    pm.stock,
    p.product_name,
    p.product_description,
    p.price,
    p.image,
    p.maker,
    p.release_date,
    p.cpu,
    p.memory,
    p.ssd,
    p.drive,
    p.display,
    p.os,
    p.shipping_id,
    s2.shipping_date,     -- ★ shipping_date を取得
    s.status_name,
    c.category_name
FROM product_management pm
INNER JOIN product p ON pm.product_id = p.product_id
INNER JOIN status s ON pm.status_id = s.status_id
LEFT JOIN category_management c ON p.category_id = c.category_id
LEFT JOIN shipping s2 ON p.shipping_id = s2.shipping_id   -- ★ shipping を JOIN
WHERE pm.product_management_id = :product_management_id
";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':product_management_id', $product_management_id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo '<div class="notification is-warning">該当する商品が見つかりません。</div>';
        exit;
    }

} catch (PDOException $e) {
    echo '<div class="notification is-danger">接続エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}

require 'header.php';
?>

<div class="columns">
  <?php require '../config/left-menu.php'; ?>

  <div class="column" style="padding: 2rem;">
    <h1 class="title is-4">商品管理／商品マスター／商品マスター詳細</h1>
    <h2 class="subtitle is-6 mb-5">商品詳細</h2>

    <?php if (isset($_GET['message']) && $_GET['message'] === 'edited'): ?>
        <div class="notification is-success">
            編集が完了しました。
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['message']) && $_GET['message'] === 'registered'): ?>
        <div class="notification is-success">
            商品の登録が完了しました。
        </div>
    <?php endif; ?>

    <div class="columns">
      <div class="column is-one-third">
        <div class="card">
          <div class="card-content">
            <figure class="image is-4by3">
              <?php
              $imageFilename = ltrim($product['image'], '/'); 
              $imageBaseUrl = '../'; 
              $imagePath = $imageBaseUrl . htmlspecialchars($imageFilename);

              if (!empty($product['image']) && file_exists($imagePath)) {
                  echo '<img src="' . $imagePath . '" alt="' . htmlspecialchars($product['product_name']) . '">';
              } else {
                  echo '<img src="../img/noimage.png" alt="画像なし">';
              }
              ?>
            </figure>

            <hr>
            <p class="title is-5"><?= htmlspecialchars($product['product_name']); ?></p>
            <p class="subtitle is-6 has-text-danger">¥<?= number_format($product['price']); ?> 円</p>
            <p class="subtitle is-6">商品番号：<strong><?= htmlspecialchars($product['product_id']); ?></strong></p>
            <p class="mt-3">在庫数：<strong><?= htmlspecialchars($product['stock']); ?>個</strong></p>
            <p class="mt-2">発送日：<strong><?= htmlspecialchars($product['shipping_date'] ?? '―'); ?></strong></p>

            <a href="K10-product_edit.php?id=<?= htmlspecialchars($product['product_management_id']); ?>" 
              class="button is-warning is-small is-fullwidth" 
              style="margin-top:8px;">
              編集
            </a>

            <form method="POST" onsubmit="return confirm('本当にこの商品を削除しますか？');" style="margin-top:10px;">
              <input type="hidden" name="delete_id" value="<?= htmlspecialchars($product['product_management_id']); ?>">
              <button type="submit" class="button is-danger is-small is-fullwidth">削除</button>
            </form>
          </div>
        </div>
      </div>

      <div class="column is-two-thirds">
        <table class="table is-fullwidth is-striped">
          <tbody>
            <tr><th>メーカー</th><td><?= htmlspecialchars($product['maker'] ?: '―'); ?></td></tr>
            <tr><th>発売日</th><td><?= htmlspecialchars($product['release_date'] ?: '―'); ?></td></tr>
            <tr><th>商品説明</th><td><?= nl2br(htmlspecialchars($product['product_description'])); ?></td></tr>
            <tr><th>カテゴリ</th><td><?= htmlspecialchars($product['category_name'] ?? '―'); ?></td></tr>
            <tr><th>CPU</th><td><?= htmlspecialchars($product['cpu'] ?: '―'); ?></td></tr>
            <tr><th>メモリ</th><td><?= htmlspecialchars($product['memory'] ?: '―'); ?></td></tr>
            <tr><th>SSD</th><td><?= htmlspecialchars($product['ssd'] ?: '―'); ?></td></tr>
            <tr><th>ドライブ</th><td><?= htmlspecialchars($product['drive'] ?: '―'); ?></td></tr>
            <tr><th>ディスプレイ</th><td><?= htmlspecialchars($product['display'] ?: '―'); ?></td></tr>
            <tr><th>OS</th><td><?= htmlspecialchars($product['os'] ?: '―'); ?></td></tr>
            <tr><th>付属品</th><td><?= htmlspecialchars($product['accessories'] ?: '―'); ?></td></tr>
            <tr><th>状態区分</th><td><?= htmlspecialchars($product['status_name']); ?></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <a href="K7-product_master.php" class="button is-light mt-4">商品一覧へ戻る</a>
  </div>
</div>

<?php require 'footer.php'; ?>
