<?php
// 作者：勝原優太郎
require '../config/db-connect.php';
require 'header.php';

try {
    // DB接続
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 総会員数取得
    $stmt = $pdo->query("SELECT COUNT(*) AS total_customers FROM customer_management");
    $total_customers = $stmt->fetch(PDO::FETCH_ASSOC)['total_customers'] ?? 0;

    // 在庫切れ商品数取得
    $stmt2 = $pdo->query("SELECT COUNT(*) AS out_of_stock_count FROM product_management WHERE stock = 0");
    $out_of_stock_count = $stmt2->fetch(PDO::FETCH_ASSOC)['out_of_stock_count'] ?? 0;

    // 注文状況取得
    $stmt3 = $pdo->query("SELECT 
        SUM(payment_confirmation = '入金済み') AS paid_count,
        SUM(payment_confirmation = '未入金') AS unpaid_count
    FROM order_management");
    $order_counts = $stmt3->fetch(PDO::FETCH_ASSOC);

    $paid_count   = $order_counts['paid_count'] ?? 0;
    $unpaid_count = $order_counts['unpaid_count'] ?? 0;

    // 新規受付かつメール未送信の件数
    $stmt4 = $pdo->query("
        SELECT COUNT(*) AS pending_email_count
        FROM order_management
        WHERE email_sent = 0
        AND cancelled_at IS NULL
    ");
    $pending_email_count = $stmt4->fetch(PDO::FETCH_ASSOC)['pending_email_count'] ?? 0;

    /* ▼━━━━━━━━━━━━━━━━━━━━━━
       売上状況の取得（入金済みのみ）
       ━━━━━━━━━━━━━━━━━━━━━━━━ */

    // 今までの売上
    $stmt5 = $pdo->query("
    SELECT 
        COALESCE(SUM(P.price), 0) AS total_sales,
        COUNT(P.product_id) AS total_sales_items
    FROM order_management OM
    LEFT JOIN order_detail_management ODM 
        ON OM.order_management_id = ODM.order_management_id
    LEFT JOIN product_management PM
        ON ODM.product_management_id = PM.product_management_id
    LEFT JOIN product P
        ON PM.product_id = P.product_id
    WHERE OM.payment_confirmation = '入金済み'
    AND OM.cancelled_at IS NULL
    ");
    $sales = $stmt5->fetch(PDO::FETCH_ASSOC);

    $total_sales       = $sales['total_sales'] ?? 0;
    $total_sales_items = $sales['total_sales_items'] ?? 0;

    // 今月の売上
    $stmt6 = $pdo->query("
    SELECT 
        COALESCE(SUM(P.price), 0) AS monthly_sales,
        COUNT(P.product_id) AS monthly_sales_items
    FROM order_management OM
    LEFT JOIN order_detail_management ODM 
        ON OM.order_management_id = ODM.order_management_id
    LEFT JOIN product_management PM
        ON ODM.product_management_id = PM.product_management_id
    LEFT JOIN product P
        ON PM.product_id = P.product_id
    WHERE OM.payment_confirmation = '入金済み'
    AND DATE_FORMAT(OM.order_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
    AND OM.cancelled_at IS NULL
    ");

    $monthly = $stmt6->fetch(PDO::FETCH_ASSOC);

    $monthly_sales       = $monthly['monthly_sales'] ?? 0;
    $monthly_sales_items = $monthly['monthly_sales_items'] ?? 0;

    // 今日の売上
    $stmt7 = $pdo->query("
    SELECT 
        COALESCE(SUM(P.price), 0) AS daily_sales,
        COUNT(P.product_id) AS daily_sales_items
    FROM order_management OM
    LEFT JOIN order_detail_management ODM 
        ON OM.order_management_id = ODM.order_management_id
    LEFT JOIN product_management PM
        ON ODM.product_management_id = PM.product_management_id
    LEFT JOIN product P
        ON PM.product_id = P.product_id
    WHERE OM.payment_confirmation = '入金済み'
    AND DATE(OM.order_date) = CURDATE()
    AND OM.cancelled_at IS NULL
    ");

    $daily = $stmt7->fetch(PDO::FETCH_ASSOC);

    $daily_sales       = $daily['daily_sales'] ?? 0;
    $daily_sales_items = $daily['daily_sales_items'] ?? 0;

} catch (PDOException $e) {
    echo '<div class="notification is-danger">DB接続エラー: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}
?>

<div class="columns">
<?php require '../config/left-menu.php'; ?>

<div class="column" style="padding: 2rem; background: #f5f7fa; min-height: 100vh;">

    <!-- ホームカード全体 -->
    <div class="box" style="border-radius: 12px; padding: 2rem;">
        <h1 class="title is-4">ホーム</h1>

        <!-- 注文状況カード -->
        <div class="box" style="border: 2px solid #444; border-radius: 12px; margin-bottom: 2rem;">
            <h2 class="title is-5" style="margin-bottom: 1rem;">注文状況</h2>

            <!-- 新規受付（メール未送信） -->
            <div class="columns is-mobile" style="padding: 0.5rem 1rem; border-bottom: 1px solid #ddd;">
                <div class="column is-6 has-text-weight-semibold">
                    <a href="K5-order_master.php?category=email_pending" style="text-decoration:none; color:inherit;">注文新規受付</a>
                </div>
                <div class="column is-6 has-text-right">
                    <a href="K5-order_master.php?category=email_pending" style="text-decoration:none; color:inherit;"><?= number_format($pending_email_count) ?> ＞</a>
                </div>
            </div>

            <!-- 入金済み -->
            <div class="columns is-mobile" style="padding: 0.5rem 1rem; border-bottom: 1px solid #ddd;">
                <div class="column is-6 has-text-weight-semibold">
                    <a href="K5-order_master.php?category=paid" style="text-decoration:none; color:inherit;">入金済み</a>
                </div>
                <div class="column is-6 has-text-right">
                    <a href="K5-order_master.php?category=paid" style="text-decoration:none; color:inherit;"><?= number_format($paid_count) ?> ＞</a>
                </div>
            </div>

            <!-- 未入金 -->
            <div class="columns is-mobile" style="padding: 0.5rem 1rem;">
                <div class="column is-6 has-text-weight-semibold">
                    <a href="K5-order_master.php?category=pending" style="text-decoration:none; color:inherit;">未入金</a>
                </div>
                <div class="column is-6 has-text-right">
                    <a href="K5-order_master.php?category=pending" style="text-decoration:none; color:inherit;"><?= number_format($unpaid_count) ?> ＞</a>
                </div>
            </div>
        </div>

        <!-- 売上状況 & ショップ状況 -->
        <div class="columns">
            <!-- 売上状況 -->
            <div class="column">
                <div class="box" style="border: 2px solid #444; border-radius: 12px;">
                    <h2 class="title is-5 has-text-centered">売上状況</h2>

                    <div class="has-text-centered" style="margin-bottom: 1.5rem;">
                        <p class="has-text-weight-bold">
                            ￥<?= number_format($total_sales) ?> / <?= number_format($total_sales_items) ?>件
                        </p>
                        <p class="is-size-7">累計売上高 / 売上件数</p>
                    </div>

                    <div class="has-text-centered" style="margin-bottom: 1.5rem;">
                        <p class="has-text-weight-bold">
                            ￥<?= number_format($monthly_sales) ?> / <?= number_format($monthly_sales_items) ?>件
                        </p>
                        <p class="is-size-7">今月の売上高 / 売上件数</p>
                    </div>

                    <div class="has-text-centered">
                        <p class="has-text-weight-bold">
                            ￥<?= number_format($daily_sales) ?> / <?= number_format($daily_sales_items) ?>件
                        </p>
                        <p class="is-size-7">今日の売上高 / 売上件数</p>
                    </div>
                </div>
            </div>

            <!-- ショップ状況 -->
            <div class="column">
                <div class="box" style="border: 2px solid #444; border-radius: 12px;">
                    <h2 class="title is-5 has-text-centered">ショップ状況</h2>

                    <div class="has-text-centered" style="padding-bottom: 1rem; border-bottom: 1px solid #ddd;">
                        <a href="K7-product_master.php?out_of_stock=1" style="text-decoration:none; color:inherit;">
                            <p class="has-text-weight-bold">
                                在庫切れ商品　<?= number_format($out_of_stock_count) ?> ＞
                            </p>
                        </a>
                    </div>

                    <div class="has-text-centered" style="padding-top: 1rem;">
                        <a href="K3-customer_master.php" style="text-decoration:none; color:inherit;">
                            <p class="has-text-weight-bold">会員数　<?= number_format($total_customers) ?> ＞</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
</div>

<?php require 'footer.php'; ?>
