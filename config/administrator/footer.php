<footer>
        <div style="text-align: center; padding: 10px; border-top: 1px solid #ccc; margin-top: 20px;">
            <p>&copy; 2025 RePhone. All Rights Reserved.</p>
        </div>
    </footer>
    </body>
</html>

<?php
// --- ▼ 修正点 ▼ ---
// $pdo 変数が存在する場合（DB接続した場合）のみ、接続を切断する
if (isset($pdo)) {
    $pdo = null; // DB切断
}
// --- ▲ 修正点 ▲ ---
?>