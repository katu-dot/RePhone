<?php
ob_start();
require '../config/db-connect.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = $_POST['product_name'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $imagePath = '';

    // 画像アップロード処理（ここは同じ）
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = date('YmdHis') . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($_FILES['image']['name']));
        $targetFile = $uploadDir . $fileName;
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imagePath = $fileName;
            }
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            header("Location: K7-product_master.php");
exit;
        } else {
            $message = "画像ファイルのみアップロードできます。";
        }
    }

    if ($product_name && $price && $stock) {
        $sql = "INSERT INTO products (product_name, image, price, stock)
                VALUES (:product_name, :image, :price, :stock)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':product_name', $product_name);
        $stmt->bindValue(':image', $imagePath);
        $stmt->bindValue(':price', $price);
        $stmt->bindValue(':stock', $stock);
        $stmt->execute();

        // ✅ header.php より前に移動
    } else {
        $message = "すべての項目を入力してください。";
    }
}

require 'header.php';
$message = '';

?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>商品登録 - RePhone_staff</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.3/css/bulma.min.css">

<style>
body {
  background-color: #f5f5f5;
  margin: 0;
  padding: 0;
}

/* ==== 左メニューとメインの配置調整 ==== */
.main {
  padding: 20px 40px 40px 40px;
  margin-top: 0 !important;
  width: 100%;
}

/* ==== ヘッダーとメインの間の隙間を削除 ==== */
.navbar {
  margin-bottom: 0 !important;
  padding-bottom: 0 !important;
}

.navbar + .main {
  margin-top: 0 !important;
}

/* ==== コンテナデザイン ==== */
.container {
  background: #fff;
  padding: 30px 40px;
  margin-top: 0;
  border-radius: 6px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.08);
  width: 600px;
}

/* ==== 見出し ==== */
.title {
  color: #000;
  font-weight: bold;
  margin-top: 0;
}
.subtitle {
  color: #000;
  margin-bottom: 20px;
}

/* ==== 入力フォーム部分 ==== */
.table td {
  border: none;
  padding: 10px 8px;
  vertical-align: middle;
}
.table td:first-child { 
  font-weight: bold;
  color: #000;
  width: 120px;
}
.input, .select select, .textarea {
  background-color: #fff;
  border: 1px solid #ccc;
  border-radius: 4px;
}
.file-input {
  border: 1px solid #ccc;
  padding: 6px;
  border-radius: 4px;
}

/* ==== アップロードボックス ==== */
.upload-box {
  border: 2px dashed #ccc;
  border-radius: 8px;
  text-align: center;
  padding: 20px;
  background-color: #fafafa;
  cursor: pointer;
}
.upload-box:hover {
  background-color: #f0f0f0;
}
#preview {
  margin-top: 10px;
  max-width: 100%;
  border: 1px solid #ddd;
  border-radius: 4px;
  display: none;
}

/* ==== ナビの文字色など ==== */
.menu-list a { 
  color: #000 !important; 
  background-color: transparent !important;
} 
.menu-list a:hover { 
  background-color: #f5f5f5 !important; 
} 
.menu-list a.is-active {
   font-weight: bold;
   border-left: 4px solid #3273dc;
   padding-left: 0.5rem; 
   background-color: #f0f0f0 !important;
   color: #000 !important; 
}
.submenu { 
  margin-left: 1rem;
  border-left: 3px solid #3273dc;
  padding-left: 0.5rem; 
} 
.submenu li a { 
  color: #000; 
}
.submenu li a:hover { 
  color: #3273dc; 
}

/* ==== 登録ボタン ==== */
.button.is-white {
  background-color: white !important;
  color: #363636 !important;
  border: 1px solid #ccc !important;
  width: 200px;
}
.button.is-white:hover {
  background-color: #f2f2f2 !important;
}
.columns{
  text-align: center;
}


</style>
</head>

<body>
  <div class="main">
  <div class="columns">
<?php require '../config/left-menu.php'; ?>

  <div class="container">
    <h2 class="title is-4">商品管理／商品登録</h2>
    <hr>
    <h3 class="subtitle is-5">基本情報</h3>

    <?php if (!empty($message)): ?>
      <div class="notification is-info"><?= htmlspecialchars($message) ?></div>
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
                  <option value="<?= $i ?>"><?= $i ?></option>
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
<?php ob_end_flush(); ?>
</body>
</html>