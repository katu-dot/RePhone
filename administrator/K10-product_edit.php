<?php
// 作者：勝原優太郎
// 商品編集ページ（K10-product_edit.php）

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require '../config/db-connect.php'; // DB接続設定

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "<div class='notification is-danger'>DB接続エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}

// --- ▼ URLパラメータからID取得 ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='notification is-danger'>不正なアクセスです。</div>";
    exit;
}
$product_management_id = (int)$_GET['id'];

$message = '';
$message_class = 'is-info';

// --- ▼ 既存データ取得 ---
try {
    $sql = "
        SELECT 
            pm.product_management_id,
            pm.admin_id,
            pm.product_id,
            pm.status_id,
            pm.accessories_id,
            pm.stock,
            pm.category_id,

            p.product_name,
            p.product_description,
            p.price,
            p.image,
            p.maker,
            p.release_date,
            p.cpu,
            p.memory,
            p.ssd,
            p.drive,
            p.display,
            p.os,
            p.shipping_id,

            s.status_name,
            sh.shipping_date,
            a.accessories_name

        FROM product_management pm
        INNER JOIN product p ON pm.product_id = p.product_id
        INNER JOIN status s ON pm.status_id = s.status_id
        LEFT JOIN shipping sh ON p.shipping_id = sh.shipping_id
        LEFT JOIN accessories a ON pm.accessories_id = a.accessories_id
        WHERE pm.product_management_id = :id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $product_management_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo "<div class='notification is-warning'>該当商品が見つかりません。</div>";
        exit;
    }

    // カテゴリ取得
    $catStmt = $pdo->query("SELECT category_id, category_name FROM category_management");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

    // ▼ shipping 一覧取得
    $shipStmt = $pdo->query("SELECT shipping_id, shipping_date FROM shipping ORDER BY shipping_id ASC");
    $shipping_list = $shipStmt->fetchAll(PDO::FETCH_ASSOC);

    // ▼ accessories 一覧取得
    $accStmt = $pdo->query("SELECT accessories_id, accessories_name FROM accessories ORDER BY accessories_id ASC");
    $accessories_list = $accStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div class='notification is-danger'>データ取得エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}

// --- ▼ 編集処理（POST） ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = $_POST['product_name'] ?? '';

    $maker = $_POST['maker'] ?? '';
    $release_date = $_POST['release_date'] ?? null;
    $cpu = $_POST['cpu'] ?? '';
    $memory = $_POST['memory'] ?? '';
    $ssd = $_POST['ssd'] ?? '';
    $drive = $_POST['drive'] ?? '';
    $display = $_POST['display'] ?? '';
    $os = $_POST['os'] ?? '';
    $shipping_id = $_POST['shipping_id'] ?? null; // INT

    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $accessories_id = $_POST['accessories_id'] ?? 1;
    $spec = $_POST['spec'] ?? '';
    $rank = $_POST['rank'] ?? '';
    $category_id = $_POST['category_id'] ?? null;

    $status_map = ['ランクA' => 1, 'ランクB' => 2, 'ランクC' => 3];
    $status_id = $status_map[$rank] ?? 1;

    $imagePath = $product['image'];

    // --- ▼ 画像アップロード処理 ---
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = date('YmdHis') . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($_FILES['image']['name']));
        $targetFile = $uploadDir . $fileName;
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imagePath = 'uploads/' . $fileName;
            }
        } else {
            $message = "画像ファイルのみアップロードできます。";
            $message_class = 'is-danger';
        }
    }

    // 必須チェック
    if ($product_name && $price != '' && $stock !== '' && $category_id && $shipping_id && empty($message)) {
        try {
            $pdo->beginTransaction();

            // product 更新
            $sql1 = "
                UPDATE product 
                SET product_name = :name,
                    product_description = :desc,
                    price = :price,
                    image = :image,
                    maker = :maker,
                    release_date = :release_date,
                    cpu = :cpu,
                    memory = :memory,
                    ssd = :ssd,
                    drive = :drive,
                    display = :display,
                    os = :os,
                    shipping_id = :shipping_id
                WHERE product_id = :pid
            ";
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->execute([
                ':name' => $product_name,
                ':desc' => $spec,
                ':price' => $price,
                ':image' => $imagePath,
                ':maker' => $maker,
                ':release_date' => $release_date,
                ':cpu' => $cpu,
                ':memory' => $memory,
                ':ssd' => $ssd,
                ':drive' => $drive,
                ':display' => $display,
                ':os' => $os,
                ':shipping_id' => $shipping_id,
                ':pid' => $product['product_id']
            ]);

            // product_management 更新
            $sql2 = "
                UPDATE product_management 
                SET stock = :stock,
                    accessories_id = :accessories_id,
                    status_id = :status_id,
                    category_id = :category_id
                WHERE product_management_id = :pmid
            ";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([
                ':stock' => $stock,
                ':accessories_id' => $accessories_id,
                ':status_id' => $status_id,
                ':category_id' => $category_id,
                ':pmid' => $product_management_id
            ]);

            $pdo->commit();
            header("Location: K8-product_detail.php?id={$product_management_id}&message=edited");
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "更新中にエラーが発生しました: " . htmlspecialchars($e->getMessage());
            $message_class = 'is-danger';
        }
    } else if (empty($message)) {
        $message = "すべての必須項目を入力してください。";
        $message_class = 'is-danger';
    }
}

require 'header.php';
?>

<div class="columns">
    <?php require '../config/left-menu.php'; ?>

    <div class="column" style="padding:2rem;">
        <h1 class="title is-4">商品管理／商品マスター／商品マスター編集</h1>
        <h2 class="subtitle is-6 mb-5">商品編集</h2>
        <hr>

        <?php if ($message): ?>
            <div class="notification <?= $message_class; ?>"><?= htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <table class="table is-fullwidth">

                <!-- 商品名 -->
                <tr>
                    <th>商品名</th>
                    <td><input type="text" class="input" name="product_name" value="<?= htmlspecialchars($product['product_name']); ?>" required></td>
                </tr>

                <!-- 商品画像 -->
                <tr>
                    <th>商品画像</th>
                    <td>
                        <div style="margin-bottom:10px;">
                            <?php if (!empty($product['image'])): ?>
                                <img id="preview" src="../<?= htmlspecialchars($product['image']); ?>" alt="商品画像" style="max-width:200px; border:1px solid #ccc; border-radius:8px;">
                            <?php else: ?>
                                <img id="preview" src="" alt="プレビュー" style="display:none; max-width:200px; border:1px solid #ccc; border-radius:8px;">
                            <?php endif; ?>
                        </div>
                        <input type="file" id="imageInput" name="image" accept="image/*">
                    </td>
                </tr>

                <!-- 価格 -->
                <tr>
                    <th>価格</th>
                    <td><input type="number" class="input" name="price" value="<?= htmlspecialchars($product['price']); ?>" required></td>
                </tr>

                <!-- 在庫 -->
                <tr>
                    <th>在庫数</th>
                    <td><input type="number" class="input" name="stock" value="<?= htmlspecialchars($product['stock']); ?>" min="0" required></td>
                </tr>

                <!-- 発送予定日 -->
                <tr>
                    <th>発送予定日</th>
                    <td>
                        <div class="select is-fullwidth">
                            <select name="shipping_id" required>
                                <option value="">選択してください</option>
                                <?php foreach ($shipping_list as $ship): ?>
                                    <option value="<?= $ship['shipping_id']; ?>" <?= $ship['shipping_id'] == $product['shipping_id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($ship['shipping_date']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </td>
                </tr>

                <!-- メーカー -->
                <tr>
                    <th>メーカー</th>
                    <td><input type="text" class="input" name="maker" value="<?= htmlspecialchars($product['maker']); ?>"></td>
                </tr>

                <!-- 発売日 -->
                <tr>
                    <th>発売日</th>
                    <td><input type="date" class="input" name="release_date" value="<?= htmlspecialchars($product['release_date']); ?>"></td>
                </tr>

                <!-- 商品説明 -->
                <tr>
                    <th>商品説明</th>
                    <td><textarea class="textarea" name="spec" rows="3"><?= htmlspecialchars($product['product_description']); ?></textarea></td>
                </tr>

                <!-- カテゴリ -->
                <tr>
                    <th>カテゴリ</th>
                    <td>
                        <div class="select is-fullwidth">
                            <select name="category_id" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['category_id']; ?>" <?= $cat['category_id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </td>
                </tr>

                <!-- CPU -->
                <tr>
                    <th>CPU</th>
                    <td><input type="text" class="input" name="cpu" value="<?= htmlspecialchars($product['cpu']); ?>"></td>
                </tr>

                <!-- メモリ -->
                <tr>
                    <th>メモリ</th>
                    <td>
                        <input type="text" class="input" name="memory"
                            value="<?= htmlspecialchars($product['memory'] !== '' ? $product['memory'] : '―'); ?>">
                    </td>
                </tr>

                <!-- SSD -->
                <tr>
                    <th>SSD</th>
                    <td>
                        <input type="text" class="input" name="ssd"
                            value="<?= htmlspecialchars($product['ssd'] !== '' ? $product['ssd'] : '―'); ?>">
                    </td>
                </tr>

                <!-- ドライブ -->
                <tr>
                    <th>ドライブ</th>
                    <td>
                        <input type="text" class="input" name="drive"
                            value="<?= htmlspecialchars($product['drive'] !== '' ? $product['drive'] : '―'); ?>">
                    </td>
                </tr>

                <!-- ディスプレイ -->
                <tr>
                    <th>ディスプレイ</th>
                    <td><input type="text" class="input" name="display" value="<?= htmlspecialchars($product['display']); ?>"></td>
                </tr>

                <!-- OS -->
                <tr>
                    <th>OS</th>
                    <td><input type="text" class="input" name="os" value="<?= htmlspecialchars($product['os']); ?>"></td>
                </tr>

                <!-- 付属品 -->
                <tr>
                    <th>付属品</th>
                    <td>
                        <div class="select is-fullwidth">
                            <select name="accessories_id" required>
                                <?php foreach ($accessories_list as $acc): ?>
                                    <option value="<?= $acc['accessories_id']; ?>" <?= $acc['accessories_id'] == $product['accessories_id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($acc['accessories_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </td>
                </tr>

                <!-- 状態 -->
                <tr>
                    <th>状態</th>
                    <td>
                        <div class="select is-fullwidth">
                            <select name="rank" required>
                                <option value="ランクA" <?= $product['status_name'] === 'ランクA' ? 'selected' : ''; ?>>ランクA</option>
                                <option value="ランクB" <?= $product['status_name'] === 'ランクB' ? 'selected' : ''; ?>>ランクB</option>
                                <option value="ランクC" <?= $product['status_name'] === 'ランクC' ? 'selected' : ''; ?>>ランクC</option>
                            </select>
                        </div>
                    </td>
                </tr>

            </table>

            <div class="has-text-centered" style="margin-top:20px;">
                <button class="button is-info is-medium" type="submit">更新する</button>
                <a href="K8-product_detail.php?id=<?= $product_management_id ?>" class="button is-light is-medium">戻る</a>
            </div>

        </form>
    </div>
</div>

<script>
// 画像プレビュー
document.getElementById('imageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('preview');
    if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            preview.src = ev.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.src = '';
        preview.style.display = 'none';
    }
});
</script>

<?php require 'footer.php'; ?>
