<?php
// 作者：末吉心愛

require '../config/db-connect.php';
require 'header.php';
require '../config/left-menu.php'; 

$message = '';

// 登録ボタンが押されたとき
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = $_POST['product_name'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $imagePath = '';

    // 画像アップロード処理
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = date('YmdHis') . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($_FILES['image']['name']));
        $targetFile = $uploadDir . $fileName;
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imagePath = $fileName;
            }
        } else {
            $message = "画像ファイルのみアップロードできます。";
        }
    }

    // データベース登録
    if ($product_name && $price && $stock) {
        $sql = "INSERT INTO products (product_name, image, price, stock)
                VALUES (:product_name, :image, :price, :stock)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':product_name', $product_name);
        $stmt->bindValue(':image', $imagePath);
        $stmt->bindValue(':price', $price);
        $stmt->bindValue(':stock', $stock);
        $stmt->execute();
        $message = "商品を登録しました！";
    } else {
        $message = "すべての項目を入力してください。";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>商品登録 - RePhone_staff</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">
<style>
body { background-color: #f8f9fa; }
.main { margin-left: 220px; padding: 40px; }
.container {
  background: #fff;
  padding: 30px 40px;
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  width: 600px;
}
.title { color: #c0392b; font-weight: bold; }
#preview { max-width: 100%; margin-top: 10px; border: 1px solid #ddd; border-radius: 5px; display: none; }
.message { margin-bottom: 20px; }
</style>
</head>

<body>
<div class="main">
  <div class="container">
    <h1 class="title is-4">商品管理／商品登録</h1>

    <?php if (!empty($message)): ?>
      <div class="notification is-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">
      <table class="table is-fullwidth">
        <tr>
          <td>商品名：</td>
          <td><input class="input" type="text" name="product_name" required></td>
        </tr>
        <tr>
          <td>商品画像：</td>
          <td>
            <input class="file-input" type="file" name="image" id="imageInput" accept="image/*">
            <img id="preview" alt="">
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
      </table>

      <div class="has-text-centered">
        <button class="button is-link is-medium" type="submit">商品登録</button>
      </div>
    </form>
  </div>
</div>

<!-- プレビュー用スクリプト -->
<script>
document.getElementById('imageInput').addEventListener('change', function(event) {
  const file = event.target.files[0];
  const preview = document.getElementById('preview');
  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.src = e.target.result;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
  } else {
    preview.style.display = 'none';
  }
});
</script>

</body>
</html>
