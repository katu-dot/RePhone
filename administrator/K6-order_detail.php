<?php
// セッションを開始
session_start();

// 1. 共通ファイルの読み込み
require './header.php'; 
require '../config/db-connect.php'; 
require '../config/left-menu.php'; 

// 2. GETパラメータ（注文ID）の検証
// -----------------------------------------------------
$order = null; // 注文データを格納する変数

// 注文マスター(order_master.php)から 'id' (order_management_id) を受け取る
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='main-content'><p class='has-text-danger'>無効な注文IDです。</p></div>";
    require './footer.php';
    exit();
}
$order_id = intval($_GET['id']); // 安全な数値としてIDを取得

// 3. データベースからの詳細データ取得
// -----------------------------------------------------
try {
    // 注文(OM)、顧客(CM)、注文明細(ODM)、商品管理(PM)、商品(P)をすべて結合
    $sql = "
        SELECT 
            OM.order_date,
            OM.payment_confirmation,
            CM.name AS customer_name,
            CM.customer_management_id, -- レイアウトの「顧客番号」
            CM.phone AS customer_phone,
            CM.address AS customer_address,
            P.product_name,
            P.price,
            P.image AS product_image
        FROM 
            order_management OM
        -- 注文(OM) -> 顧客(CM)
        INNER JOIN 
            customer_management CM ON OM.customer_management_id = CM.customer_management_id
        -- 注文(OM) -> 注文明細(ODM) (型変換JOIN)
        LEFT JOIN
            order_detail_management ODM ON CAST(OM.order_management_id AS CHAR) = ODM.order_management_id
        -- 注文明細(ODM) -> 商品管理(PM) (型変換JOIN)
        LEFT JOIN
            product_management PM ON ODM.product_management_id = CAST(PM.product_management_id AS CHAR)
        -- 商品管理(PM) -> 商品(P)
        LEFT JOIN
            product P ON PM.product_id = P.product_id
        WHERE 
            OM.order_management_id = ? -- 受け取ったIDで絞り込み
        LIMIT 1 -- 注文は1件
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    // 注文が見つからない場合の処理
    if (!$order) {
        echo "<div class='main-content'><p class='has-text-danger'>指定された注文は見つかりません。</p></div>";
        require './footer.php';
        exit();
    }

} catch (PDOException $e) {
    echo "<div class='main-content'><p class='has-text-danger'>データベースエラー: " . $e->getMessage() . "</p></div>";
    require './footer.php';
    exit();
}
?>

<div class="main-content">
    <h2 class="title is-4">注文管理/注文マスター/注文マスター詳細</h2>
    <hr>
    <h3 class="subtitle is-5">注文詳細:</h3>

    <div class="columns">
        
        <div class="column is-one-third">
            <div class="card">
                <div class="card-image">
                    <figure class="image is-4by3">
                        <img src="../<?php echo htmlspecialchars($order['product_image'] ?? 'img/default.jpg'); ?>" alt="<?php echo htmlspecialchars($order['product_name']); ?>">
                    </figure>
                </div>
                <div class="card-content">
                    <p class="title is-5"><?php echo htmlspecialchars($order['product_name']); ?></p>
                    <p class="subtitle is-6 has-text-danger">¥<?php echo number_format($order['price']); ?> 円</p>
                </div>
            </div>
        </div>

        <div class="column is-two-thirds">
            <table class="table is-fullwidth is-striped">
                <tbody>
                    <tr>
                        <th>注文日</th>
                        <td><?php echo date('Y/m/d', strtotime($order['order_date'])); ?></td>
                    </tr>
                    <tr>
                        <th>顧客名</th>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?>様</td>
                    </tr>
                    <tr>
                        <th>顧客番号</th>
                        <td><?php echo htmlspecialchars($order['customer_management_id']); ?></td>
                    </tr>
                    <tr>
                        <th>電話番号</th>
                        <td><?php echo htmlspecialchars($order['customer_phone']); ?></td>
                    </tr>
                    <tr>
                        <th>住所</th>
                        <td><?php echo htmlspecialchars($order['customer_address']); ?></td>
                    </tr>
                    <tr>
                        <th>郵便番号</th>
                        <td class="has-text-grey-light">(DBに項目なし)</td>
                    </tr>
                    <tr>
                        <th>配達希望日</th>
                        <td class="has-text-grey-light">(DBに項目なし)</td>
                    </tr>
                    <tr>
                        <th>配達希望時間</th>
                        <td class="has-text-grey-light">(DBに項目なし)</td>
                    </tr>
                    <tr>
                        <th>入金状況</th>
                        <td>
                            <span class="tag <?php echo ($order['payment_confirmation'] === '入金済み') ? 'is-success' : 'is-danger'; ?>">
                                <?php echo htmlspecialchars($order['payment_confirmation']); ?>
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
    </div>
</div>

<?php 
// 4. フッターの読み込みとDB切断
require './footer.php'; 
?>