<?php
/*--------------------------------------------------------------------
    FUNCTION    ：convert_string()
    INFOMATION  ：文字コード変換後の文字列を返す
 -------------------------------------------------------------------*/
function convert_string($str = "", $enc = "") {
    $ret_enc = detect_encoding_ja($str);                   // kanji_func.phpの関数より文字コードを判別する
    $str     = mb_convert_encoding($str, $enc, $ret_enc);  // 任意の文字コードにコンバートする

    return $str;
}

/*--------------------------------------------------------------------
    FUNCTION    ：escape_string
    INFOMATION  ：入力文字列のエスケープ処理
 -------------------------------------------------------------------*/
function escape_string($str = "") {
    global  $extension_code_flg;
    
    if (preg_match("/(?:\x87[\x40-\x75\x7e\x80-\x9c]|"                              // NEC特殊文字
                 . "\xed[\x40-\x7e\x80-\xfc]|\xee[\x40-\x7e\x80-\xec\xef-\xfc]|"    // NEC選定IBM拡張文字
                 . "[\xfa-\xfb][\x40-\x7e\x80-\xfc]|\xfc[\x40-\x4b]"                // IBM拡張文字
                 . ")+$/", $str)) {
        $extension_code_flg = 1;
    }
    
    $str = trim(mb_convert_kana($str, "sKV", mb_detect_encoding($str)));

    $str = str_replace("&#8722;", "-", $str);
    $str = str_replace("&#59;", ";", $str);

    $str = html_entity_decode($str, ENT_NOQUOTES);                              // HTMLエンティティがあったらデコードする

    $str = str_replace("\\'", "'", $str);                                       // \'から'に置換する
    $str = str_replace("\\\"", "\"", $str);                                     // \"から"に置換する
    $str = str_replace("\\\\", "\\", $str);                                     // \\から\に置換する

    $str = str_replace(";", "&#59;", $str);

    $str = htmlentities($str, ENT_NOQUOTES, mb_detect_encoding($str));          // HTMLエンティティに変換する

    $str = str_replace("&amp;#59;", "&#59;", $str);

    return $str;
}

/*--------------------------------------------------------------------
    FUNCTION    ：foo
    INFOMATION  ：ＩＤ用文字列の生成関数
 -------------------------------------------------------------------*/
function foo($mode = "") {
    $ar[] = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z");
    $ar[] = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
    $ar[] = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 0);

    mt_srand((double)microtime() * 1000000);
    $type = mt_rand(0, 2);
    if ($mode == 1) {
        /* 英数混在 */
        if ($type == 2){
            $ret = mt_rand(0, 10);
        } else {
            $ret = mt_rand(0, 25);
        }
    } else {
        /* 数字のみ */
        $type = 2;
        $ret  = mt_rand(0, 10);
    }
    return $ar[$type][$ret];
}

/*--------------------------------------------------------------------
    FUNCTION    ：makeID
    INFOMATION  ：任意の桁数の文字列を数字または英数でランダムに作成
 -------------------------------------------------------------------*/
function makeID($mode = "", $length = "") {
    for($ii = 0, $workid = ""; $ii < $length; $ii++) {
        $i[ $ii ] = foo($mode);
        $workid   = $workid . $i[$ii];
    }

    return $workid;
}

/*--------------------------------------------------------------------
    FUNCTION    ：make_html
    INFOMATION  ：HTMLファイルを作成する
                ：infile　テンプレートファイル名
                ：outfile　出力時ファイル名
                ：hankana　半角カナの使用を指定
                ：inArray　差込むデータの連想配列
 *------------------------------------------------------------------*/
function make_html($infile = "", $outfile = "", $hankana = "", $inArray = "") {
    /* ファイルのステータスのキャッシュをクリアする */
    clearstatcache();

    /* すでにファイルが書き出されている場合は処理を抜ける */
    if (!file_exists($outfile) ) {
        /* 元ファイルを読み込む */
        if ($handle = fopen($infile, 'r')) {
            /* ファイルがオープンに成功 */
            while($bufferLine = fgets($handle, 1000)) {
                $text .= $bufferLine;
            }
            fclose($handle);

            /* HTMLファイルに差し込むデータがある場合 */
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

            /* 半角カタカナを利用する場合 */
            if ($hankana) {
                $ret_enc = detect_encoding_ja($text);
                $text    = mb_convert_kana($text, "aks", $ret_enc);
            }

            /* 読み込んだデータをSJISに変換する */
            $text = convert_string($text, "SHIFT-JIS");
        } else {
            /* ファイルのオープンに失敗 */
            return -1;
        }

        /* 指定されたディレクトリにHTMLファイルを書き出す */
        if ($handle = fopen($outfile, 'w')) {
            // オープンしたファイルに$somecontentを書き込みます
            if (fwrite($handle, $text) === FALSE) {
                /* 書き出しに失敗 */
                return -2;
            } else {
                /* 書き出しまで完了 */
                fclose($handle);
                return 1;
            }
        }
    } else {
        /* ファイルが存在するので処理の必要がない */
        return 0;
    }
}

/*--------------------------------------------------------------------
    FUNCTION    ：check_html
    INFOMATION  ：HTMLファイルが作成されているか確認する
                ：outfile　出力時ファイル名
 -------------------------------------------------------------------*/
function check_html($outfile = "") {
    /* ファイルのステータスのキャッシュをクリアする */
    clearstatcache();

    /* ファイルが書き出されているかどうか確認 */
    if (file_exists($outfile)) {
        return 1;
    }
    return 0;
}

/*--------------------------------------------------------------------
    FUNCTION    ：delete_html
    INFOMATION  ：HTMLファイルを削除する
                ：outfile　出力時ファイル名
 -------------------------------------------------------------------*/
function delete_html($outfile = "") {
    /* ファイルのステータスのキャッシュをクリアする */
    clearstatcache();

    /* 書き出されているファイルが場合のみ削除する */
    if (file_exists($outfile)) {
        unlink( $outfile );
        return 1;
    }
    return 0;
}

/*--------------------------------------------------------------------
    FUNCTION    ：check_terminal
    DATE        ：2007.01.11
    AUTHER      ：Yuji.Tanaka
    INFOMATION  ：IPとユーザーエージェントによる端末チェック処理
 -------------------------------------------------------------------*/
function check_terminal( $ip_on="" ){
    $im      = "docomo";     // i-mode端末
    $sb      = "softbank";   // SoftBank端末
    $ez      = "au";         // au端末
    $wl      = "wilcom";     // WILCOM端末
    $pc      = "pc";         // PC端末
    $default = "au";         // それ以外の端末
//    $default = "docomo";     // それ以外の端末
//    $default = "softbank";   // それ以外の端末

    /* ユーザーの判別処理 */
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

    /* クローラー対策 */
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

    /* 端末の振り分け */
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
