<?php
// --- ▼ デバッグ用 ▼ ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
// --- ▲ デバッグ用 ▲ ---

session_start();
require '../config/db-connect.php';

// --- ▼ DB接続 ▼ ---
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "<div class='notification is-danger'>接続エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
// --- ▲ DB接続 ▲ ---

$message = '';
$message_class = 'is-info';

// --- ▼ カテゴリ一覧取得 ▼ ---
$categories = $pdo->query("SELECT category_id, category_name FROM category_management ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- ▼ shipping一覧取得 ▼ ---
$shipping_list = $pdo->query("SELECT shipping_id, shipping_date FROM shipping ORDER BY shipping_id ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- ▼ 登録処理 ▼ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $product_name   = $_POST['product_name'] ?? '';
    $price          = $_POST['price'] ?? 0;
    $stock          = $_POST['stock'] ?? 0;
    $spec           = $_POST['spec'] ?? '';
    $rank           = $_POST['rank'] ?? '';
    $category_id    = $_POST['category_id'] ?? ($categories[0]['category_id'] ?? 1);
    $shipping_id    = $_POST['shipping_id'] ?? '';
    $admin_id       = $_SESSION['admin_id'] ?? 1;

    // 付属品 → accessories_id に変換
    $accessories_map = [
        '本体のみ' => 1,
        '箱・付属品あり' => 2,
        '付属品なし' => 3
    ];
    $accessories_name = $_POST['accessories_name'] ?? '本体のみ';
    $accessories_id = $accessories_map[$accessories_name] ?? 1;

    // ランク → status_id に変換
    $status_map = ['ランクA' => 1, 'ランクB' => 2, 'ランクC' => 3];
    $status_id = $status_map[$rank] ?? 1;

    // 空欄は"-"に変換
    $cpu     = $_POST['cpu']     !== '' ? $_POST['cpu']     : '-';
    $memory  = $_POST['memory']  !== '' ? $_POST['memory']  : '-';
    $ssd     = $_POST['ssd']     !== '' ? $_POST['ssd']     : '-';
    $drive   = $_POST['drive']   !== '' ? $_POST['drive']   : '-';
    $display = $_POST['display'] !== '' ? $_POST['display'] : '-';
    $os      = $_POST['os']      !== '' ? $_POST['os']      : '-';

    $imagePath = '';

    // 画像アップロード
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = date('YmdHis') . '_' . preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', basename($_FILES['image']['name']));
        $targetFile = $uploadDir . $fileName;

        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed) && move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = 'uploads/' . $fileName; 
        } else {
            $message = "画像ファイルのみアップロード可能です。";
            $message_class = 'is-danger';
        }
    }

    if ($product_name && $price !== '' && $stock !== '' && $shipping_id && empty($message)) {
        try {
            $pdo->beginTransaction();

            // product 登録
            $sql1 = "INSERT INTO product 
                (product_name, product_description, price, stock, image, category_id, maker, release_date, cpu, memory, ssd, drive, display, os, shipping_id)
                VALUES
                (:product_name, :spec, :price, :stock, :image, :category_id, :maker, :release_date, :cpu, :memory, :ssd, :drive, :display, :os, :shipping_id)";
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->execute([
                ':product_name' => $product_name,
                ':spec'         => $spec,
                ':price'        => $price,
                ':stock'        => $stock,
                ':image'        => $imagePath,
                ':category_id'  => $category_id,
                ':maker'        => $_POST['maker'] ?? '',
                ':release_date' => $_POST['release_date'] ?? null,
                ':cpu'          => $cpu,
                ':memory'       => $memory,
                ':ssd'          => $ssd,
                ':drive'        => $drive,
                ':display'      => $display,
                ':os'           => $os,
                ':shipping_id'  => $shipping_id
            ]);

            $product_id = $pdo->lastInsertId();

            // product_management 登録
            $sql2 = "INSERT INTO product_management
                (admin_id, product_id, status_id, stock, accessories_id, spec, category_id)
                VALUES
                (:admin_id, :product_id, :status_id, :stock, :accessories_id, :spec, :category_id)";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([
                ':admin_id'      => $admin_id,
                ':product_id'    => $product_id,
                ':status_id'     => $status_id,
                ':stock'         => $stock,
                ':accessories_id'=> $accessories_id,
                ':spec'          => $spec,
                ':category_id'   => $category_id
            ]);

            $pdo->commit();

            header("Location: K8-product_detail.php?id={$product_id}&message=registered");
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "登録失敗: " . htmlspecialchars($e->getMessage());
            $message_class = 'is-danger';
        }

    } else if (empty($message)) {
        $message = "すべての項目を入力してください。";
        $message_class = 'is-danger';
    }
}

require './header.php';
?>

<div class="columns">
<?php require '../config/left-menu.php'; ?>

<div class="column" style="padding: 2rem;">
<h1 class="title is-4">商品管理／商品登録</h1>
<h2 class="subtitle is-6">商品登録フォーム</h2>

<?php if ($message): ?>
    <div class="notification <?= $message_class; ?>">
        <?= htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="box">
<form action="" method="post" enctype="multipart/form-data">
<table class="table is-fullwidth">
<tr>
    <td>商品名：</td>
    <td><input class="input" type="text" name="product_name" required></td>
</tr>
<tr>
    <td>商品画像：</td>
    <td>
        <img id="preview" src="" style="display:none; max-width:200px; border:1px solid #ccc;">
        <input type="file" id="imageInput" name="image" accept="image/*">
    </td>
</tr>
<tr><td>価格：</td><td><input class="input" type="number" name="price" min="0" required></td></tr>
<tr><td>在庫数：</td><td><input class="input" type="number" name="stock" min="0" required></td></tr>
<tr>
<td>発送予定日：</td>
<td>
    <div class="select is-fullwidth">
        <select name="shipping_id" required>
            <option value="">選択してください</option>
            <?php foreach ($shipping_list as $ship): ?>
                <option value="<?= $ship['shipping_id']; ?>">
                    <?= htmlspecialchars($ship['shipping_date']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</td>
</tr>
<tr><td>メーカー：</td><td><input class="input" type="text" name="maker" required></td></tr>
<tr><td>発売日：</td><td><input class="input" type="date" name="release_date"></td></tr>
<tr><td>商品説明：</td><td><textarea class="textarea" name="spec"></textarea></td></tr>
<tr>
<td>カテゴリ：</td>
<td>
    <div class="select is-fullwidth">
        <select name="category_id" required>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['category_id']; ?>"><?= htmlspecialchars($cat['category_name']); ?></option>
            <?php endforeach ?>
        </select>
    </div>
</td>
</tr>
<tr><td>CPU：</td><td><input class="input" type="text" name="cpu" required></td></tr>
<tr><td>メモリ：</td><td><input class="input" type="text" name="memory" required></td></tr>
<tr><td>SSD：</td><td><input class="input" type="text" name="ssd" required></td></tr>
<tr><td>ドライブ：</td><td><input class="input" type="text" name="drive" required></td></tr>
<tr><td>ディスプレイ：</td><td><input class="input" type="text" name="display" required></td></tr>
<tr><td>OS：</td><td><input class="input" type="text" name="os" required></td></tr>
<tr>
<td>付属品：</td>
<td>
    <div class="select is-fullwidth">
        <select name="accessories_name">
            <option value="本体のみ">本体のみ</option>
            <option value="箱・付属品あり">箱・付属品あり</option>
            <option value="付属品なし">付属品なし</option>
        </select>
    </div>
</td>
</tr>
<tr>
<td>状態：</td>
<td>
    <div class="select is-fullwidth">
        <select name="rank">
            <option value="ランクA">ランクA</option>
            <option value="ランクB">ランクB</option>
            <option value="ランクC">ランクC</option>
        </select>
    </div>
</td>
</tr>
</table>

<div class="buttons mt-3">
    <a href="K7-product_master.php" class="button is-light">商品一覧へ戻る</a>
    <button class="button is-info is-medium" type="submit">商品登録</button>
</div>

</form>
</div>
</div>
</div>

<script>
document.getElementById('imageInput').addEventListener('change', function(e){
    const file = e.target.files[0];
    const preview = document.getElementById('preview');
    if(file){
        const reader = new FileReader();
        reader.onload = function(ev){
            preview.src = ev.target.result;
            preview.style.display='block';
        };
        reader.readAsDataURL(file);
    }else{
        preview.style.display='none';
    }
});
</script>

<?php require 'footer.php'; ?>
<?php
// --- ▼ デバッグ用 ▼ ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
// --- ▲ デバッグ用 ▲ ---

session_start();
require '../config/db-connect.php';

// --- ▼ DB接続 ▼ ---
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "<div class='notification is-danger'>接続エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
// --- ▲ DB接続 ▲ ---

$message = '';
$message_class = 'is-info';

// --- ▼ カテゴリ一覧取得 ▼ ---
$categories = $pdo->query("SELECT category_id, category_name FROM category_management ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- ▼ shipping一覧取得 ▼ ---
$shipping_list = $pdo->query("SELECT shipping_id, shipping_date FROM shipping ORDER BY shipping_id ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- ▼ 登録処理 ▼ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $product_name   = $_POST['product_name'] ?? '';
    $price          = $_POST['price'] ?? 0;
    $stock          = $_POST['stock'] ?? 0;
    $spec           = $_POST['spec'] ?? '';
    $rank           = $_POST['rank'] ?? '';
    $category_id    = $_POST['category_id'] ?? ($categories[0]['category_id'] ?? 1);
    $shipping_id    = $_POST['shipping_id'] ?? '';
    $admin_id       = $_SESSION['admin_id'] ?? 1;

    // 付属品 → accessories_id に変換
    $accessories_map = [
        '本体のみ' => 1,
        '箱・付属品あり' => 2,
        '付属品なし' => 3
    ];
    $accessories_name = $_POST['accessories_name'] ?? '本体のみ';
    $accessories_id = $accessories_map[$accessories_name] ?? 1;

    // ランク → status_id に変換
    $status_map = ['ランクA' => 1, 'ランクB' => 2, 'ランクC' => 3];
    $status_id = $status_map[$rank] ?? 1;

    // 空欄は"-"に変換
    $cpu     = $_POST['cpu']     !== '' ? $_POST['cpu']     : '-';
    $memory  = $_POST['memory']  !== '' ? $_POST['memory']  : '-';
    $ssd     = $_POST['ssd']     !== '' ? $_POST['ssd']     : '-';
    $drive   = $_POST['drive']   !== '' ? $_POST['drive']   : '-';
    $display = $_POST['display'] !== '' ? $_POST['display'] : '-';
    $os      = $_POST['os']      !== '' ? $_POST['os']      : '-';

    $imagePath = '';

    // 画像アップロード
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = date('YmdHis') . '_' . preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', basename($_FILES['image']['name']));
        $targetFile = $uploadDir . $fileName;

        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed) && move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = 'uploads/' . $fileName; 
        } else {
            $message = "画像ファイルのみアップロード可能です。";
            $message_class = 'is-danger';
        }
    }

    if ($product_name && $price !== '' && $stock !== '' && $shipping_id && empty($message)) {
        try {
            $pdo->beginTransaction();

            // product 登録
            $sql1 = "INSERT INTO product 
                (product_name, product_description, price, stock, image, category_id, maker, release_date, cpu, memory, ssd, drive, display, os, shipping_id)
                VALUES
                (:product_name, :spec, :price, :stock, :image, :category_id, :maker, :release_date, :cpu, :memory, :ssd, :drive, :display, :os, :shipping_id)";
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->execute([
                ':product_name' => $product_name,
                ':spec'         => $spec,
                ':price'        => $price,
                ':stock'        => $stock,
                ':image'        => $imagePath,
                ':category_id'  => $category_id,
                ':maker'        => $_POST['maker'] ?? '',
                ':release_date' => $_POST['release_date'] ?? null,
                ':cpu'          => $cpu,
                ':memory'       => $memory,
                ':ssd'          => $ssd,
                ':drive'        => $drive,
                ':display'      => $display,
                ':os'           => $os,
                ':shipping_id'  => $shipping_id
            ]);

            $product_id = $pdo->lastInsertId();

            // product_management 登録
            $sql2 = "INSERT INTO product_management
                (admin_id, product_id, status_id, stock, accessories_id, spec, category_id)
                VALUES
                (:admin_id, :product_id, :status_id, :stock, :accessories_id, :spec, :category_id)";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([
                ':admin_id'      => $admin_id,
                ':product_id'    => $product_id,
                ':status_id'     => $status_id,
                ':stock'         => $stock,
                ':accessories_id'=> $accessories_id,
                ':spec'          => $spec,
                ':category_id'   => $category_id
            ]);

            $pdo->commit();

            header("Location: K8-product_detail.php?id={$product_id}&message=registered");
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "登録失敗: " . htmlspecialchars($e->getMessage());
            $message_class = 'is-danger';
        }

    } else if (empty($message)) {
        $message = "すべての項目を入力してください。";
        $message_class = 'is-danger';
    }
}

require './header.php';
?>

<div class="columns">
<?php require '../config/left-menu.php'; ?>

<div class="column" style="padding: 2rem;">
<h1 class="title is-4">商品管理／商品登録</h1>
<h2 class="subtitle is-6">商品登録フォーム</h2>

<?php if ($message): ?>
    <div class="notification <?= $message_class; ?>">
        <?= htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="box">
<form action="" method="post" enctype="multipart/form-data">
<table class="table is-fullwidth">
<tr>
    <td>商品名：</td>
    <td><input class="input" type="text" name="product_name" required></td>
</tr>
<tr>
    <td>商品画像：</td>
    <td>
        <img id="preview" src="" style="display:none; max-width:200px; border:1px solid #ccc;">
        <input type="file" id="imageInput" name="image" accept="image/*">
    </td>
</tr>
<tr><td>価格：</td><td><input class="input" type="number" name="price" min="0" required></td></tr>
<tr><td>在庫数：</td><td><input class="input" type="number" name="stock" min="0" required></td></tr>
<tr>
<td>発送予定日：</td>
<td>
    <div class="select is-fullwidth">
        <select name="shipping_id" required>
            <option value="">選択してください</option>
            <?php foreach ($shipping_list as $ship): ?>
                <option value="<?= $ship['shipping_id']; ?>">
                    <?= htmlspecialchars($ship['shipping_date']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</td>
</tr>
<tr><td>メーカー：</td><td><input class="input" type="text" name="maker" required></td></tr>
<tr><td>発売日：</td><td><input class="input" type="date" name="release_date"></td></tr>
<tr><td>商品説明：</td><td><textarea class="textarea" name="spec"></textarea></td></tr>
<tr>
<td>カテゴリ：</td>
<td>
    <div class="select is-fullwidth">
        <select name="category_id" required>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['category_id']; ?>"><?= htmlspecialchars($cat['category_name']); ?></option>
            <?php endforeach ?>
        </select>
    </div>
</td>
</tr>
<tr><td>CPU：</td><td><input class="input" type="text" name="cpu" required></td></tr>
<tr><td>メモリ：</td><td><input class="input" type="text" name="memory" required></td></tr>
<tr><td>SSD：</td><td><input class="input" type="text" name="ssd" required></td></tr>
<tr><td>ドライブ：</td><td><input class="input" type="text" name="drive" required></td></tr>
<tr><td>ディスプレイ：</td><td><input class="input" type="text" name="display" required></td></tr>
<tr><td>OS：</td><td><input class="input" type="text" name="os" required></td></tr>
<tr>
<td>付属品：</td>
<td>
    <div class="select is-fullwidth">
        <select name="accessories_name">
            <option value="本体のみ">本体のみ</option>
            <option value="箱・付属品あり">箱・付属品あり</option>
            <option value="付属品なし">付属品なし</option>
        </select>
    </div>
</td>
</tr>
<tr>
<td>状態：</td>
<td>
    <div class="select is-fullwidth">
        <select name="rank">
            <option value="ランクA">ランクA</option>
            <option value="ランクB">ランクB</option>
            <option value="ランクC">ランクC</option>
        </select>
    </div>
</td>
</tr>
</table>

<div class="buttons mt-3">
    <a href="K7-product_master.php" class="button is-light">商品一覧へ戻る</a>
    <button class="button is-info is-medium" type="submit">商品登録</button>
</div>

</form>
</div>
</div>
</div>

<script>
document.getElementById('imageInput').addEventListener('change', function(e){
    const file = e.target.files[0];
    const preview = document.getElementById('preview');
    if(file){
        const reader = new FileReader();
        reader.onload = function(ev){
            preview.src = ev.target.result;
            preview.style.display='block';
        };
        reader.readAsDataURL(file);
    }else{
        preview.style.display='none';
    }
});
</script>

<?php require 'footer.php'; ?>
