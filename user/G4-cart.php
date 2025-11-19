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
    
    // 指定されたIDをセッションから削除
    if (isset($_SESSION['cart'][$del_id])) {
        unset($_SESSION['cart'][$del_id]);
    }
    
    // ▼▼▼ 修正点：削除後に自分自身へリダイレクトし、処理を終了する ▼▼▼
    // ※ header.php で既にHTMLが出力されている場合、このheader()関数はエラーになります。
    // もし "headers already sent" エラーが出る場合は、JavaScriptでのリダイレクトに切り替える必要があります。
    
    // PHPでのリダイレクト (基本)
    // header("Location: G4-cart.php");
    // exit();
    
    // 代替案：JavaScriptでのリダイレクト (HTML出力後でも動作する安全策)
    echo "<script>window.location.href = 'G4-cart.php';</script>";
    exit();
    // ▲▲▲ 修正点 ▲▲▲
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
        
        // 商品IDが1つもない場合はSQLエラーになるためチェック
        if (!empty($product_ids)) {
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $sql = "SELECT product_id, product_name, price, image FROM product WHERE product_id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($product_ids);
            
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($products as $product) {
                $pid = $product['product_id'];
                // セッションにキーが存在するか再確認 (削除直後のエラー防止)
                if (isset($_SESSION['cart'][$pid])) {
                    $qty = $_SESSION['cart'][$pid];
                    $subtotal = $product['price'] * $qty;
                    
                    $cart_items[] = [
                        'id' => $pid,
                        'name' => $product['product_name'],
                        'price' => $product['price'],
                        'image' => $product['image'],
                        'qty' => $qty,
                        'subtotal' => $subtotal
                    ];
                    $total_price += $subtotal;
                }
            }
        }

    } catch (PDOException $e) {
        echo "<div class='notification is-danger'>エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
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
                                        <input class="input is-small ml-2" type="number" value="<?php echo $item['qty']; ?>" style="width: 60px;" readonly>
                                    </div>
                                    <div class="level-right">
                                        <p class="title is-5 has-text-danger">
                                            <?php echo number_format($item['price']); ?> 円
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
                    <div class="column has-text-right"><?php echo number_format($total_price); ?> 円</div>
                </div>
                <div class="columns is-mobile">
                    <div class="column">送料:</div>
                    <div class="column has-text-right"><?php echo number_format($shipping_fee); ?> 円</div>
                </div>
                <hr style="margin: 0.5rem 0;">
                <div class="columns is-mobile">
                    <div class="column is-size-4 has-text-danger has-text-weight-bold has-text-centered">
                        <?php echo number_format($total_price + $shipping_fee); ?> 円
                    </div>
                </div>
            </div>

            <div class="buttons is-centered are-medium mt-5">
                <a href="L1-login.php" class="button is-danger" style="width: 45%;">
                    ログインして<br>購入
                </a>
                <a href="G5-order_input1.php" class="button is-danger is-light" style="width: 45%; background-color: #ff4b5c; color: white;">
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

<?php 
require './footer.php'; 
?>