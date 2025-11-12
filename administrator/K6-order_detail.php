<?php
// --- ▼ デバッグ用：エラーを強制的に表示 ▼ ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
// --- ▲ デバッグ用 ▲ ---

session_start();

// 1. 共通ファイルの読み込み
require './header.php'; 
require '../config/db-connect.php'; 
// --- ▼ DB接続処理（K3スタイル）▼ ---
try {
    $pdo = new PDO($connect, USER, PASS); 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "<div class='notification is-danger'>データベース接続エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit();
}
// --- ▲ DB接続処理 ▲ ---


// 2. GETパラメータ（注文ID）の検証
// -----------------------------------------------------
$order = null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='main-content'><p class='has-text-danger'>無効な注文IDです。</p></div>";
    require './footer.php';
    exit();
}
$order_id = intval($_GET['id']); 

// 3. データベースからの詳細データ取得 (本来のクエリに戻す)
// -----------------------------------------------------
try {
    // --- ▼ 修正されたSQLクエリ：単一の注文IDで絞り込み、全詳細情報を取得 ▼ ---
    $sql = "
        -- ---
        -- 注文詳細ページ (order_detail.php) 用のSELECTクエリ
        -- ---
        SELECT 
            OM.order_date,
            OM.payment_confirmation,
            OM.delivery_date,
            OM.delivery_time,
            CM.name AS customer_name,
            CM.customer_management_id,
            CM.phone AS customer_phone,
            CM.address AS customer_address,
            CM.postal_code,
            P.product_name,
            P.price,
            P.image AS product_image
        FROM 
            order_management OM
        INNER JOIN 
            customer_management CM ON OM.customer_management_id = CM.customer_management_id
        LEFT JOIN
            order_detail_management ODM ON CAST(OM.order_management_id AS CHAR) = ODM.order_management_id
        LEFT JOIN
            product_management PM ON ODM.product_management_id = CAST(PM.product_management_id AS CHAR)
        LEFT JOIN
            product P ON PM.product_id = P.product_id
        WHERE 
            OM.order_management_id = ? -- プレースホルダ
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id]); // ★配列渡し
    $order = $stmt->fetch(PDO::FETCH_ASSOC); // ★1件だけ取得

    if (!$order) {
        echo "<div class='main-content'><p class='has-text-danger'>指定された注文ID ({$order_id}) の詳細情報が見つかりませんでした。</p></div>";
        require './footer.php';
        exit();
    }

} catch (PDOException $e) {
    echo "<div class='main-content'><p class='has-text-danger'>データベースエラー: " . htmlspecialchars($e->getMessage()) . "</p></div>";
    require './footer.php';
    exit();
}
?>

<div class="columns">
    
    <?php require '../config/left-menu.php'; ?>

    <div class="column" style="padding: 2rem;">

        <h2 class="title is-4">注文管理/注文マスター/注文マスター詳細</h2>
        <hr>
        <h3 class="subtitle is-5">注文詳細:</h3>

        <div class="columns">
            
            <div class="column is-one-third">
                <div class="card">
                    <div class="card-image">
                    <figure class="image is-4by3">
    <?php
        // 画像のパスを設定
        $imageBaseUrl = '../img/';
$imageFilename = ltrim($order['product_image'] ?? '', '/');
$imagePath = $imageBaseUrl . $imageFilename;

if (!empty($order['product_image'])) {
    echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($order['product_name']) . '">';
} else {
    echo '<img src="' . $imageBaseUrl . 'noimage.png" alt="画像なし">';
}

    ?>
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
                            <td><?php echo htmlspecialchars($order['postal_code'] ?? '未設定'); ?></td>
                        </tr>
                        <tr>
                            <th>配達希望日</th>
                            <td><?php echo htmlspecialchars($order['delivery_date'] ?? '未設定'); ?></td>
                        </tr>
                        <tr>
                            <th>配達希望時間</th>
                            <td><?php echo htmlspecialchars($order['delivery_time'] ?? '未設定'); ?></td>
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
</div>

<?php 
require './footer.php'; 
?>