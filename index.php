<?php

//TODO: 書き込み件数が増えたらこまるので、その辺りを作る
//  アプローチ方法をどうするのかによって方針が変わる
//    1. データベースに書き込み内容を入れるようにする　→　結構長い間、何もしなくてもわりと重くなりにくい
//    2. 書き込み件数上限を設けて、ログファイルを切り替える → サーバ容量が許す限り動きそうではある
//    3. 古いのは消す　→　昭和っぽい対応ではあるが、想定通りに動きそうではある

// TODO: class を使ってプログラムを整理する
//          エラー表示用

// TODO: Smarty などの、テンプレートエンジンで プログラムのファイルを分割
//       現実問題としては、テンプレートエンジンはわりとフレームワークの一部になっていることが多いので、
//       Laravel とか最近流行ってるフレームワークを使って簡単なページを作ってみるといいかもしれない

error_reporting(E_ALL);
ini_set("display_errors", 1);

$logFile = "log.tsv";

// CSFR
session_start();

function setToken() {
  $_SESSION['token'] = sha1(uniqid(mt_rand(), true));
}

function checkToken() {
  if (empty($_SESSION['token']) || ($_SESSION['token'] != $_POST['token'])) {
    echo "不正なPOST";
    exit;
  }
}

$log = file($logFile);

$posting = $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['name']) && isset($_POST['message']);

if ($posting) { // isset :変数がセットされて、かつNULLではない場合に TRUE

  checkToken();

  $name = $_POST['name'];
  $message = $_POST['message'];

  if(strlen($name) > 100 || strlen($message) > 1500){
    echo "名前やメッセージが長過ぎます";
    exit;
  }

  if($_POST['name'] == ""){ // 名前を空欄を回避
    $name = "名無しサイダー";
  }

if(isset($_SESSION['name']) && //順番に評価されるので注意
   isset($_SESSION['message']) &&
   $_SESSION['name'] == $_POST['name'] &&
   $_SESSION['message'] == $_POST['message'])
  {
    echo "二重書き込みです";
    exit;
}

  $fileData = fopen($logFile, "ab");

  if ($fileData) { // ファイルを開くのに成功していたら
    flock($fileData, LOCK_EX); // 書き込みロック
    if ($message) {
      // 書き込む内容を準備する
      $count = count($log) + 1;
      $name = str_replace("\t", ' ', $name); //tab回避
      $name =   htmlspecialchars($name, ENT_QUOTES, 'UTF-8', false);//<tag>など変換
      $name = str_replace("\n", ' ', $name); //名前は改行出来ない様にする
      $writeDate = date('Y/n/j H:i:s');
      $message = str_replace("\t", ' ', $message);
      $message =   htmlspecialchars($message, ENT_QUOTES, 'UTF-8', false);
      $message = str_replace("\n", '<br>', $message);

      fwrite($fileData, "No." . $count . "\t" . $name . "\t" . $writeDate . "\t" . $message . "\n");
    }
    fclose($fileData);
    $_SESSION['name'] = $_POST['name'];
    $_SESSION['message'] = $_POST['message'];
  }
} else {
  setToken();
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <link rel="stylesheet" href="stylesheet.css" type="text/css">
  <title>サイダダ掲示板</title>
</head>
<body>
  <h1>サイダダ掲示板</h1>
  <h2>こんにちは、仲良く書き込んで下さい。</h2>
  <h2>間違って書き込みしても削除できませんので、あしからず。（頑張れば出来ます）</h2>
  <a href="../index.html">戻る</a>
  <hr>

  <form action="index.php" method="post">
    <table>
      <tr>
        <td>
          <input type="text" name="name" placeholder="お名前" maxlength='100'>
        </td>
      </tr>
      <tr>
        <td>
          <textarea name="message" placeholder="メッセージ" maxlength="1024"></textarea>
        </td>
      </tr>
      <tr>
        <td>
          <input type="submit" value="書き込む">
          <input type="hidden" name="token" value=<?php echo ($_SESSION['token']); ?>>
        </td>
      </tr>
    <table>
  </form>

  <hr>

<?php

if ($posting && !($message == "")) {
  $log[] = "No." . $count . "\t" . $name . "\t" . $writeDate . "\t" . $message . "\n";
}

$display = array_reverse($log);

foreach ($display as $post) {
  $cols = explode("\t", $post);

  echo "<div>";
  echo "<span class=\"writeNumber\">";
  echo $cols[0] . " ";
  echo "</span>";
  echo "<span class=\"name\">";
  echo $cols[1] . " ";
  echo "</span>";
  echo "<span class=\"writeDate\">";
  echo $cols[2];
  echo "</span>";
  echo "</div>";

  echo "<div>";
  echo "<span class=\"message\">";
  echo $cols[3];
  echo "</span>";
  echo "<div>";
  echo "<hr>";

}
?>
</body>
</html>
