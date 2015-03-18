<?php
/*--------------------------------------------------------------------
    FUNCTION    ��convert_string()
    INFOMATION  ��ʸ���������Ѵ����ʸ������֤�
 -------------------------------------------------------------------*/
function convert_string($str = "", $enc = "") {
    $ret_enc = detect_encoding_ja($str);                   // kanji_func.php�δؿ����ʸ�������ɤ�Ƚ�̤���
    $str     = mb_convert_encoding($str, $enc, $ret_enc);  // Ǥ�դ�ʸ�������ɤ˥���С��Ȥ���

    return $str;
}

/*--------------------------------------------------------------------
    FUNCTION    ��escape_string
    INFOMATION  ������ʸ����Υ��������׽���
 -------------------------------------------------------------------*/
function escape_string($str = "") {
    global  $extension_code_flg;
    
    if (preg_match("/(?:\x87[\x40-\x75\x7e\x80-\x9c]|"                              // NEC�ü�ʸ��
                 . "\xed[\x40-\x7e\x80-\xfc]|\xee[\x40-\x7e\x80-\xec\xef-\xfc]|"    // NEC����IBM��ĥʸ��
                 . "[\xfa-\xfb][\x40-\x7e\x80-\xfc]|\xfc[\x40-\x4b]"                // IBM��ĥʸ��
                 . ")+$/", $str)) {
        $extension_code_flg = 1;
    }
    
    $str = trim(mb_convert_kana($str, "sKV", mb_detect_encoding($str)));

    $str = str_replace("&#8722;", "-", $str);
    $str = str_replace("&#59;", ";", $str);

    $str = html_entity_decode($str, ENT_NOQUOTES);                              // HTML����ƥ��ƥ������ä���ǥ����ɤ���

    $str = str_replace("\\'", "'", $str);                                       // \'����'���ִ�����
    $str = str_replace("\\\"", "\"", $str);                                     // \"����"���ִ�����
    $str = str_replace("\\\\", "\\", $str);                                     // \\����\���ִ�����

    $str = str_replace(";", "&#59;", $str);

    $str = htmlentities($str, ENT_NOQUOTES, mb_detect_encoding($str));          // HTML����ƥ��ƥ����Ѵ�����

    $str = str_replace("&amp;#59;", "&#59;", $str);

    return $str;
}

/*--------------------------------------------------------------------
    FUNCTION    ��foo
    INFOMATION  ���ɣ���ʸ����������ؿ�
 -------------------------------------------------------------------*/
function foo($mode = "") {
    $ar[] = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z");
    $ar[] = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
    $ar[] = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 0);

    mt_srand((double)microtime() * 1000000);
    $type = mt_rand(0, 2);
    if ($mode == 1) {
        /* �ѿ����� */
        if ($type == 2){
            $ret = mt_rand(0, 10);
        } else {
            $ret = mt_rand(0, 25);
        }
    } else {
        /* �����Τ� */
        $type = 2;
        $ret  = mt_rand(0, 10);
    }
    return $ar[$type][$ret];
}

/*--------------------------------------------------------------------
    FUNCTION    ��makeID
    INFOMATION  ��Ǥ�դη����ʸ���������ޤ��ϱѿ��ǥ�����˺���
 -------------------------------------------------------------------*/
function makeID($mode = "", $length = "") {
    for($ii = 0, $workid = ""; $ii < $length; $ii++) {
        $i[ $ii ] = foo($mode);
        $workid   = $workid . $i[$ii];
    }

    return $workid;
}

/*--------------------------------------------------------------------
    FUNCTION    ��make_html
    INFOMATION  ��HTML�ե�������������
                ��infile���ƥ�ץ졼�ȥե�����̾
                ��outfile�����ϻ��ե�����̾
                ��hankana��Ⱦ�ѥ��ʤλ��Ѥ����
                ��inArray��������ǡ�����Ϣ������
 *------------------------------------------------------------------*/
function make_html($infile = "", $outfile = "", $hankana = "", $inArray = "") {
    /* �ե�����Υ��ơ������Υ���å���򥯥ꥢ���� */
    clearstatcache();

    /* ���Ǥ˥ե����뤬�񤭽Ф���Ƥ�����Ͻ�����ȴ���� */
    if (!file_exists($outfile) ) {
        /* ���ե�������ɤ߹��� */
        if ($handle = fopen($infile, 'r')) {
            /* �ե����뤬�����ץ������ */
            while($bufferLine = fgets($handle, 1000)) {
                $text .= $bufferLine;
            }
            fclose($handle);

            /* HTML�ե�����˺�������ǡ����������� */
            if ($inArray) {
                foreach ($inArray as $key => $value) {
                    if (!is_int($key)) {
                        $search = "--".$key."--";
                    } else {
                        $key    = sprintf("%03d", $key);
                        $search = "--STRING_" . $key . "--";
                    }
                    $text = str_replace($search, $value, $text);
                }
            }

            /* Ⱦ�ѥ������ʤ����Ѥ����� */
            if ($hankana) {
                $ret_enc = detect_encoding_ja($text);
                $text    = mb_convert_kana($text, "aks", $ret_enc);
            }

            /* �ɤ߹�����ǡ�����SJIS���Ѵ����� */
            $text = convert_string($text, "SHIFT-JIS");
        } else {
            /* �ե�����Υ����ץ�˼��� */
            return -1;
        }

        /* ���ꤵ�줿�ǥ��쥯�ȥ��HTML�ե������񤭽Ф� */
        if ($handle = fopen($outfile, 'w')) {
            // �����ץ󤷤��ե������$somecontent��񤭹��ߤޤ�
            if (fwrite($handle, $text) === FALSE) {
                /* �񤭽Ф��˼��� */
                return -2;
            } else {
                /* �񤭽Ф��ޤǴ�λ */
                fclose($handle);
                return 1;
            }
        }
    } else {
        /* �ե����뤬¸�ߤ���Τǽ�����ɬ�פ��ʤ� */
        return 0;
    }
}

/*--------------------------------------------------------------------
    FUNCTION    ��check_html
    INFOMATION  ��HTML�ե����뤬��������Ƥ��뤫��ǧ����
                ��outfile�����ϻ��ե�����̾
 -------------------------------------------------------------------*/
function check_html($outfile = "") {
    /* �ե�����Υ��ơ������Υ���å���򥯥ꥢ���� */
    clearstatcache();

    /* �ե����뤬�񤭽Ф���Ƥ��뤫�ɤ�����ǧ */
    if (file_exists($outfile)) {
        return 1;
    }
    return 0;
}

/*--------------------------------------------------------------------
    FUNCTION    ��delete_html
    INFOMATION  ��HTML�ե������������
                ��outfile�����ϻ��ե�����̾
 -------------------------------------------------------------------*/
function delete_html($outfile = "") {
    /* �ե�����Υ��ơ������Υ���å���򥯥ꥢ���� */
    clearstatcache();

    /* �񤭽Ф���Ƥ���ե����뤬���Τߺ������ */
    if (file_exists($outfile)) {
        unlink( $outfile );
        return 1;
    }
    return 0;
}

/*--------------------------------------------------------------------
    FUNCTION    ��check_terminal
    DATE        ��2007.01.11
    AUTHER      ��Yuji.Tanaka
    INFOMATION  ��IP�ȥ桼��������������Ȥˤ��ü�������å�����
 -------------------------------------------------------------------*/
function check_terminal( $ip_on="" ){
    $im      = "docomo";     // i-modeü��
    $sb      = "softbank";   // SoftBankü��
    $ez      = "au";         // auü��
    $wl      = "wilcom";     // WILCOMü��
    $pc      = "pc";         // PCü��
    $default = "au";         // ����ʳ���ü��
//    $default = "docomo";     // ����ʳ���ü��
//    $default = "softbank";   // ����ʳ���ü��

    /* �桼������Ƚ�̽��� */
    if (strstr($_SERVER['HTTP_USER_AGENT'], 'DoCoMo')) {
        for($i = 0, $check = 0; $i < count($docomo); $i++) {
            if (eregi($iplist[$i], $_SERVER['REMOTE_ADDR'])) {
                $check = 1;
            }
        }
        if ($check) $env = "i";
        else        $env = "o";
    } elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'SoftBank') || strstr($_SERVER['HTTP_USER_AGENT'], 'Vodafone') || 
        strstr($_SERVER['HTTP_USER_AGENT'], 'J-PHONE') || strstr($_SERVER['HTTP_USER_AGENT'], 'MOT-')) {
        for($i = 0, $check = 0; $i < count($softbank); $i++) {
            if (eregi($softbank[$i], $_SERVER['REMOTE_ADDR'])) {
                $check = 1;
            }
        }
        if ($check) $env = "v";
        else        $env = "o";
    } elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'KDDI') || strstr($_SERVER['HTTP_USER_AGENT'], 'UP.Browser')) {
        for($i = 0, $check = 0; $i < count($au); $i++) {
            if (eregi($au[$i], $_SERVER['REMOTE_ADDR'])) {
                $check = 1;
            }
        }
        if ($check) $env = "ez";
        else        $env = "o";
    } elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'DDIPOCKET') || strstr($_SERVER['HTTP_USER_AGENT'],'WILLCOM')) {
        for($i = 0, $check = 0; $i < count($willcom); $i++) {
            if (eregi($willcom[$i], $_SERVER['REMOTE_ADDR'])) {
                $check = 1;
            }
        }
        if ($check) $env = "h";
        else        $env = "o";
    } elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'ASTEL') || strstr( $_SERVER['HTTP_USER_AGENT'], 'PDXGW') || 
      strstr($_SERVER['HTTP_USER_AGENT'], 'L-mode') || strstr($_SERVER['HTTP_USER_AGENT'], 'PlayStation2')) {
        $env = "o";
    } else {
        $env = "pc";
    }

    /* �����顼�к� */
    if ($env == "o") {
        if (strstr($_SERVER['HTTP_USER_AGENT'], 'DoCoMo')) {
            $env = "i";
        } elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'SoftBank') || strstr($_SERVER['HTTP_USER_AGENT'], 'Vodafone') || 
            strstr($_SERVER['HTTP_USER_AGENT'], 'J-PHONE') || strstr($_SERVER['HTTP_USER_AGENT'], 'MOT-')) {
            $env = "v";
        } elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'KDDI') || strstr($_SERVER['HTTP_USER_AGENT'], 'UP.Browser')) {
            $env = "ez";
        } elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'DDIPOCKET') || strstr($_SERVER['HTTP_USER_AGENT'],'WILLCOM')) {
            $env = "h";
        }
    }

    /* ü���ο���ʬ�� */
    if ($env == "i") {
        return $im;
    } elseif ($env == "v") {
        return $sb;
    } elseif ($env == "ez") {
        return $ez;
    } elseif ($env == "h") {
        return $wl;
    } else {
        return $default;
    }
}
