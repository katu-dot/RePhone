<?php
// --- ▼ デバッグ用：エラーを強制的に表示 ▼ ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
// --- ▲ デバッグ用 ▲ ---

session_start();
require '../config/db-connect.php'; // DB接続情報 ($connect, USER, PASS) のみ読み込む

// --- ▼ DB接続処理 ▼ ---
try {
    $pdo = new PDO($connect, USER, PASS); 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // 接続失敗時はHTMLを描画する前にエラーを表示して停止
    echo "<div class='notification is-danger'>データベース接続エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit(); 
}
// --- ▲ DB接続処理 ▲ ---

$message = '';
$message_class = 'is-info'; // メッセージのスタイル（is-info, is-success, is-danger）
 
// --- ▼ フォーム登録処理 ▼ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $product_name = $_POST['product_name'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $accessories = $_POST['accessories'] ?? '本体のみ';
    $spec = $_POST['spec'] ?? '';
    $rank = $_POST['rank'] ?? 'ランクA';
    
    $status_map = ['ランクA' => 1, 'ランクB' => 2, 'ランクC' => 3];
    $status_id = $status_map[$rank] ?? 1;
    $admin_id = $_SESSION['admin_id'] ?? 1; 
    $category_id = 1; // 暫定 (フォームにカテゴリ選択を追加する必要があります)
    $imagePath = ''; 

    // 画像アップロード処理
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

    if ($product_name && $price && $stock && empty($message)) {
        
        try {
            $pdo->beginTransaction();

            // 1. product テーブルへ登録
            $sql1 = "INSERT INTO product (product_name, product_description, price, stock, image, category_id)
                     VALUES (:product_name, :spec, :price, :stock, :image, :category_id)";
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->execute([
                ':product_name' => $product_name,
                ':spec' => $spec,
                ':price' => $price,
                ':stock' => $stock,
                ':image' => $imagePath,
                ':category_id' => $category_id
            ]);

            $product_id = $pdo->lastInsertId();

            // 2. product_management テーブルへ登録
            $sql2 = "INSERT INTO product_management (admin_id, product_id, status_id, stock, accessories, spec)
                     VALUES (:admin_id, :product_id, :status_id, :stock, :accessories, :spec)";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([
                ':admin_id' => $admin_id,
                ':product_id' => $product_id,
                ':status_id' => $status_id,
                ':stock' => $stock,
                ':accessories' => $accessories,
                ':spec' => $spec
            ]);
            
            $pdo->commit();

            // --- ▼ 修正点：リダイレクトを削除し、成功メッセージを設定 ▼ ---
            $message = "商品を登録しました。";
            $message_class = 'is-success'; // メッセージを成功（緑色）にする
            // header("Location: K7-product_master.php"); // 削除
            // exit; // 削除
            // --- ▲ 修正点 ▲ ---

        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "登録に失敗しました。エラー: " . $e->getMessage();
            $message_class = 'is-danger';
        }

    } else if (empty($message)) {
        $message = "すべての項目を入力してください。";
        $message_class = 'is-danger';
    }
}
// --- ▲ 登録処理ここまで ▲ ---


// --- ▼ HTMLの描画開始 ▼ ---
require './header.php'; 
?>
 
<style>
/* ... (CSSは省略しますが、外部ファイルへの移動を推奨) ... */
.main {
  padding: 20px 40px 40px 40px;
}
.container {
  background: #fff;
  padding: 30px 40px;
  margin-top: 0;
  border-radius: 6px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.08);
  width: 600px;
}
/* ... (以下省略) ... */
</style>

<div class="main">
  <div class="columns">
<?php require '../config/left-menu.php'; ?>
 
  <div class="container">
    <h2 class="title is-4">商品管理／商品登録</h2>
    <hr>
    <h3 class="subtitle is-5">基本情報</h3>
 
    <?php if (!empty($message)): ?>
      <div class="notification <?php echo $message_class; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
 
    <form action="" method="post" enctype="multipart/form-data">
      <table class="table is-fullwidth">
        <tr>
          <td>商品名：</td>
          <td><input class="input" type="text" name="product_name" placeholder="商品名を入力" required></td>
        </tr>
        <tr>
          <td>商品画像：</td>
          <td>
            <label class="upload-box" for="imageInput">
              ＋ファイルをアップロード
              <input class="file-input" type="file" name="image" id="imageInput" accept="image/*" style="display:none;">
              <img id="preview" alt="プレビュー">
            </label>
          </td>
        </tr>
        <tr>
          <td>価格：</td>
          <td><input class="input" type="number" name="price" min="0" placeholder="例：2000" required></td>
        </tr>
        <tr>
          <td>在庫数：</td>
          <td>
            <div class="select is-fullwidth">
              <select name="stock">
                <?php for ($i = 1; $i <= 10; $i++): ?>
                  <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
              </select>
            </div>
          </td>
        </tr>
        <tr>
          <td>付属品：</td>
          <td>
            <div class="select is-fullwidth">
              <select name="accessories">
                <option value="本体のみ"> 本体のみ</option>
                <option value="箱・付属品あり">箱・付属品あり</option>
                <option value="付属品なし">付属品なし</option>
              </select>
            </div>
          </td>
        </tr>
        <tr>
          <td>スペック：</td>
          <td><textarea class="textarea" name="spec" rows="2" placeholder="例：64GB / SIMフリー"></textarea></td>
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
 
      <div class="has-text-centered" style="margin-top:30px;">
        <button class="button is-info is-medium" type="submit">商品登録</button>
      </div>
    </form>
   </div>
</div>
 
 
 
<script>
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
    preview.style.display = 'none';
  }
});
</script>

<?php require 'footer.php'; ?>
 
</html>