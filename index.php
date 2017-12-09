<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);


// CSFR
session_start();

function setToken() {
  $token = sha1(uniqid(mt_rand(), true));
  $_SESSION['token'] = $token;
}

function checkToken() {
  if (empty($_SESSION['token']) || ($_SESSION['token'] != $_POST['token'])) {
    echo "不正なPOST";
    exit;
  }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['name']) && isset($_POST['message'])) { // isset :変数がセットされて、かつNULLではない場合に TRUE

  checkToken();

  $name = $_POST['name'];
  $message = $_POST['message'];

  if($_POST['name'] == ""){ // 名前を空欄を回避
    $name = "名無しサイダー";
  }

  $logFile = "log.tsv";
  $fileData = fopen($logFile, "ab");

  $count = count(file($logFile)) + 1;

  if ($fileData) { // ファイルを開くのに成功していたら
    flock($fileData, LOCK_EX); // 書き込みロック
    if ($message) {

      $name = str_replace("\t", ' ', $name); //tab回避
      $message = str_replace("\t", ' ', $message);
      $message = str_replace("\n", '<br>', $message);

      $writeDate = date('Y/n/j H:i:s');
      fwrite($fileData, "No." . $count . "\t" . $name . "\t" . $writeDate . "\t" . $message . "\n");
    }
    fclose($fileData);
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

  <hr>

  <form action="index.php" method="post">
    <table>
      <tr>
        <td>
          <input type="text" name="name" placeholder="お名前">
        </td>
      </tr>
      <tr>
        <td>
          <textarea name="message" placeholder="メッセージ"></textarea>
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
  $logFile = "log.tsv";

  $display = file($logFile, FILE_IGNORE_NEW_LINES);
  $display = array_reverse($display);

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
