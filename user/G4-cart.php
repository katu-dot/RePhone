<?php
session_start();
 
require './header.php';
require '../config/db-connect.php';
 
// --- ▼ カート操作ロジック ▼ ---
 
// 1. カートに追加 (POSTリクエスト)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $pid = (int)$_POST['product_id'];
    $qty = (int)($_POST['quantity'] ?? 1);
   
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
   
    if (isset($_SESSION['cart'][$pid])) {
        $_SESSION['cart'][$pid] += $qty;
    } else {
        $_SESSION['cart'][$pid] = $qty;
    }
}
 
// 2. カートから削除 (GETリクエスト action=delete)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
   
    if (isset($_SESSION['cart'][$del_id])) {
        unset($_SESSION['cart'][$del_id]);
    }
   
    echo "<script>window.location.href = 'G4-cart.php';</script>";
    exit();
}
 
// --- ▲ カート操作ロジック ▲ ---
 
// --- ▼ 表示用データ取得ロジック ▼ ---
$cart_items = [];
$total_price = 0;
$shipping_fee = 970; // 送料
 
if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
    try {
        $pdo = new PDO($connect, USER, PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
        $product_ids = array_keys($_SESSION['cart']);
       
        if (!empty($product_ids)) {
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $sql = "SELECT p.product_id, p.product_name, p.price, p.image, pm.stock
                    FROM product p
                    LEFT JOIN product_management pm ON p.product_id = pm.product_id
                    WHERE p.product_id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($product_ids);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
            foreach ($products as $product) {
                $pid = $product['product_id'];
                $qty = $_SESSION['cart'][$pid] ?? 0;
                $stock = (int)($product['stock'] ?? 0);
 
                $subtotal = ($stock > 0) ? $product['price'] * $qty : 0; // 在庫切れは0
 
                $cart_items[] = [
                    'id' => $pid,
                    'name' => $product['product_name'],
                    'price' => $product['price'],
                    'image' => $product['image'],
                    'qty' => $qty,
                    'stock' => $stock,
                    'subtotal' => $subtotal
                ];
 
                if ($stock > 0) {
                    $total_price += $subtotal;
                }
            }
        }
 
    } catch (PDOException $e) {
        echo "<div class='notification is-danger'>エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
 
// 在庫切れ商品しかない場合は合計0
if ($total_price <= 0) {
    $total_price = 0;
}
// --- ▲ 表示用データ取得ロジック ▲ ---
?>
 
<section class="section">
    <div class="container">
       
        <h2 class="title is-4">
            <img src="../img/user-logo.jpg" alt="RePhone" style="height: 1.2em; vertical-align: middle;">
        </h2>
        <hr>
 
        <?php if (empty($cart_items)): ?>
            <div class="notification is-warning has-text-centered">
                カートに商品は入っていません。<br>
                <a href="G1-top.php" class="button is-small mt-3">お買い物に戻る</a>
            </div>
        <?php else: ?>
 
            <?php foreach ($cart_items as $item): ?>
                <div class="box">
                    <article class="media">
                        <div class="media-left" style="display:flex; align-items:center;">
                            <label class="checkbox">
                                <input type="checkbox" checked>
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
                                <p>
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                </p>
                                <div class="level is-mobile">
                                    <div class="level-left">
                                        <a href="G4-cart.php?action=delete&id=<?php echo $item['id']; ?>" class="button is-small is-white border-grey" style="border: 1px solid #ccc;">
                                            削除
                                        </a>
 
                                        <?php if ($item['stock'] > 0): ?>
                                            <?php $maxQty = min(5, $item['stock']); ?>
                                            <select class="input is-small ml-2 qty-select"
                                                    data-id="<?= $item['id'] ?>"
                                                    data-price="<?= $item['price'] ?>"
                                                    style="width: 60px;">
                                                <?php for ($i = 1; $i <= $maxQty; $i++): ?>
                                                    <option value="<?= $i ?>"
                                                        <?= ($item['qty'] == $i) ? 'selected' : '' ?> >
                                                        <?= $i ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        <?php else: ?>
                                            <p class="ml-2" style="color:red; font-weight:bold;">在庫切れ</p>
                                        <?php endif; ?>
 
                                    </div>
                                    <div class="level-right">
                                        <p class="title is-5 has-text-danger subtotal" id="subtotal-<?= $item['id'] ?>">
                                            <?= number_format($item['subtotal']); ?> 円
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
 
            <div class="box" style="background-color: #f9f9f9; border: none; box-shadow: none;">
                <div class="columns is-mobile">
                    <div class="column">商品小計:</div>
                    <div class="column has-text-right" id="total_price">
                        <?= number_format($total_price); ?> 円
                    </div>
                </div>
                <div class="columns is-mobile">
                    <div class="column">送料:</div>
                    <div class="column has-text-right"><?= number_format($shipping_fee); ?> 円</div>
                </div>
                <hr style="margin: 0.5rem 0;">
                <div class="columns is-mobile">
                    <div class="column is-size-4 has-text-danger has-text-weight-bold has-text-centered" id="grand_total">
                        <?= number_format($total_price + $shipping_fee); ?> 円
                    </div>
                </div>
            </div>
 
            <div class="buttons is-centered are-medium mt-5">
                <a href="L1-login.php" class="button is-danger" style="width: 45%;">
                    ログインして<br>購入
                </a>
                <a href="G5-order_input.php" class="button is-danger is-light" style="width: 45%; background-color: #ff4b5c; color: white;">
                    通常購入
                </a>
            </div>
 
            <div class="notification is-light mt-5 has-text-grey is-size-7 has-text-centered">
                <p class="has-text-danger is-size-6 mb-2">カート内商品について</p>
                ショッピングカートに入った状態では、商品の在庫は確保されていません。
            </div>
 
        <?php endif; ?>
 
    </div>
</section>
 
<!-- ▼ 変更した数量に応じて小計・合計をリアルタイム更新するJS ▼ -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const qtySelects = document.querySelectorAll(".qty-select");
 
    qtySelects.forEach(select => {
        select.addEventListener("change", function() {
            const id = this.dataset.id;
            const price = parseInt(this.dataset.price);
            const qty = parseInt(this.value);
 
            // 小計を更新
            const subtotalElem = document.querySelector(`#subtotal-${id}`);
            const newSubtotal = price * qty;
            subtotalElem.innerText = newSubtotal.toLocaleString() + ' 円';
 
            // 合計再計算
            let total = 0;
            document.querySelectorAll(".subtotal").forEach(el => {
                const val = parseInt(el.innerText.replace(/,/g, ""));
                if (!isNaN(val)) total += val;
            });
 
            document.getElementById("total_price").innerText = total.toLocaleString() + ' 円';
            document.getElementById("grand_total").innerText
                = (total + 970).toLocaleString() + ' 円';
        });
    });
});
</script>
 
<?php
require './footer.php';
?>

