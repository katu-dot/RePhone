<?php
// セッションを開始
session_start();

// 1. 共通ファイルの読み込み
require './header.php'; 
// DB接続ファイル (ご提示のファイル名に合わせました)
require '../config/db-connect.php'; 
// 左メニューファイル
require '../config/left-menu.php'; 

// 2. 検索処理のロジック
// -----------------------------------------------------
$search_query = '';
$orders = []; // 検索結果を格納する配列
$result_count = 0;

// GETリクエストで検索ワードが送信されたかチェック
if (isset($_GET['q']) && $_GET['q'] !== '') {
    $search_query = trim(htmlspecialchars($_GET['q']));
    
    // LIKE検索のためのワイルドカードを追加
    $like_query = '%' . $search_query . '%';

    try {
        // --- ▼ SQLクエリの修正 ▼ ---
        $sql = "
            SELECT 
                OM.*, 
                CM.name AS customer_name,
                P.product_name,  -- [修正] productテーブル(P)から取得
                P.price          -- [修正] productテーブル(P)から取得
            FROM 
                order_management OM
            INNER JOIN 
                customer_management CM ON OM.customer_management_id = CM.customer_management_id
            LEFT JOIN
                -- [修正] INT型のOM.order_management_idをCHAR型に変換してVARCHAR型とJOIN
                order_detail_management ODM ON CAST(OM.order_management_id AS CHAR) = ODM.order_management_id
            LEFT JOIN
                -- [修正] VARCHAR型のODM.product_management_idをCHAR型に変換してINT型とJOIN
                -- (※DB環境によっては CAST(ODM.product_management_id AS SIGNED) の方が良い場合もあります)
                product_management PM ON ODM.product_management_id = CAST(PM.product_management_id AS CHAR)
            LEFT JOIN
                -- [追加] 商品名と価格を取得するためにproductテーブル(P)にJOIN
                product P ON PM.product_id = P.product_id
            WHERE 
                CM.name LIKE :q_name OR 
                P.product_name LIKE :q_product OR  -- [修正] productテーブル(P)を検索
                CAST(OM.order_management_id AS CHAR) LIKE :q_orderid -- [修正] 検索時もCAST
            GROUP BY
                OM.order_management_id
            ORDER BY 
                OM.order_date DESC
        ";
        // --- ▲ SQLクエリの修正 ▲ ---
        
        $stmt = $pdo->prepare($sql);
        // プレースホルダに値をバインド
        $stmt->bindParam(':q_name', $like_query);
        $stmt->bindParam(':q_product', $like_query);
        $stmt->bindParam(':q_orderid', $like_query);
        
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result_count = count($orders);

    } catch (PDOException $e) {
        $error_message = 'データベースエラーが発生しました。';
        // 開発中はエラー内容を表示するとデバッグに役立ちます
        // echo $error_message . $e->getMessage(); 
    }
}
?>

<div class="main-content">
    <h2 class="title is-4">注文管理/注文マスター</h2>
    <hr>
    <h3 class="subtitle is-5">基本情報</h3>
    <hr>

    <form action="order_master.php" method="GET">
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
    
    <?php if ($search_query !== ''): ?>
        <h3 class="subtitle is-5 mt-5">注文検索：<?php echo $result_count; ?>件が該当しました</h3>
        <hr>
    <?php endif; ?>

    <div class="order-list columns is-multiline">
        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
                <div class="column is-one-third">
                    <div class="card">
                        <div class="card-content">
                            <a href="order_detail.php?id=<?php echo htmlspecialchars($order['order_management_id']); ?>">
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
                    </div>
                </div>
            <?php endforeach; ?>
        <?php elseif ($search_query !== ''): ?>
            <div class="column is-full">
                <p class="has-text-centered has-text-grey-light">該当する注文は見つかりませんでした。</p>
            </div>
        <?php else: ?>
            <div class="column is-full">
                <p class="has-text-centered has-text-grey-light">検索ワードを入力して注文を検索してください。</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
// 3. フッターの読み込みとDB切断
require './footer.php'; 
?>