<?php
/*--------------------------------------------------------------------
	SOUCE	：smtp_send_mail.func.php
	COMMENT	：SMTP送信メールプログラム
--------------------------------------------------------------------*/

/*--------------------------------------------------------------------
 *	FUNCTION	：err_disp
 *	INFOMATION	：エラー表示
 *------------------------------------------------------------------*/
function err_disp(){
    $location	= "http://XXXX.XXX.XXX/error.html";
    header("Location: ".$location);
    header("Connection: close");
    exit;
}

/*--------------------------------------------------------------------
 *	FUNCTION	：send_message
 *	INFOMATION	：サーバー通信関数
 *------------------------------------------------------------------*/
function send_message($input_from = "", $input_to = "", $input_subject = "", $input_body = "", $sock = "", $command = "") {
    fputs($sock, $command."\r\n");
    $buffer = fgets($sock, 512);
    if ((substr($buffer, 0, 3) == '+OK') || 
    (substr($buffer, 0, 3) == '354') || (substr($buffer, 0, 3) == '220') || 
    (substr($buffer, 0, 3) == '221') || (substr($buffer, 0, 3) == '250') ) {
        return $buffer;
    } else {
        err_disp();
    }
    return 0;
}

/*--------------------------------------------------------------------
 *	FUNCTION	：send_smtp_mail
 *	INFOMATION	：SMTPメール送信関数
 *------------------------------------------------------------------*/
function smtp_send_mail($input_from = "", $input_to = "", $input_subject = "", $input_body = "") {
    /* SMTP接続処理（最大30回まで接続をリトライする） */
    $times = 30;
    for($i = 0; $i < $times; $i++) {
        $sock = fsockopen("smtp.XXXX.XXXXX.XXX", 25, $errno, $errstr, 30);
        if ($sock) break;       // 正しく接続ができたので
        else       usleep(250); // 接続できなかった場合は0.25秒待つ
    }

    /* 正常にソケットを開けなかったのでエラーを表示する */
    if (!$sock) err_disp();

    /* メールの送信先送信元を変数に格納 */
    $from = $input_from;
    $to   = $input_to;

    /* タイトルをJISコードにエンコードする */
    $check_subject = mb_detect_encoding($input_subject);
    $check_subject = detect_encoding_ja($input_subject);
    $subject       = mb_convert_encoding($input_subject, "JIS", $check_subject);
    $subject       = "=?ISO-2022-JP?B?" . base64_encode($subject) . "?=";

    /* 本文をJISコードにエンコードする */
    $check_body    = detect_encoding_ja($input_body);
    $body          = mb_convert_encoding($input_body, "JIS", $check_body);

    /* メールの送信 */
    send_message($input_from, $input_to, $input_subject, $input_body, $sock, "EHLO xxxx.xxx");
    send_message($input_from, $input_to, $input_subject, $input_body, $sock, "MAIL FROM: " . $from);
    send_message($input_from, $input_to, $input_subject, $input_body, $sock, "RCPT TO: " . $to);
    send_message($input_from, $input_to, $input_subject, $input_body, $sock, "DATA");
    send_message($input_from, $input_to, $input_subject, $input_body, $sock, "To: " . $to);
    send_message($input_from, $input_to, $input_subject, $input_body, $sock, "From: " . $from);
    send_message($input_from, $input_to, $input_subject, $input_body, $sock, "Subject: " . $subject);
    send_message($input_from, $input_to, $input_subject, $input_body, $sock, "Mime-Version: 1.0");
    send_message($input_from, $input_to, $input_subject, $input_body, $sock, "Content-Type: text/plain; charset=ISO-2022-JP");
    send_message($input_from, $input_to, $input_subject, $input_body, $sock, "Content-Transfer-Encoding: 7bit");
    send_message($input_from, $input_to, $input_subject, $input_body, $sock, $body . "\r\n.");
    send_message($input_from, $input_to, $input_subject, $input_body, $sock, "QUIT");
    fclose($sock);
}

?>
