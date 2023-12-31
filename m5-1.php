<?php
// DB接続設定
    // ・データベース名
    $dsn = 'mysql:dbname=データベース名;host=localhost';
    // ・ユーザー名
    $user = 'ユーザー名';
    // ・パスワード
    $pass = 'パスワード';
    //PHPでPDO（PHP Data Objects）を使用してデータベースに接続
    $pdo = new PDO($dsn, $user, $pass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
    
    //テーブル設定:CREATE
    //もしまだこのテーブルが存在しないなら、"tables"というテーブルを作成
     $sql = "CREATE TABLE IF NOT EXISTS tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    comment TEXT NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pass VARCHAR(255) NOT NULL
    );";

    //変数 $sql に格納されているSQLクエリをデータベースに送信
    //その結果を $stmt 変数に格納
    $stmt = $pdo->query($sql); 
    
//編集投稿と新規投稿
if(isset($_POST["submit"]) && (!empty($_POST["name"])&&!empty($_POST["comment"])&& !empty($_POST["pass"]))) {
    $name = $_POST["name"];
    $comment = $_POST["comment"];
    $pass = $_POST["pass"];
    
    
    // 投稿番号"number"が送信されていたら編集投稿
    if (isset($_POST["number"]) && !empty($_POST["number"])) {
        $number = intval($_POST["number"]);
        //編集番号とパスワードが一致していた箇所のデータを置き換える：SET更新前と更新後を置き換える
        $stmt = $pdo->prepare("UPDATE tables SET name = :name, comment = :comment, pass = :pass WHERE id = :id AND pass=:pass");
        $stmt->bindParam(':id', $number, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);
        $stmt->bindParam(':pass', $pass, PDO::PARAM_STR); 
        $stmt->execute();
        //クエリの実行後、影響を受けた行数を取得
        $rowCount = $stmt->rowCount(); 
        //影響を受けた行数が1行以上あれば、編集は成功
        if ($rowCount > 0) {
            echo $number . "番編集しました";
        } else {
            echo "編集に失敗しました";
        }
    }else{
        //新規投稿
        // 直近の投稿番号MAX(id)を取得
        $stmt = $pdo->query("SELECT MAX(id) FROM tables");
        $lastId = $stmt->fetchColumn();
        // 新しい投稿のidを計算
        $newId = $lastId + 1;
        // 新規挿入のSQL文
        $stmt = $pdo->prepare("INSERT INTO tables (id,name, comment, pass) VALUES (:id, :name, :comment, :pass)");
        $stmt->bindParam(':id', $newId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);
        $stmt->bindParam(':pass', $pass, PDO::PARAM_STR); 
        $stmt->execute();
        //クエリの実行後、影響を受けた行数を取得
        $rowCount = $stmt->rowCount(); 
        //影響を受けた行数が1行以上あれば、投稿は成功
        if ($rowCount > 0) {
            echo $newId . "番投稿しました";
        } else {
            echo "投稿に失敗しました";
        }
    }
//送信が押された時、"名前""コメント""パスワード"のいづれかがなかった場合、エラー表示
}elseif(isset($_POST["submit"])&& (empty($_POST["name"])||empty($_POST["comment"])||empty($_POST["pass"]))) { 
    echo"投稿内容が不足しています";
}

// データを昇順で並べる
$stmt = $pdo->query("SELECT * FROM tables ORDER BY id ASC");
//実行結果を連想配列として取得
$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);


// データ削除
if (isset($_POST["deleteNumber"]) && isset($_POST["deleteSubmit"])) {
    $deleteId = $_POST["deleteNumber"];
    $deletepass = $_POST["deletepass"];
    //削除番号と投稿番号が一致した行を抽出
    $stmt = $pdo->prepare("SELECT * FROM tables WHERE id = :id");
    $stmt->bindParam(':id', $deleteId, PDO::PARAM_INT);
    $stmt->execute();
    //削除する行を定義
    $deleteData = $stmt->fetch(PDO::FETCH_ASSOC);
    //削除予定の行が存在する、かつ、パスワードが一致していれば削除
    if ($deleteData && $deleteData['pass'] === $deletepass) {
        $stmt = $pdo->prepare("DELETE FROM tables WHERE id = :id");
        $stmt->bindParam(':id', $deleteId, PDO::PARAM_INT);
        $stmt->execute();
        //クエリの実行後、影響を受けた行数を取得
        $rowCount = $stmt->rowCount(); 
        //影響を受けた行数が1行以上あれば、削除は成功
        if ($rowCount > 0) {
            echo $deleteId . "番削除しました";
        } else {
            echo "削除に失敗しました";
        }
    }else{// パスワードが一致しない場合、エラーメッセージを表示
        echo "パスワードが間違っているか、もしくは投稿番号が存在しません";
    }
}


// 編集フォームを送信して、入力フォームに表示させる
$editpassValue = ''; // 初期化
if (isset($_POST["editNumber"]) && isset($_POST["editSubmit"])) {
    $editNumber = intval($_POST["editNumber"]);// 編集対象の投稿番号
    $editpass = $_POST["editpass"];// 編集対象のパスワード
    
    // 編集番号を使用してデータを取得
    $stmt = $pdo->prepare("SELECT * FROM tables WHERE id = :id");
    $stmt->bindParam(':id', $editNumber, PDO::PARAM_INT);
    $stmt->execute();
    
    //実行後の結果の一列を連想配列として取得→取得できている場合入力フォームに表示
    $editedComment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // パスワードが一致しない場合、エラーメッセージを表示→idが合わず処理ができなかった、または、パスワードが合わなかった時以外は編集番号を入力フォームに表示
    if (!$editedComment || $editedComment['pass'] !== $editpass) {
        echo "パスワードが間違っているか、もしくは投稿番号が存在しません";
    }else{//パスワードが一致していた場合は、
        $editpassValue = $editpass; // 編集フォームのパスワード入力欄に表示する値
    }
} else{
    $editpassValue = ''; // 初期化
}

// データ取得
$stmt = $pdo->query("SELECT * FROM tables ORDER BY id ASC");
//実行結果を連想配列として取得
$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <style>
            input[type="text"],
            input[type="number"],
            input[type="password"] {
            width: 30%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 3px;
            }
        
        
    </style>
</head>
<body>
<form action="" method="post">
    <input type="text" name="name" placeholder="名前" value="<?php echo isset($editedComment['name'])&&($editedComment['pass']== $editpass) ? $editedComment['name'] : ''; ?>"><br>
    <input type="text" name="comment" placeholder="コメント" value="<?php echo isset($editedComment['comment'])&&($editedComment['pass']== $editpass) ? $editedComment['comment'] : ''; ?>"><br>
    <input type="hidden" name="number" placeholder="投稿番号"value="<?php echo isset($editedComment['id']) ? $editedComment['id'] : ''; ?>">
    <input type="text" name="pass" placeholder="パスワード"value="<?php echo isset($editedComment['pass']) &&($editedComment['pass']== $editpass)? $editedComment['pass'] : ''; ?>">
    <input type="submit" name="submit" value="送信">

</form>

<form action="" method="post">
    <input type="number" name="deleteNumber" placeholder="削除対象番号"><br>
    <input type="text" name="deletepass" placeholder="パスワード">
    <input type="submit" name="deleteSubmit" value="削除">
</form>

<form action="" method="post">
    <input type="number" name="editNumber" placeholder="編集対象番号"><br>
    <input type="text" name="editpass" placeholder="パスワード" value="<?php echo $editpassValue; ?>">
    <input type="submit" name="editSubmit" value="編集">
</form>


<?php
//テーブルの連想配列を行に分割
foreach ($tables as $line) {
    //行から要素に分割→列名と対応する値を取得：各列名は $key に、対応する値は $value に格納
    foreach ($line as $key => $value) {
        // パスワードの場合は非表示にする
        if ($key !== 'pass') {
            //echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . ' ';
            echo $value.'';
        }
    }
    echo "<br>";
}

?>

</body>
</html>