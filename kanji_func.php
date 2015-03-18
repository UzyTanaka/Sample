<?php
/*--------------------------------------------------------------------
	SOUCE	��kanji_func.php
	INFO	��ʸ�������ɴ�Ϣ�ؿ�
	Copyright (c) 2006 interspace Co.,Ltd. All rights reserved.
--------------------------------------------------------------------*/

/*--------------------------------------------------------------------
 *	FUNCTION	��detect_encoding_ja()
 *	INFOMATION	��ʸ�������ɤ�Ĵ�٤��Ⱦ�Ѥ���̤�б���
 *------------------------------------------------------------------*/
function detect_encoding_ja($str = "") {
    $enc = @mb_detect_encoding($str, 'ASCII,JIS,EUC-JP,SJIS,UTF-8');

    switch($enc) {
        case FALSE   :
        case 'ASCII' : 
        case 'JIS'   : 
        case 'UTF-8' : break;
        case 'EUC-JP' :
            // ������ EUC-JP �򸡽Ф�����硢EUC-JP �Ȥ���Ƚ��
            if (@mb_detect_encoding($str, 'SJIS,UTF-8,EUC-JP') === 'EUC-JP') {
                break;
            }
            $_hint = "\xbf\xfd" . $str; // "\xbf\xfd" : EUC-JP "��"

            // EUC-JP -> UTF-8 �Ѵ����˥ޥåԥ󥰤��ѹ������ʸ������( �� �� �� �ʤ�)
            mb_regex_encoding('EUC-JP');
            $_hint = mb_ereg_replace("\xad(?:\xe2|\xf5|\xf6|\xf7|\xfa|\xfb|\xfc|\xf0|\xf1|\xf2)", '', $_hint);

            $_tmp  = mb_convert_encoding($_hint, 'UTF-8', 'EUC-JP');
            $_tmp2 = mb_convert_encoding($_tmp, 'EUC-JP', 'UTF-8');
            if ($_tmp2 === $_hint) {
                // �㳰����( EUC-JP �ʳ���ǧ�������ϰ� )
                if (
                    // SJIS �ȽŤʤ��ϰ�(2�Х���|3�Х���|i�⡼�ɳ�ʸ��|1�Х���ʸ��)
                    !preg_match('/^(?:'
                        . '[\x8E\xE0-\xE9][\x80-\xFC]|\xEA[\x80-\xA4]|'
                        . '\x8F[\xB0-\xEF][\xE0-\xEF][\x40-\x7F]|'
                        . '\xF8[\x9F-\xFC]|\xF9[\x40-\x49\x50-\x52\x55-\x57\x5B-\x5E\x72-\x7E\x80-\xB0\xB1-\xFC]|'
                        . '[\x00-\x7E]'
                        . ')+$/', $str ) && 
                    // UTF-8 �ȽŤʤ��ϰ�(���ѱѿ���|����|1�Х���ʸ��)
                    !preg_match( '/^(?:'
                        . '\xEF\xBC[\xA1-\xBA]|[\x00-\x7E]|'
                        . '[\xE4-\xE9][\x8E-\x8F\xA1-\xBF][\x8F\xA0-\xEF]|'
                        . '[\x00-\x7E]'
                        . ')+$/', $str )
                ) {
                    // ��Ｐ���ϰϤ�����ʤ��ä����ϡ�EUC-JP �Ȥ��Ƹ���
                    break;
                }
                // �㳰����2(���������٤�¿�����ʽϸ�� EUC-JP �Ȥ���Ƚ��)
                // (����|����|����|����|����|����|���|����|��|����)
                if (mb_ereg('^(?:'
                    . '\xE0\xDD\xE0\xEA|\xE0\xE8\xE0\xE1|\xE0\xF5\xE0\xEF|\xE1\xF2\xE1\xFB|'
                    . '\xE2\xFB\xE2\xF5|\xE6\xCE\xE2\xF1|\xE7\xAF\xE6\xF9|\xE8\xE7\xE8\xEA|'
                    . '\xE9\xAC\xE9\xAF|\xE9\xF1\xE9\xD9|[\x00-\x7E]'
                    . ')+$', $str)
                ) {
                    break;
                }
            }

        default :
            // ������ SJIS ��Ƚ�Ǥ��줿���ϡ�ʸ�������ɤ� SJIS �Ȥ���Ƚ��
            $enc = @mb_detect_encoding($str, 'UTF-8,SJIS');
            if ($enc === 'SJIS') {
                break;
            }
            // �ǥե���ȤȤ��� SJIS ������
            $enc = 'SJIS';

            $_hint = "\xe9\x9b\x80" . $str; // "\xe9\x9b\x80" : UTF-8 "��"

            // �Ѵ����˥ޥåԥ󥰤��ѹ������ʸ����Ĵ��
            mb_regex_encoding('UTF-8');
            $_hint = mb_ereg_replace("\xe3\x80\x9c", "\xef\xbd\x9e", $_hint);
            $_hint = mb_ereg_replace("\xe2\x88\x92", "\xe3\x83\xbc", $_hint);
            $_hint = mb_ereg_replace("\xe2\x80\x96", "\xe2\x88\xa5", $_hint);

            $_tmp  = mb_convert_encoding($_hint, 'SJIS', 'UTF-8');
            $_tmp2 = mb_convert_encoding($_tmp, 'UTF-8', 'SJIS');

            if ($_tmp2 === $_hint) {
                $enc = 'UTF-8';
            }
            // UTF-8 �� SJIS 2ʸ�����Ťʤ��ϰϤؤ��н�(SJIS ��ͥ��)
            if (preg_match('/^(?:[\xE4-\xE9][\x80-\xBF][\x80-\x9F][\x00-\x7F])+/', $str)) {
                $enc = 'SJIS';
            }
    }
    return $enc;
}

/*--------------------------------------------------------------------
 *	FUNCTION	��irregularCode()
 *	INFOMATION	��EUC-JP�Υ��쥮��顼��������ʸ�������ɤ�Ĵ�٤�
 *------------------------------------------------------------------*/
function irregularCode($str = "") {
    // �㳰����( EUC-JP �ʳ���ǧ�������ϰ� )
    if (
        // SJIS �ȽŤʤ��ϰ�(2�Х���|3�Х���|i�⡼�ɳ�ʸ��|1�Х���ʸ��)
        !preg_match('/^(?:'
            . '[\x8E\xE0-\xE9][\x80-\xFC]|\xEA[\x80-\xA4]|'
            . '\x8F[\xB0-\xEF][\xE0-\xEF][\x40-\x7F]|'
            . '\xF8[\x9F-\xFC]|\xF9[\x40-\x49\x50-\x52\x55-\x57\x5B-\x5E\x72-\x7E\x80-\xB0\xB1-\xFC]|'
            . '[\x00-\x7E]'
            . ')+$/', $str ) && 
        // UTF-8 �ȽŤʤ��ϰ�(���ѱѿ���|����|1�Х���ʸ��)
        !preg_match('/^(?:'
            . '\xEF\xBC[\xA1-\xBA]|[\x00-\x7E]|'
            . '[\xE4-\xE9][\x8E-\x8F\xA1-\xBF][\x8F\xA0-\xEF]|'
            . '[\x00-\x7E]'
            . ')+$/', $str )
    ) {
        // ��Ｐ���ϰϤ�����ʤ��ä����ϡ�EUC-JP �Ȥ��Ƹ���
        return true;
    }
    return false;
}

/*--------------------------------------------------------------------
 *	FUNCTION	��irregularString()
 *	INFOMATION	�����쥮��顼ʸ����Ĵ�٤�
 *------------------------------------------------------------------*/
function irregularString($str = "") {
    // �㳰����2(���������٤�¿�����ʽϸ�� EUC-JP �Ȥ���Ƚ��)
    // (����|����|����|����|����|����|���|����|��|����)
    if (mb_ereg('^(?:'
        . '\xE0\xDD\xE0\xEA|\xE0\xE8\xE0\xE1|\xE0\xF5\xE0\xEF|\xE1\xF2\xE1\xFB|'
        . '\xE2\xFB\xE2\xF5|\xE6\xCE\xE2\xF1|\xE7\xAF\xE6\xF9|\xE8\xE7\xE8\xEA|'
        . '\xE9\xAC\xE9\xAF|\xE9\xF1\xE9\xD9|[\x00-\x7E]'
        . ')+$', $str )
    ) {
        return true;
    }
    return false;
}

?>
