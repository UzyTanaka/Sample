<?php
/*--------------------------------------------------------------------
    SOUCE   ��img_mail.php
    INFO    ���᡼��ź�դβ����ե��������¸
--------------------------------------------------------------------*/

/*--------------------------------------------------------------------
    FUNCTION    ��mime_split
    INFOMATION  ���إå�����ʸ��ʬ�䤹��
 *------------------------------------------------------------------*/
function mime_split($data = "") {
    $part    = split("\n\n", $data, 2);
    $part[1] = ereg_replace("\n[\t ]+", " ", $part[1]);
    return $part;
}

/*--------------------------------------------------------------------
    FUNCTION    ��addr_search
    INFOMATION  ���᡼�륢�ɥ쥹����Ф���
 *------------------------------------------------------------------*/
function addr_search($addr = "") {
    if (eregi("[-!#$%&\'*+\\./0-9A-Z^_`a-z{|}~]+@[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+", $addr, $fromreg)) {
        return $fromreg[0];
    }
    return false;
}

/*--------------------------------------------------------------------
    FUNCTION    ��convert
    INFOMATION  ��ʸ�������ɥ���С���JIS��SJIS
 *------------------------------------------------------------------*/
function convert($str = "") {
    return mb_convert_encoding($str, "SJIS", "JIS,SJIS");
}

/*--------------------------------------------------------------------
    FUNCTION    ��get_data
    INFOMATION  ���᡼��ǡ����μ���
 *------------------------------------------------------------------*/
function get_data(){
    /* ɸ�����Ϥ����������������˳�Ǽ���� */
    $input = fopen('php://stdin', 'r');
    while(!feof($input)) {
        $msg .= fgets($input);
    }

    /* ʸ�������к� */
    if (function_exists("mb_internal_encoding")) {
        mb_internal_encoding("SJIS");
    }

    return $msg;
}

/*--------------------------------------------------------------------
    FUNCTION    ��split_multipart_data
    INFOMATION  ���ޥ���ѡ��Ȥʤ�ХХ�������ʬ��
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
        /* ���̤Υƥ����ȥ᡼�� */
        $part[0] = $msg;
    }
    return $part;
}

/*--------------------------------------------------------------------
    FUNCTION    ��get_file
    INFOMATION  ���᡼����ʸ��ź�եե�������������
 *------------------------------------------------------------------*/
function get_file($part = "") {
    global  $now;           // �᡼���������
    global  $subject;       // �᡼��η�̾
    global  $from;          // �����ԥ᡼�륢�ɥ쥹

    $tmpdir  = "/XXXX/XXXX/XXXX/";                       // ������¸�Îގ��ڎ��Ď�
    $maxbyte = 2048000;                                  // 2048KB ����ź���̡ʥХ��ȡ�1�ե�����ˤĤ��ˢ�Ķ�����Τ���¸���ʤ�
    $maxtext = 1000;                                     // ������ʸʸ������Ⱦ�Ѥ�
    $subtype = "gif|jpe?g|png";                          // �б�MIME���֥����ס�����ɽ����Content-Type: image/jpeg�θ�����ʬ��octet-stream�ϴ�����
    $viri    = ".+\.exe$|.+\.zip$|.+\.pif$|.+\.scr$";    // ��¸���ʤ��ե�����(����ɽ��)

    foreach($part as $multi) {
        list($m_head, $m_body) = mime_split($multi);
        $m_body                = ereg_replace("\n\.\n$", "", $m_body);
        if (!eregi("Content-type: *([^;\n]+)", $m_head, $type)) {
            continue;
        }
        list($main, $sub)      = explode("/", $type[1]);

        /* ��ʸ��ǥ����� */
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

            $text = eregi_replace("([[:digit:]]{11})|([[:digit:]\-]{13})", "", $text);                          // �����ֹ���
            $text = eregi_replace("[_]{25,}", "", $text);                                                       // �������
            $text = ereg_replace("Content-type: multipart/appledouble;[[:space:]]boundary=(.*)", "", $text);    // mac���
            if (is_array( $word ) ) $text = str_replace($word, "", $text);                                      // ���������
            if ($len = strlen( $text ) > $maxtext) $text = substr($text, 0, $maxtext) . "...";                  // ʸ���������С�
            $text = str_replace(">","&gt;", $text);
            $text = str_replace("<","&lt;", $text);
            $text = str_replace("\n", "\r", $text);
            $text = str_replace("\r", "\n", $text);
            $text = preg_replace("/\n{2,}/", "\n\n", $text);
            $text = str_replace("\n", "<br>", $text);
        }

        /* �ե�����̾����� */
        if (eregi("name=\"?([^\"\n]+)\"?", $m_head, $filereg)) {
            $filename = ereg_replace("[\t\n]", "", $filereg[1]);
            while(eregi("(.*)=\?iso-2022-jp\?B\?([^\?]+)\?=(.*)", $filename, $regs)) {
                $filename = $regs[1] . base64_decode($regs[2]) . $regs[3];
                $filename = convert($filename);
            }
        }

        /* ź�եǡ�����ǥ����ɤ�����¸ */
        if (eregi("Content-Transfer-Encoding:.*base64", $m_head) && eregi($subtype, $sub)) {
            $tmp = base64_decode($m_body);  // base64�ǥ����ɤ���
            if (!$filename) {               // �ե�����̾�������Ǥ��ʤ��ä����ϻ���Υե�����̾���������
                $filename = $now . "." . $sub;
            }

            /* �ե����륵�����Ȳ����ե�����ξ��ǽ񤭹��߻���ξ��ϥե�����˽񤭽Ф���¸���� */
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
    FUNCTION    ��make_image_local
    INFOMATION  �����᡼�����������
 *------------------------------------------------------------------*/
function make_image_local($filename = "") {
    global  $user_seq;
    global  $text_seq;

    $getdir  = "/XXXX/XXXX/XXXX/";
    $savedir = "/XXXX/XXXX/XXXX/" . $user_seq . "/";

    /* ���������פ���� */
    define("IMAGETYPE_GIF", 1);
    define("IMAGETYPE_JPG", 2);
    define("IMAGETYPE_PNG", 3);

    /* �������祵�����ʽ�or���� */
    define("MAX_SIZE", 200);

    $img      = null;
    $max_size = MAX_SIZE;
    $size_x   = 0;
    $size_y   = 0;

    /* ���᡼�������פ�Ƚ�ꤹ�� */
    $img_type = exif_imagetype($getdir . $filename);

    /* ��¸�褬¸�ߤ��Ƥ��뤫�ɤ�����ǧ���� */
    if (!is_writable($savedir)) {
        mkdir($savedir, 0777);
        sleep(1);
        clearstatcache();
    }

    if (is_writable($savedir)) {
        umask(0111);

        /* �����˲������᡼����������� */
        if ($img_type == IMAGETYPE_GIF) {       // GIF�ե�����
            $img = imagecreatefromgif($getdir . $filename);
        } elseif ($img_type == IMAGETYPE_JPG) { // JPG�ե�����
            $img = imagecreatefromjpeg($getdir . $filename);
        } elseif ($img_type == IMAGETYPE_PNG) { // PNG�ե�����
            $img = imagecreatefrompng($getdir . $filename);
        } else {
            exit(99);
        }

        $size_x = ImageSX($img);
        $size_y = ImageSY($img);

        if ($img) {
            /* �����Υ������ʽĲ������å��� */
            if ($size_x <= 1 || $size_y <= 1) { //�Ĳ��Υ������ɤ��餫��1pix�ʲ��Ǥ����NOPHOTO
                return 0;
            }
        }

        /* �����Υ������ѹ� */
        if ($size_x >= $size_y) {
            /* �̾��Τ� */
            if ($max_size < $size_x) {
                $new_y = intval($size_y * $max_size / $size_x);
                $new_x = $max_size;
                /* �����ꥵ���� */
                $wk_img = imagecreatetruecolor($new_x, $new_y);
                /* ���ߡ��˥ꥵ�������ƥ��ԡ� */
                imagecopyresampled($wk_img, $img, 0, 0, 0, 0, $new_x, $new_y, $size_x, $size_y);
                $img    = $wk_img;
            }
        } else {
            /* �̾��Τ� */
            if ($max_size < $size_y) {
                $new_x = intval($size_x * $max_size / $size_y);
                $new_y = $max_size;
                /* �����ꥵ���� */
                $wk_img = imagecreatetruecolor($new_x, $new_y);
                /* ���ߡ��˥ꥵ�������ƥ��ԡ� */
                imagecopyresampled($wk_img, $img, 0, 0, 0, 0, $new_x, $new_y, $size_x, $size_y);
                $img    = $wk_img;
            }
        }

        if ($img_type == IMAGETYPE_GIF) {       // GIF�ե�����ξ��
            ImageGif($img, $savedir . $filename);
        } elseif ($img_type == IMAGETYPE_JPG) { // JPG�ե�����ξ��
            ImageJPEG($img, $savedir . $filename);
        } elseif ($img_type == IMAGETYPE_PNG) { // PNG�ե�����ξ��
            ImagePNG($img, $savedir . $filename);
        }

        /* �����õ� */
        imagedestroy($img);
    }
}

/*--------------------------------------------------------------------
    FUNCTION    ��uploaderr_send()
    INFOMATION  ���ѹ���λ�᡼�������
 *------------------------------------------------------------------*/
function uploaderr_send(){
    /* ���������Ʊ���ѿ�����ѤǤ���褦����� */
    global  $email;

    /* �᡼��������³�� */
    $title  = "Upload OK!!";
    $header = "From: info@xxxx.xxx\r\nReturn-Path: -finfo@xxxx.xxx\r\n";
    $handle = fopen('/XXXX/XXXX/XXXX/upload_err.txt', 'r');
    while($bufferLine = fgets($handle, 1000)) {
        $text .= $bufferLine;
    }
    fclose($handle);
    $text = ereg_replace("--INSERTA--", $title, $text);

    /* SMTP�ǤΥ᡼������ */
    smtp_send_mail("upload@xxxx.xxx", $email, $title, $text);
}

/*------------------------------------------------------------------*/
/*                      �ᥤ���������                              */
/*------------------------------------------------------------------*/

$msg        = get_data();
$subject    = "";
$from       = "";
$text       = "";
$atta       = "";
$part       = "";
$filename   = "";

/* �᡼��ξ����HEAD��BODY��ʬ�䤹�� */
list($head, $body)  = mime_split($msg);

/* �᡼�뤫�����դ���� */
eregi("Date:[ \t]*([^\n]+)", $head, $datereg);
if (($now = strtotime($datereg[1])) == -1) {
    $now = time();
}

/* ���ԥ����ɤ��� */
$head = ereg_replace("\n? ", "", $head);

/* ���֥������Ȥ���� */
if (eregi("\nSubject:[ \t]*([^\n]+)", $head, $subreg)) {
    $subject = $subreg[1];
    /* MIME B�Îގ����Ď� */
    while(eregi("(.*)=\?iso-2022-jp\?B\?([^\?]+)\?=(.*)", $subject, $regs)) {
        $subject = $regs[1] . base64_decode($regs[2]) . $regs[3];
    }
    /* MIME Q�Îގ����Ď� */
    while(eregi("(.*)=\?iso-2022-jp\?Q\?([^\?]+)\?=(.*)", $subject, $regs)) {
        $subject = $regs[1] . quoted_printable_decode($regs[2]) . $regs[3];
    }
    $subject  = htmlspecialchars(convert($subject));
    list($user_seq, $text_seq) = explode(",", $subject);
    $user_seq = base64_decode($user_seq);
    $text_seq = base64_decode($text_seq);
}

/* �����ԥ��ɥ쥹����� */
if (eregi("From:[ \t]*([^\n]+)", $head, $freg)) {
    $from = addr_search($freg[1]);
} elseif (eregi("Reply-To:[ \t]*([^\n]+)", $head, $freg)) {
    $from = addr_search($freg[1]);
} elseif (eregi("Return-Path:[ \t]*([^\n]+)", $head, $freg)) {
    $from = addr_search($freg[1]);
}
$email = $from;

/* �ޥ���ѡ��Ȥʤ�ХХ�������ʬ�� */
$part = split_multipart_data($head, $body);

/* �᡼����ʸ��ź�եե��������� */
get_file($part);

/*------------------------------------------------------------------*/
/*                      �ᥤ�������λ                              */
/*------------------------------------------------------------------*/

?>
