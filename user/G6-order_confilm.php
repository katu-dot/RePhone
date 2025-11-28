<?php
require './header.php';
require '../config/db-connect.php';
session_start();

// ▼ G5 から POST が来ていない場合は入力画面へ戻す
if (!isset($_POST['items']) || !is_array($_POST['items']) || count($_POST['items']) === 0) {
    echo "<script>alert('注文内容が正しく受け取れませんでした'); window.location.href='G5-order_input.php';</script>";
    exit();
}

$data = $_POST;
$cart_items = $data['items'];
$total_price = 0;
$shipping_fee = $data['shipping_fee'] ?? 970;

// ▼ 合計計算（POSTの subtotal を利用）
foreach ($cart_items as $item) {
    $subtotal = isset($item['subtotal']) ? intval($item['subtotal']) : 0;
    $total_price += $subtotal;
}
?>

<section class="section">
<div class="container has-text-centered">

    <h2 class="title is-4">ご注文内容確認</h2>
    <hr>

    <!-- ▼ ご注文商品一覧 ▼ -->
    <div class="box mb-4" style="max-width:600px; margin:0 auto;">
        <h3 class="title is-5">───ご注文商品───</h3>
        <table class="table is-fullwidth is-bordered has-text-centered">
            <thead>
                <tr>
                    <th>商品名</th>
                    <th>数量</th>
                    <th>小計</th>
                    <th>商品番号</th>
                    <th>付属品</th>
                    <th>状態</th>
                    <th>発送日</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($cart_items as $item): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($item['name'] ?? '') ?>
                        <?php if (isset($item['stock']) && $item['stock'] <= 0): ?>
                            <br><span class="has-text-danger">現在在庫切れです。入荷までしばらくお待ちください</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($item['qty'] ?? '') ?></td>
                    <td>¥<?= number_format(intval($item['subtotal'] ?? 0)) ?></td>
                    <td><?= htmlspecialchars($item['product_id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($item['accessories'] ?? '―') ?></td>
                    <td><?= htmlspecialchars($item['status'] ?? '―') ?></td>
                    <td><?= htmlspecialchars($item['shipping_date'] ?? '―') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <p class="has-text-centered is-size-5 has-text-danger">
            合計：¥<?= number_format($total_price + $shipping_fee) ?>（送料込）
        </p>
    </div>

    <!-- ▼ お客様情報・配送・支払い ▼ -->
    <div class="box" style="max-width:500px; margin:0 auto; text-align:center;">

        <!-- ▼ お客様情報 ▼ -->
        <h3 class="title is-5">───お客様情報───</h3>
        <p>氏名：<?= htmlspecialchars($data['name'] ?? '') ?></p>
        <p>メール：<?= htmlspecialchars($data['email'] ?? '') ?></p>
        <p>電話番号：<?= htmlspecialchars($data['phone'] ?? '') ?></p>

        <hr>

        <!-- ▼ 配送情報 ▼ -->
        <h3 class="title is-5">───配送情報───</h3>
        <p>郵便番号：<?= htmlspecialchars($data['postal_code'] ?? '') ?></p>
        <p>住所：<?= htmlspecialchars($data['address'] ?? '') ?></p>
        <p>番地：<?= htmlspecialchars($data['street_address'] ?? '') ?></p>
        <p>配達希望日：<?= htmlspecialchars($data['delivery_date'] ?: '指定なし') ?></p>
        <p>配達希望時間：<?= htmlspecialchars($data['delivery_time'] ?: '指定なし') ?></p>

        <hr>

        <!-- ▼ お支払い方法 ▼ -->
        <h3 class="title is-5">───お支払い方法───</h3>
        <p><?= htmlspecialchars($data['payment_method'] ?? '') ?></p>

        <!-- ▼ 確定ボタン ▼ -->
        <form action="G7-order_complete.php" method="POST" style="margin-top:1.5rem;">

            <!-- ▼ 商品以外の情報をそのまま送る -->
            <?php foreach ($data as $key => $value): ?>
                <?php if ($key !== 'items'): ?>
                    <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- ▼ 商品情報をそのまま転送（商品管理IDや付属品、状態、発送日も含む） -->
            <?php foreach ($cart_items as $i => $item): ?>
                <?php
                $fields = ['product_management_id','product_id','name','price','qty','subtotal','accessories','status','shipping_date'];
                foreach ($fields as $field):
                    $val = $item[$field] ?? '';
                ?>
                    <input type="hidden"
                           name="items[<?= $i ?>][<?= $field ?>]"
                           value="<?= htmlspecialchars($val) ?>">
                <?php endforeach; ?>
            <?php endforeach; ?>

            <div class="field is-grouped is-grouped-centered mt-4">
                <div class="control">
                    <a href="G5-order_input.php" class="button is-light is-medium">
                        修正する
                    </a>
                </div>
                <div class="control">
                    <button type="submit" class="button is-danger is-medium">
                        この内容で注文する
                    </button>
                </div>
            </div>
        </form>

    </div>

</div>
</section>

<?php require './footer.php'; ?>
