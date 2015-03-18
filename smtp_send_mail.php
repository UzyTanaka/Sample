<?php
/*--------------------------------------------------------------------
	SOUCE	��smtp_send_mail.func.php
	COMMENT	��SMTP�����᡼��ץ����
--------------------------------------------------------------------*/

/*--------------------------------------------------------------------
 *	FUNCTION	��err_disp
 *	INFOMATION	�����顼ɽ��
 *------------------------------------------------------------------*/
function err_disp(){
    $location	= "http://XXXX.XXX.XXX/error.html";
    header("Location: ".$location);
    header("Connection: close");
    exit;
}

/*--------------------------------------------------------------------
 *	FUNCTION	��send_message
 *	INFOMATION	�������С��̿��ؿ�
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
 *	FUNCTION	��send_smtp_mail
 *	INFOMATION	��SMTP�᡼�������ؿ�
 *------------------------------------------------------------------*/
function smtp_send_mail($input_from = "", $input_to = "", $input_subject = "", $input_body = "") {
    /* SMTP��³�����ʺ���30��ޤ���³���ȥ饤����� */
    $times = 30;
    for($i = 0; $i < $times; $i++) {
        $sock = fsockopen("smtp.XXXX.XXXXX.XXX", 25, $errno, $errstr, 30);
        if ($sock) break;       // ��������³���Ǥ����Τ�
        else       usleep(250); // ��³�Ǥ��ʤ��ä�����0.25���Ԥ�
    }

    /* ����˥����åȤ򳫤��ʤ��ä��Τǥ��顼��ɽ������ */
    if (!$sock) err_disp();

    /* �᡼������������������ѿ��˳�Ǽ */
    $from = $input_from;
    $to   = $input_to;

    /* �����ȥ��JIS�����ɤ˥��󥳡��ɤ��� */
    $check_subject = mb_detect_encoding($input_subject);
    $check_subject = detect_encoding_ja($input_subject);
    $subject       = mb_convert_encoding($input_subject, "JIS", $check_subject);
    $subject       = "=?ISO-2022-JP?B?" . base64_encode($subject) . "?=";

    /* ��ʸ��JIS�����ɤ˥��󥳡��ɤ��� */
    $check_body    = detect_encoding_ja($input_body);
    $body          = mb_convert_encoding($input_body, "JIS", $check_body);

    /* �᡼������� */
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
