<?php
// 作者：勝原優太郎
session_start();
require '../config/db-connect.php';

// ---------------------------
// ログイン判定
// ---------------------------
$is_logged_in = isset($_SESSION['user_id']);

// ---------------------------
// カート初期化
// ---------------------------
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ---------------------------
// フラッシュメッセージ取得
// ---------------------------
$message = '';
if (!empty($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// ---------------------------
// カート追加処理
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_management_id']) && !isset($_POST['direct_purchase'])) {
    $pid = (int)$_POST['product_management_id'];
    $qty = max(1, (int)($_POST['quantity'] ?? 1));

    if (isset($_SESSION['cart'][$pid])) {
        $_SESSION['cart'][$pid] += $qty;
    } else {
        $_SESSION['cart'][$pid] = $qty;
    }

    // フラッシュメッセージを保存してリダイレクト
    $_SESSION['flash_message'] = "商品をカートに追加しました。";
    header("Location: G3-product_detail.php?id=" . $pid);
    exit();
}

// ---------------------------
// 商品情報取得
// ---------------------------
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo '<div class="notification is-danger">不正なアクセスです。</div>';
        exit;
    }

    $product_management_id = (int)$_GET['id'];

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

// ---------------------------
// header.php をここで読み込む
// ---------------------------
require './header.php';
?>

<!-- ▼ カート追加後メッセージ -->
<?php if (!empty($message)): ?>
    <div class="notification is-success" style="margin: 1rem;">
        <?= htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

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

        <!-- カートに入れるフォーム -->
        <form action="G3-product_detail.php?id=<?= htmlspecialchars($product_management_id); ?>" method="POST">
            <input type="hidden" name="product_management_id" value="<?= htmlspecialchars($product['product_management_id']); ?>">
            <div class="field is-grouped">
                <div class="control">
                    <div class="select">
                        <select name="quantity">
                            <?php
                            $stock = $product['stock'];
                            $max_qty = min(5, $stock);
                            for ($i = 1; $i <= max(1, $max_qty); $i++) {
                                echo "<option value=\"{$i}\">{$i}</option>";
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
        <!-- ▼ ログイン状態でボタン出し分け ---------------------------- -->

        <?php if ($stock > 0): ?>

        <?php if ($is_logged_in): ?>

            <!-- ▼ ログイン中：会員情報を自動入力して購入 -->
            <form action="G5-order_input.php" method="POST" style="margin-top:1rem;">
                <input type="hidden" name="product_management_id" value="<?= htmlspecialchars($product['product_management_id']); ?>">
                <input type="hidden" name="quantity" value="1">
                <input type="hidden" name="direct_purchase" value="1">
                <input type="hidden" name="auto_fill" value="1"> 
                <!-- ↑ 会員情報自動入力のフラグ -->
                <button type="submit" class="button is-danger" style="width:60%;">購入する</button>
            </form>

        <?php else: ?>

            <!-- ▼ 未ログイン：配送先入力して購入（会員情報なしで購入） -->
            <form action="G5-order_input.php" method="POST" style="margin-top:1rem;">
                <input type="hidden" name="product_management_id" value="<?= htmlspecialchars($product['product_management_id']); ?>">
                <input type="hidden" name="quantity" value="1">
                <input type="hidden" name="guest_purchase" value="1">
                <!-- ↑ ゲスト購入フラグ -->
                <button type="submit" class="button is-danger" style="width:60%;">配送先を入力して購入</button>
            </form>

            <!-- ▼ 未ログイン：ログインして購入 -->
            <div class="mt-2">
                <a href="L1-login.php" class="button is-danger is-light" style="width:60%;">ログインして購入</a>
            </div>

        <?php endif; ?>

        <?php endif; ?>
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

<a href="G1-top.php" class="button is-light ml-5 mb-4">トップへ戻る</a>

<?php require 'footer.php'; ?>
