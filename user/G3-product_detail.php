<?php
// 作者：勝原優太郎
session_start();
require '../config/db-connect.php';

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo '<div class="notification is-danger">不正なアクセスです。</div>';
        exit;
    }

    $product_management_id = (int)$_GET['id'];

    // 付属品も join して取得 (元のSQLを維持)
    $sql = "
    SELECT 
        pm.product_management_id,
        pm.stock,
        pm.accessories_id,
        p.product_id,
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
        s2.shipping_date,
        s.status_name,
        c.category_name,
        a.accessories_name
    FROM product_management pm
    INNER JOIN product p ON pm.product_id = p.product_id
    INNER JOIN status s ON pm.status_id = s.status_id
    LEFT JOIN category_management c ON pm.category_id = c.category_id
    LEFT JOIN shipping s2 ON p.shipping_id = s2.shipping_id
    LEFT JOIN accessories a ON pm.accessories_id = a.accessories_id
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

<div class="columns" style="padding: 2rem;">
    <div class="column is-one-third">
        <figure class="image is-4by3">
            <?php
            $imagePath = '../' . ltrim($product['image'], '/');
            if (!empty($product['image']) && file_exists($imagePath)) {
                echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($product['product_name']) . '" style="object-fit: contain;">';
            } else {
                echo '<img src="../img/noimage.png" alt="画像なし" style="object-fit: contain;">';
            }
            ?>
        </figure>
    </div>

    <div class="column is-two-thirds">
        <p class="title is-4"><?= htmlspecialchars($product['product_name']); ?></p>
        <p class="subtitle is-5 has-text-danger">¥<?= number_format($product['price']); ?> 円</p>
        <p class="subtitle is-6">商品番号：<strong><?= htmlspecialchars($product['product_id']); ?></strong></p>
        <p class="mt-2">在庫数：<strong><?= htmlspecialchars($product['stock']); ?>個</strong></p>
        <p>発送日：<strong><?= htmlspecialchars($product['shipping_date'] ?? '―'); ?></strong></p>

        <hr>
        <form action="G4-cart.php" method="POST">
            <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['product_id']); ?>">
            
            <div class="field is-grouped">
                <div class="control">
                    <div class="select">
                        <select name="quantity">
                            <?php 
                            $stock = $product['stock'];
                            // 在庫がある場合、最大5個まで選択可能にする
                            if ($stock > 0) {
                                $max_qty = ($stock > 5) ? 5 : $stock;
                                for ($i = 1; $i <= $max_qty; $i++) {
                                    echo "<option value=\"{$i}\">{$i}</option>";
                                }
                            } else {
                                echo "<option value=\"0\">0</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="control">
                    <?php if ($stock > 0): ?>
                        <button type="submit" class="button is-danger">カートに入れる</button>
                    <?php else: ?>
                        <button type="button" class="button is-dark" disabled>売り切れ</button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
        </div>
</div>

<div class="columns" style="padding: 0 2rem 2rem 2rem;">
    <div class="column">
        <table class="table is-fullwidth is-striped">
            <tbody>
                <tr><th>メーカー</th><td><?= htmlspecialchars($product['maker'] ?: '―'); ?></td></tr>
                <tr><th>発売日</th><td><?= htmlspecialchars($product['release_date'] ?: '―'); ?></td></tr>
                <tr><th>商品説明</th><td><?= nl2br(htmlspecialchars($product['product_description'] ?: '―')); ?></td></tr>
                <tr><th>カテゴリ</th><td><?= htmlspecialchars($product['category_name'] ?? '―'); ?></td></tr>
                <tr><th>CPU</th><td><?= htmlspecialchars($product['cpu'] ?: '―'); ?></td></tr>
                <tr><th>メモリ</th><td><?= htmlspecialchars($product['memory'] ?: '―'); ?></td></tr>
                <tr><th>SSD</th><td><?= htmlspecialchars($product['ssd'] ?: '―'); ?></td></tr>
                <tr><th>ドライブ</th><td><?= htmlspecialchars($product['drive'] ?: '―'); ?></td></tr>
                <tr><th>ディスプレイ</th><td><?= htmlspecialchars($product['display'] ?: '―'); ?></td></tr>
                <tr><th>OS</th><td><?= htmlspecialchars($product['os'] ?: '―'); ?></td></tr>
                <tr><th>付属品</th><td><?= htmlspecialchars($product['accessories_name'] ?? '―'); ?></td></tr>
                <tr><th>状態区分</th><td><?= htmlspecialchars($product['status_name']); ?></td></tr>
            </tbody>
        </table>
    </div>
</div>

<a href="G1-top.php" class="button is-light ml-2 mb-4">トップへ戻る</a>

<?php require 'footer.php'; ?>