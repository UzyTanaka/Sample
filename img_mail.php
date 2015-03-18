<?php
/*--------------------------------------------------------------------
    SOUCE   ：img_mail.php
    INFO    ：メール添付の画像ファイルの保存
--------------------------------------------------------------------*/

/*--------------------------------------------------------------------
    FUNCTION    ：mime_split
    INFOMATION  ：ヘッダと本文を分割する
 *------------------------------------------------------------------*/
function mime_split($data = "") {
    $part    = split("\n\n", $data, 2);
    $part[1] = ereg_replace("\n[\t ]+", " ", $part[1]);
    return $part;
}

/*--------------------------------------------------------------------
    FUNCTION    ：addr_search
    INFOMATION  ：メールアドレスを抽出する
 *------------------------------------------------------------------*/
function addr_search($addr = "") {
    if (eregi("[-!#$%&\'*+\\./0-9A-Z^_`a-z{|}~]+@[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+", $addr, $fromreg)) {
        return $fromreg[0];
    }
    return false;
}

/*--------------------------------------------------------------------
    FUNCTION    ：convert
    INFOMATION  ：文字コードコンバートJIS→SJIS
 *------------------------------------------------------------------*/
function convert($str = "") {
    return mb_convert_encoding($str, "SJIS", "JIS,SJIS");
}

/*--------------------------------------------------------------------
    FUNCTION    ：get_data
    INFOMATION  ：メールデータの取得
 *------------------------------------------------------------------*/
function get_data(){
    /* 標準入力から情報を取得し配列に格納する */
    $input = fopen('php://stdin', 'r');
    while(!feof($input)) {
        $msg .= fgets($input);
    }

    /* 文字化け対策 */
    if (function_exists("mb_internal_encoding")) {
        mb_internal_encoding("SJIS");
    }

    return $msg;
}

/*--------------------------------------------------------------------
    FUNCTION    ：split_multipart_data
    INFOMATION  ：マルチパートならばバウンダリに分割
 *------------------------------------------------------------------*/
function split_multipart_data($head = "", $body = "") {
    if (eregi("\nContent-type:.*multipart/", $head)) {
        eregi('boundary="([^"]+)"', $head, $boureg);
        $body = str_replace($boureg[1], urlencode( $boureg[1] ), $body);
        $part = split("\n--" . urlencode( $boureg[1] ) . "-?-?", $body);

        /* multipart/altanative */
        if (eregi('boundary="([^"]+)"', $body, $boureg2)) {
            $body = str_replace($boureg2[1], urlencode($boureg2[1]), $body);
            $body = eregi_replace("\n--" . urlencode($boureg[1]) . "-?-?\n", "", $body);
            $part = split("\n--" . urlencode($boureg2[1]) . "-?-?", $body);
        }
    } else {
        /* 普通のテキストメール */
        $part[0] = $msg;
    }
    return $part;
}

/*--------------------------------------------------------------------
    FUNCTION    ：get_file
    INFOMATION  ：メール本文か添付ファイルを取得する
 *------------------------------------------------------------------*/
function get_file($part = "") {
    global  $now;           // メール受信時刻
    global  $subject;       // メールの件名
    global  $from;          // 送信者メールアドレス

    $tmpdir  = "/XXXX/XXXX/XXXX/";                       // 画像保存ﾃﾞｨﾚｸﾄﾘ
    $maxbyte = 2048000;                                  // 2048KB 最大添付量（バイト・1ファイルにつき）※超えるものは保存しない
    $maxtext = 1000;                                     // 最大本文文字数（半角で
    $subtype = "gif|jpe?g|png";                          // 対応MIMEサブタイプ（正規表現）Content-Type: image/jpegの後ろの部分。octet-streamは危険かも
    $viri    = ".+\.exe$|.+\.zip$|.+\.pif$|.+\.scr$";    // 保存しないファイル(正規表現)

    foreach($part as $multi) {
        list($m_head, $m_body) = mime_split($multi);
        $m_body                = ereg_replace("\n\.\n$", "", $m_body);
        if (!eregi("Content-type: *([^;\n]+)", $m_head, $type)) {
            continue;
        }
        list($main, $sub)      = explode("/", $type[1]);

        /* 本文をデコード */
        if (strtolower($main) == "text") {
            if (eregi("Content-Transfer-Encoding:.*base64", $m_head)) {
                $m_body = base64_decode($m_body);
            }
            if (eregi("Content-Transfer-Encoding:.*quoted-printable", $m_head)) {
                $m_body = quoted_printable_decode($m_body);
            }

            $text = convert($m_body);
            if ($sub == "html") {
                $text = strip_tags($text);
            }

            $text = eregi_replace("([[:digit:]]{11})|([[:digit:]\-]{13})", "", $text);                          // 電話番号削除
            $text = eregi_replace("[_]{25,}", "", $text);                                                       // 下線削除
            $text = ereg_replace("Content-type: multipart/appledouble;[[:space:]]boundary=(.*)", "", $text);    // mac削除
            if (is_array( $word ) ) $text = str_replace($word, "", $text);                                      // 広告等削除
            if ($len = strlen( $text ) > $maxtext) $text = substr($text, 0, $maxtext) . "...";                  // 文字数オーバー
            $text = str_replace(">","&gt;", $text);
            $text = str_replace("<","&lt;", $text);
            $text = str_replace("\n", "\r", $text);
            $text = str_replace("\r", "\n", $text);
            $text = preg_replace("/\n{2,}/", "\n\n", $text);
            $text = str_replace("\n", "<br>", $text);
        }

        /* ファイル名を抽出 */
        if (eregi("name=\"?([^\"\n]+)\"?", $m_head, $filereg)) {
            $filename = ereg_replace("[\t\n]", "", $filereg[1]);
            while(eregi("(.*)=\?iso-2022-jp\?B\?([^\?]+)\?=(.*)", $filename, $regs)) {
                $filename = $regs[1] . base64_decode($regs[2]) . $regs[3];
                $filename = convert($filename);
            }
        }

        /* 添付データをデコードして保存 */
        if (eregi("Content-Transfer-Encoding:.*base64", $m_head) && eregi($subtype, $sub)) {
            $tmp = base64_decode($m_body);  // base64デコードする
            if (!$filename) {               // ファイル名が取得できなかった場合は暫定のファイル名を作成する
                $filename = $now . "." . $sub;
            }

            /* ファイルサイズと画像ファイルの場合で書き込み指定の場合はファイルに書き出し保存する */
            if (strlen($tmp) < $maxbyte && !eregi($viri, $filename)) {
                $fp = fopen($tmpdir.$filename, "w");
                fputs($fp, $tmp);
                fclose($fp);
                make_image_local($filename);
                unlink($tmpdir . $filename);
            } else {
                exit(99);
            }
        }
    }
}

/*--------------------------------------------------------------------
    FUNCTION    ：make_image_local
    INFOMATION  ：イメージを作成する
 *------------------------------------------------------------------*/
function make_image_local($filename = "") {
    global  $user_seq;
    global  $text_seq;

    $getdir  = "/XXXX/XXXX/XXXX/";
    $savedir = "/XXXX/XXXX/XXXX/" . $user_seq . "/";

    /* 画像タイプの定義 */
    define("IMAGETYPE_GIF", 1);
    define("IMAGETYPE_JPG", 2);
    define("IMAGETYPE_PNG", 3);

    /* 画像最大サイズ（縦or横） */
    define("MAX_SIZE", 200);

    $img      = null;
    $max_size = MAX_SIZE;
    $size_x   = 0;
    $size_y   = 0;

    /* イメージタイプを判定する */
    $img_type = exif_imagetype($getdir . $filename);

    /* 保存先が存在しているかどうか確認する */
    if (!is_writable($savedir)) {
        mkdir($savedir, 0777);
        sleep(1);
        clearstatcache();
    }

    if (is_writable($savedir)) {
        umask(0111);

        /* メモリ上に画像イメージを作成する */
        if ($img_type == IMAGETYPE_GIF) {       // GIFファイル
            $img = imagecreatefromgif($getdir . $filename);
        } elseif ($img_type == IMAGETYPE_JPG) { // JPGファイル
            $img = imagecreatefromjpeg($getdir . $filename);
        } elseif ($img_type == IMAGETYPE_PNG) { // PNGファイル
            $img = imagecreatefrompng($getdir . $filename);
        } else {
            exit(99);
        }

        $size_x = ImageSX($img);
        $size_y = ImageSY($img);

        if ($img) {
            /* 画像のサイズ（縦横チェック） */
            if ($size_x <= 1 || $size_y <= 1) { //縦横のサイズどちらかが1pix以下であればNOPHOTO
                return 0;
            }
        }

        /* 画像のサイズ変更 */
        if ($size_x >= $size_y) {
            /* 縮小のみ */
            if ($max_size < $size_x) {
                $new_y = intval($size_y * $max_size / $size_x);
                $new_x = $max_size;
                /* 画像リサイズ */
                $wk_img = imagecreatetruecolor($new_x, $new_y);
                /* ダミーにリサイズしてコピー */
                imagecopyresampled($wk_img, $img, 0, 0, 0, 0, $new_x, $new_y, $size_x, $size_y);
                $img    = $wk_img;
            }
        } else {
            /* 縮小のみ */
            if ($max_size < $size_y) {
                $new_x = intval($size_x * $max_size / $size_y);
                $new_y = $max_size;
                /* 画像リサイズ */
                $wk_img = imagecreatetruecolor($new_x, $new_y);
                /* ダミーにリサイズしてコピー */
                imagecopyresampled($wk_img, $img, 0, 0, 0, 0, $new_x, $new_y, $size_x, $size_y);
                $img    = $wk_img;
            }
        }

        if ($img_type == IMAGETYPE_GIF) {       // GIFファイルの場合
            ImageGif($img, $savedir . $filename);
        } elseif ($img_type == IMAGETYPE_JPG) { // JPGファイルの場合
            ImageJPEG($img, $savedir . $filename);
        } elseif ($img_type == IMAGETYPE_PNG) { // PNGファイルの場合
            ImagePNG($img, $savedir . $filename);
        }

        /* 画像消去 */
        imagedestroy($img);
    }
}

/*--------------------------------------------------------------------
    FUNCTION    ：uploaderr_send()
    INFOMATION  ：変更完了メールの送信
 *------------------------------------------------------------------*/
function uploaderr_send(){
    /* ソース内で同一変数を使用できるように宣言 */
    global  $email;

    /* メール送信手続き */
    $title  = "Upload OK!!";
    $header = "From: info@xxxx.xxx\r\nReturn-Path: -finfo@xxxx.xxx\r\n";
    $handle = fopen('/XXXX/XXXX/XXXX/upload_err.txt', 'r');
    while($bufferLine = fgets($handle, 1000)) {
        $text .= $bufferLine;
    }
    fclose($handle);
    $text = ereg_replace("--INSERTA--", $title, $text);

    /* SMTPでのメール送信 */
    smtp_send_mail("upload@xxxx.xxx", $email, $title, $text);
}

/*------------------------------------------------------------------*/
/*                      メイン処理開始                              */
/*------------------------------------------------------------------*/

$msg        = get_data();
$subject    = "";
$from       = "";
$text       = "";
$atta       = "";
$part       = "";
$filename   = "";

/* メールの情報をHEADとBODYに分割する */
list($head, $body)  = mime_split($msg);

/* メールから日付の抽出 */
eregi("Date:[ \t]*([^\n]+)", $head, $datereg);
if (($now = strtotime($datereg[1])) == -1) {
    $now = time();
}

/* 改行コードを削除 */
$head = ereg_replace("\n? ", "", $head);

/* サブジェクトの抽出 */
if (eregi("\nSubject:[ \t]*([^\n]+)", $head, $subreg)) {
    $subject = $subreg[1];
    /* MIME Bﾃﾞｺｰﾄﾞ */
    while(eregi("(.*)=\?iso-2022-jp\?B\?([^\?]+)\?=(.*)", $subject, $regs)) {
        $subject = $regs[1] . base64_decode($regs[2]) . $regs[3];
    }
    /* MIME Qﾃﾞｺｰﾄﾞ */
    while(eregi("(.*)=\?iso-2022-jp\?Q\?([^\?]+)\?=(.*)", $subject, $regs)) {
        $subject = $regs[1] . quoted_printable_decode($regs[2]) . $regs[3];
    }
    $subject  = htmlspecialchars(convert($subject));
    list($user_seq, $text_seq) = explode(",", $subject);
    $user_seq = base64_decode($user_seq);
    $text_seq = base64_decode($text_seq);
}

/* 送信者アドレスの抽出 */
if (eregi("From:[ \t]*([^\n]+)", $head, $freg)) {
    $from = addr_search($freg[1]);
} elseif (eregi("Reply-To:[ \t]*([^\n]+)", $head, $freg)) {
    $from = addr_search($freg[1]);
} elseif (eregi("Return-Path:[ \t]*([^\n]+)", $head, $freg)) {
    $from = addr_search($freg[1]);
}
$email = $from;

/* マルチパートならばバウンダリに分割 */
$part = split_multipart_data($head, $body);

/* メール本文か添付ファイルを取得 */
get_file($part);

/*------------------------------------------------------------------*/
/*                      メイン処理終了                              */
/*------------------------------------------------------------------*/

?>
