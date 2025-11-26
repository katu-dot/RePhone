<?php
session_start();
require './header.php';
require '../config/db-connect.php';

// ▼ ログイン判定
$is_logged_in = isset($_SESSION['user_id']);

// ▼ カートと直接購入の判定
$cart_items = [];
$total_price = 0;
$shipping_fee = 970;

// ▼ 自動入力変数（初期値空）
$name = $email = $phone = $postal_code = $address = $street_address = "";

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ▼ 直接購入 → 最優先
    if (!empty($_POST['direct_purchase']) && !empty($_POST['product_management_id'])) {
        $pid = (int)$_POST['product_management_id'];
        $qty = max(1, (int)$_POST['quantity']);
        $product_ids = [$pid];
        $quantities = [$pid => $qty];

    } elseif (!empty($_SESSION['cart'])) {
        $product_ids = array_keys($_SESSION['cart']);
        $quantities = $_SESSION['cart'];
    } else {
        echo "<script>alert('カートに商品が入っていません'); window.location.href='G1-top.php';</script>";
        exit();
    }

    // ▼ ログインユーザー情報自動入力
    if ($is_logged_in) {
        $stmt = $pdo->prepare("SELECT user_name, email, phone, postal_code, address, street_address
                               FROM user WHERE user_id = :user_id LIMIT 1");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_info) {
            $name = $user_info["user_name"];
            $email = $user_info["email"];
            $phone = $user_info["phone"];
            $postal_code = $user_info["postal_code"];
            $address = $user_info["address"];
            $street_address = $user_info["street_address"];
        }
    }

    // ▼ 商品情報取得（JOINで accessories / status / shipping）
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $sql = "SELECT pm.product_management_id, p.product_id, p.product_name, p.price, p.image,
                   pm.stock,
                   a.accessories_name AS accessories,
                   s.status_name AS status,
                   sh.shipping_date
            FROM product_management pm
            LEFT JOIN product p ON pm.product_id = p.product_id
            LEFT JOIN accessories a ON pm.accessories_id = a.accessories_id
            LEFT JOIN status s ON pm.status_id = s.status_id
            LEFT JOIN shipping sh ON pm.shipping_id = sh.shipping_id
            WHERE pm.product_management_id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($product_ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $pid = $row['product_management_id'];
        $qty = $quantities[$pid] ?? 1;

        $cart_items[] = [
            'product_management_id' => $row['product_management_id'],
            'product_id' => $row['product_id'],
            'name' => $row['product_name'],
            'qty'  => $qty,
            'price'=> $row['price'],
            'subtotal' => $row['price'] * $qty,
            'stock' => $row['stock'] ?? 0,
            'accessories' => $row['accessories'] ?? '―',
            'status' => $row['status'] ?? '―',
            'shipping_date' => $row['shipping_date'] ?? '―'
        ];
        $total_price += $row['price'] * $qty;
    }

} catch (PDOException $e) {
    echo "<div class='notification is-danger'>エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<section class="section">
<div class="container has-text-centered">

    <h2 class="title is-4">ご注文情報入力</h2>
    <hr>

    <form action="G6-order_confilm.php" method="POST">

        <!-- ▼ カート商品一覧 -->
        <div class="box mb-4" style="max-width:600px; margin:0 auto;">
            <h3 class="title is-5">───ご注文商品───</h3>
            <table class="table is-fullwidth is-bordered has-text-centered">
                <thead>
                    <tr>
                        <th>商品名</th>
                        <th>数量</th>
                        <th>小計</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($item['name']); ?>
                                <?php if ($item['stock'] <= 0): ?>
                                    <br><span class="has-text-danger">現在在庫切れです。入荷までしばらくお待ちください</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $item['qty']; ?></td>
                            <td>¥<?= number_format($item['subtotal']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- ▼ hidden input に全情報送信 -->
            <?php foreach ($cart_items as $i => $item): ?>
                <?php foreach ($item as $k => $v): ?>
                    <input type="hidden" name="items[<?= $i ?>][<?= htmlspecialchars($k) ?>]"
                           value="<?= htmlspecialchars($v) ?>">
                <?php endforeach; ?>
            <?php endforeach; ?>

            <input type="hidden" name="total_price" value="<?= $total_price + $shipping_fee ?>">
            <input type="hidden" name="shipping_fee" value="<?= $shipping_fee ?>">

            <p class="has-text-centered is-size-5 has-text-danger">
                合計：¥<?= number_format($total_price + $shipping_fee); ?>（送料込）
            </p>
        </div>

        <!-- ▼ 直接購入 hidden -->
        <?php if (!empty($_POST['direct_purchase'])): ?>
            <input type="hidden" name="direct_purchase" value="1">
            <input type="hidden" name="product_management_id" value="<?= (int)$_POST['product_management_id']; ?>">
            <input type="hidden" name="quantity" value="<?= (int)$_POST['quantity']; ?>">
        <?php endif; ?>

        <!-- ▼ 注文情報フォーム -->
        <div class="box" style="max-width:500px; margin:0 auto;">
            <h3 class="title is-5">───お客様情報───</h3>
            <div class="field">
                <label class="label">氏名</label>
                <div class="control">
                    <input class="input is-centered" type="text" name="name"
                           value="<?= htmlspecialchars($name); ?>" required>
                </div>
            </div>

            <div class="field">
                <label class="label">メールアドレス</label>
                <div class="control">
                    <input class="input is-centered" type="email" name="email"
                           value="<?= htmlspecialchars($email); ?>" required>
                </div>
            </div>

            <div class="field">
                <label class="label">電話番号</label>
                <div class="control">
                    <input class="input is-centered" type="tel" name="phone"
                           value="<?= htmlspecialchars($phone); ?>" required>
                </div>
            </div>

            <hr>

            <h3 class="title is-5">───配送情報───</h3>
            <div class="field">
                <label class="label">郵便番号</label>
                <div class="control">
                    <input class="input is-centered" type="text" name="postal_code" id="postal_code"
                           value="<?= htmlspecialchars($postal_code); ?>" required>
                </div>
            </div>

            <div class="field">
                <label class="label">住所</label>
                <div class="control">
                    <input class="input is-centered" type="text" name="address" id="address"
                           value="<?= htmlspecialchars($address); ?>" required>
                </div>
            </div>

            <div class="field">
                <label class="label">番地</label>
                <div class="control">
                    <input class="input is-centered" type="text" name="street_address" id="street_address"
                           value="<?= htmlspecialchars($street_address); ?>" required>
                </div>
            </div>

            <div class="field">
                <label class="label">配達希望日</label>
                <div class="control">
                    <input class="input is-centered" type="date" name="delivery_date">
                </div>
            </div>

            <div class="field">
                <label class="label">配達希望時間</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="delivery_time">
                            <option value="">指定なし</option>
                            <option value="午前中">午前中</option>
                            <option value="12時～14時">12時～14時</option>
                            <option value="14時～16時">14時～16時</option>
                            <option value="16時～18時">16時～18時</option>
                            <option value="18時～20時">18時～20時</option>
                            <option value="19時～21時">19時～21時</option>
                        </select>
                    </div>
                </div>
            </div>

            <hr>

            <h3 class="title is-5">───お支払い方法───</h3>
            <div class="field">
                <label class="label">お支払方法</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="payment_method" required>
                            <option value="">指定なし</option>
                            <option value="クレジットカード決済">クレジットカード決済</option>
                            <option value="コンビニ決済">コンビニ決済</option>
                            <option value="代金引換">代金引換</option>
                            <option value="キャリア決済">キャリア決済</option>
                            <option value="銀行振込">銀行振込</option>
                            <option value="電子マネー決済">電子マネー決済</option>
                            <option value="後払い決済">後払い決済</option>
                            <option value="ID決済">ID決済</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="field is-grouped is-grouped-centered mt-5">
                <div class="control">
                    <button type="submit" class="button is-danger is-medium">確認画面へ</button>
                </div>
            </div>
        </div>
    </form>
</div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const postal_codeInput = document.getElementById('postal_code');
    const addressInput = document.getElementById('address');

    postal_codeInput.addEventListener('blur', function() {
        const postal_code = this.value.replace(/\D/g, '');
        if (postal_code.length === 7) {
            fetch(`https://zipcloud.ibsnet.co.jp/api/search?zipcode=${postal_code}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 200 && data.results) {
                    const result = data.results[0];
                    addressInput.value = result.address1 + result.address2 + result.address3;
                } else {
                    addressInput.value = '';
                    alert('郵便番号が存在しません。正しい番号を入力してください。');
                }
            })
            .catch(err => {
                console.error('郵便番号APIエラー', err);
                alert('住所を取得できませんでした。後で手動入力してください。');
            });
        } else if (postal_code.length > 0) {
            addressInput.value = '';
            alert('郵便番号は7桁で入力してください。');
        }
    });
});
</script>

<?php require './footer.php'; ?>
