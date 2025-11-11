<?php
// --- ▼ デバッグ用：エラーを強制的に表示 ▼ ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
// --- ▲ デバッグ用 ▲ ---

session_start();

require './header.php'; 
require '../config/db-connect.php'; // $connect, USER, PASS が読み込まれる

// --- ▼ DB接続処理（PDOオブジェクトの作成）を追加 ▼ ---
try {
    // db-connect.php の変数を使って $pdo を作成
    $pdo = new PDO($connect, USER, PASS); 
    
    // エラーモードを「例外(Exception)」に設定
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // 接続失敗時の処理
    echo "<div class='notification is-danger'>データベース接続エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit(); // 処理を停止
}
// --- ▲ 修正点 ▲ ---


// 2. 検索処理のロジック
// (これ以降のコードは変更なし)
$search_query = '';
$orders = []; 
$result_count = 0;

if (isset($_GET['q']) && $_GET['q'] !== '') {
    $search_query = trim(htmlspecialchars($_GET['q']));
    $like_query = '%' . $search_query . '%';
} else {
    $like_query = '%%'; 
}

// ... (PHPロジックの上部省略) ...

try {
    // --- ▼ SQLクエリ (変更なし) ▼ ---
    $sql = "
        SELECT 
            OM.*, 
            CM.name AS customer_name,
            P.product_name, 
            P.price 
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
            CM.name LIKE :q_name OR 
            P.product_name LIKE :q_product OR  
            CAST(OM.order_management_id AS CHAR) LIKE :q_orderid
        GROUP BY
            OM.order_management_id
        ORDER BY 
            OM.order_date DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    
    // --- ▼ 修正点：execute()に配列で値を渡す (bindParamを削除) ▼ ---
    $stmt->execute([
        ':q_name'    => $like_query,
        ':q_product' => $like_query,
        ':q_orderid' => $like_query
    ]);
    // --- ▲ 修正点 ▲ ---
    
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result_count = count($orders);

} catch (PDOException $e) {
    // SQLエラーが発生した場合はここに到達し、エラーメッセージが表示されます
    echo "<div class='notification is-danger' style='margin: 20px;'>";
    echo "<h2 class='title is-4'>データベースエラーが発生しました</h2>";
    echo "<p><strong>エラー詳細:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
// ... (HTML部分省略) ...
?>

<section class="section">
    <div class="container is-fluid">
        <div class="columns">
            
            <?php require '../config/left-menu.php'; ?>

            <div class="column" style="padding: 2rem;">

                <h2 class="title is-4">注文管理/注文マスター</h2>
                <hr>
                <h3 class="subtitle is-5">基本情報</h3>
                <hr>

                <form action="" method="GET">
                    <div class="field is-grouped">
                        <p class="control">
                            <label class="label">注文検索：</label>
                        </p>
                        <p class="control is-expanded">
                            <input class="input" type="text" name="q" placeholder="ワード検索" value="<?php echo htmlspecialchars($search_query); ?>">
                        </p>
                        <p class="control">
                            <button type="submit" class="button is-info">検索</button>
                        </p>
                        
                        <p class="control">
                            <span class="select">
                                <select name="category">
                                    <option value="">すべてのカテゴリ</option>
                                </select>
                            </span>
                        </p>
                        <p class="control">
                            <span class="select">
                                <select name="sort">
                                    <option value="default">並べ替え: デフォルト</option>
                                </select>
                            </span>
                        </p>
                    </div>
                </form>
                <?php if ($search_query !== '' && $result_count > 0): // 検索時のみ件数表示 ?>
                    <h3 class="subtitle is-5 mt-5">注文検索：<?php echo $result_count; ?>件が該当しました</h3>
                    <hr>
                <?php endif; ?>

                <div class="order-list columns is-multiline">
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): ?>
                            <div class="column is-one-third">
                                <div class="card">
                                    <div class="card-content">
                                        <a href="K6-order_detail.php?id=<?php echo htmlspecialchars($order['order_management_id']); ?>">
                                            <p class="title is-6"><?php echo htmlspecialchars($order['product_name'] ?? '商品情報なし'); ?></p>
                                            <p class="subtitle is-7">¥<?php echo number_format($order['price'] ?? 0); ?> 円</p>
                                            <p>付属品: (別途取得)</p>
                                            <p>状態: (別途取得)</p>
                                            <hr style="margin: 10px 0;">
                                            <p>入金状況: <span class="<?php echo ($order['payment_confirmation'] === '入金済み') ? 'has-text-success' : 'has-text-danger'; ?>"><?php echo htmlspecialchars($order['payment_confirmation']); ?></span></p>
                                            <p>氏名: <?php echo htmlspecialchars($order['customer_name'] ?? '不明'); ?>様</p>
                                            <p>注文日: <?php echo date('Y/m/d', strtotime($order['order_date'])); ?></p>
                                        </a>
                                    </div>
                                </div> </div>
                        <?php endforeach; ?>
                    <?php elseif ($search_query !== ''): // 検索して0件だった場合 ?>
                        <div class="column is-full">
                            <p class="has-text-centered has-text-grey-light">該当する注文は見つかりませんでした。</p>
                        </div>
                    <?php else: // 初期表示で0件だった場合 ?>
                        <div class="column is-full">
                            <p class="has-text-centered has-text-grey-light">現在、登録されている注文情報はありません。</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php 
require './footer.php'; 
?>