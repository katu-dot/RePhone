<?php
session_start();

require './header.php'; 
require '../config/db-connect.php'; 

// -------------------------------
// ログイン判定
// -------------------------------
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;

// -------------------------------
// カート初期化
// -------------------------------
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if ($is_logged_in) {
    if (!isset($_SESSION['user_cart'])) $_SESSION['user_cart'] = [];

    // 初回ログイン時は現在のカートを user_cart に登録
    if (!isset($_SESSION['user_cart'][$user_id])) {
        $_SESSION['user_cart'][$user_id] = $_SESSION['cart'];
    } else {
        // 既存ユーザーカートと現在のカートを統合
        $_SESSION['cart'] = $_SESSION['user_cart'][$user_id] + $_SESSION['cart'];
    }
}

// -------------------------------
// 数量変更
// -------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'update' && isset($_GET['id']) && isset($_GET['qty'])) {
    $uid = (int)$_GET['id'];
    $uqty = (int)$_GET['qty'];

    if ($uqty > 0 && isset($_SESSION['cart'][$uid])) {
        $_SESSION['cart'][$uid] = $uqty;
    }

    if ($is_logged_in) {
        $_SESSION['user_cart'][$user_id] = $_SESSION['cart'];
    }

    echo "<script>window.location.href='G4-cart.php';</script>";
    exit();
}

// -------------------------------
// カート追加（POST）
// -------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_management_id'])) {
    $pmid = (int)$_POST['product_management_id'];
    $qty = max(1, (int)($_POST['quantity'] ?? 1));

    if (isset($_SESSION['cart'][$pmid])) {
        $_SESSION['cart'][$pmid] += $qty;
    } else {
        $_SESSION['cart'][$pmid] = $qty;
    }

    if ($is_logged_in) {
        $_SESSION['user_cart'][$user_id] = $_SESSION['cart'];
    }
}

// -------------------------------
// カート削除
// -------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];

    if (isset($_SESSION['cart'][$del_id])) {
        unset($_SESSION['cart'][$del_id]);
    }

    if ($is_logged_in) {
        $_SESSION['user_cart'][$user_id] = $_SESSION['cart'];
    }

    echo "<script>window.location.href='G4-cart.php';</script>";
    exit();
}

// -------------------------------
// カート商品情報取得
// -------------------------------
$cart_items = [];
$total_price = 0;
$shipping_fee = 970;

if (!empty($_SESSION['cart'])) {
    try {
        $pdo = new PDO($connect, USER, PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $product_ids = array_keys($_SESSION['cart']);
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));

        $sql = "SELECT p.product_id, p.product_name, p.price, p.image, pm.stock
                FROM product p
                LEFT JOIN product_management pm ON p.product_id = pm.product_id
                WHERE p.product_id IN ($placeholders)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($product_ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product) {
            $pid  = $product['product_id'];
            $qty  = $_SESSION['cart'][$pid] ?? 0;
            $stock = (int)($product['stock'] ?? 0);
            $subtotal = ($stock > 0) ? $product['price'] * $qty : 0;

            $cart_items[] = [
                'id' => $pid,
                'name' => $product['product_name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'qty' => $qty,
                'stock' => $stock,
                'subtotal' => $subtotal
            ];

            if ($stock > 0) $total_price += $subtotal;
        }
    } catch (PDOException $e) {
        echo "<div class='notification is-danger'>エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if ($total_price < 0) $total_price = 0;
?>

<section class="section">
<div class="container">

<h2 class="title is-4">
    <img src="../img/user-logo.jpg" alt="RePhone" style="height: 1.2em; vertical-align: middle;">
</h2>
<hr>

<?php if (empty($cart_items)): ?>
    <div class='notification is-warning has-text-centered'>
        カートに商品は入っていません。<br>
        <a href="G1-top.php" class="button is-small mt-3">お買い物に戻る</a>
    </div>
<?php else: ?>

<?php foreach ($cart_items as $item): ?>
<div class="box" data-id="<?= $item['id'] ?>" data-price="<?= $item['price'] ?>" data-qty="<?= $item['qty'] ?>" data-subtotal="<?= $item['subtotal'] ?>">
    <article class="media">
        <div class="media-left" style="display:flex; align-items:center;">
            <label class="checkbox">
                <input type="checkbox" class="item-check" checked>
            </label>
        </div>
        <div class="media-left">
            <figure class="image is-64x64">
                <?php
                    $img_db_val = $item['image'];
                    $img_path = '../' . ltrim($img_db_val, '/');
                    if (!empty($img_db_val) && file_exists($img_path)) {
                        echo '<img src="' . htmlspecialchars($img_path) . '" alt="商品画像">';
                    } else {
                        echo '<img src="../img/no_image.png" alt="No Image">';
                    }
                ?>
            </figure>
        </div>
        <div class="media-content">
            <div class="content">
                <p><strong><?= htmlspecialchars($item['name']) ?></strong></p>
                <div class="level is-mobile">
                    <div class="level-left">
                        <a href="G4-cart.php?action=delete&id=<?= $item['id'] ?>" class="button is-small is-white border-grey" style="border:1px solid #ccc;">削除</a>

                        <?php if ($item['stock'] > 0): ?>
                            <?php $maxQty = min(5, $item['stock']); ?>
                            <select class="input is-small ml-2 qty-select"
                                style="width: 60px;"
                                onchange="location.href='G4-cart.php?action=update&id=<?= $item['id'] ?>&qty=' + this.value;">
                                <?php for ($i = 1; $i <= $maxQty; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($item['qty'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        <?php else: ?>
                            <p class="ml-2" style="color:red; font-weight:bold;">在庫切れ</p>
                        <?php endif; ?>
                    </div>

                    <div class="level-right">
                        <p class="title is-5 has-text-danger"><?= number_format($item['subtotal']) ?> 円</p>
                    </div>
                </div>
            </div>
        </div>
    </article>
</div>
<?php endforeach; ?>

<div class="box" id="summary-box" style="background-color:#f9f9f9; border:none; box-shadow:none;">
    <div class="columns is-mobile">
        <div class="column">商品小計:</div>
        <div class="column has-text-right" id="summary-subtotal"><?= number_format($total_price) ?> 円</div>
    </div>
    <div class="columns is-mobile">
        <div class="column">送料:</div>
        <div class="column has-text-right" id="summary-shipping"><?= number_format($shipping_fee) ?> 円</div>
    </div>
    <hr>
    <div class="columns is-mobile">
        <div class="column is-size-4 has-text-danger has-text-weight-bold has-text-centered" id="summary-total">
            <?= number_format($total_price + $shipping_fee) ?> 円
        </div>
    </div>
</div>

<div class="buttons is-centered are-medium mt-5">
<?php if (!$is_logged_in): ?>
    <a href="L1-login.php" class="button is-danger" style="width:45%;">ログインして<br>まとめて購入</a>
    <a href="G5-order_input.php" class="button is-danger is-light" style="width:45%; background-color:#ff4b5c; color:white;">
        まとめて通常購入
    </a>
<?php else: ?>
    <a href="G5-order_input.php" class="button is-danger" style="width:60%;">
        まとめて購入
    </a>
<?php endif; ?>
</div>

<div class="notification is-light mt-5 has-text-grey is-size-7 has-text-centered">
    <p class="has-text-danger is-size-6 mb-2">カート内商品について</p>
    ショッピングカートに入った状態では、商品の在庫は確保されていません。
</div>

<?php endif; ?>

</div>
</section>

<?php require './footer.php'; ?>

<script>
function recalc() {
    const fee = 970;
    let subtotal = 0;

    document.querySelectorAll(".box[data-id]").forEach(box => {
        const check = box.querySelector(".item-check");
        if (!check.checked) return;

        const sub = parseInt(box.dataset.subtotal);
        subtotal += sub;
    });

    document.getElementById("summary-subtotal").textContent = subtotal.toLocaleString() + " 円";
    let shipping = subtotal > 0 ? fee : 0;
    document.getElementById("summary-shipping").textContent = shipping.toLocaleString() + " 円";
    document.getElementById("summary-total").textContent =
        (subtotal + shipping).toLocaleString() + " 円";
}

document.querySelectorAll(".item-check").forEach(chk => {
    chk.addEventListener("change", recalc);
});
</script>
