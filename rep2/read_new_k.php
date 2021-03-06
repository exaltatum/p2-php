<?php
/**
 * rep2 - スレッド表示スクリプト - 新着まとめ読み（携帯）
 * フレーム分割画面、右下部分
 */

require_once __DIR__ . '/../init.php';

$_login->authorize(); // ユーザ認証

// +Wiki
require_once P2_LIB_DIR . '/wiki/read.inc.php';

if ($_conf['iphone']) {
    include P2_LIB_DIR . '/toolbar_i.inc.php';
}

//==================================================================
// 変数
//==================================================================
$GLOBALS['rnum_all_range'] = $_conf['mobile.rnum_range'];

$sb_view = 'shinchaku';
$newtime = date('gis');

$online_num = 0;
$newthre_num = 0;

//=================================================
// 板の指定
//=================================================
if (isset($_GET['host'])) {
    $host = $_GET['host'];
} elseif (isset($_POST['host'])) {
    $host = $_POST['host'];
}
if (isset($_GET['bbs'])) {
    $bbs = $_GET['bbs'];
} elseif (isset($_POST['bbs'])) {
    $bbs = $_POST['bbs'];
}
if (isset($_GET['spmode'])) {
    $spmode = $_GET['spmode'];
} elseif (isset($_POST['spmode'])) {
    $spmode = $_POST['spmode'];
}

if (!(isset($host) && isset($bbs)) && !isset($spmode)) {
    p2die('必要な引数が指定されていません');
}

// 未読数制限
if (ctype_digit($_GET['unum_limit'])) {
    $unum_limit = (int)$_GET['unum_limit'];
} elseif (ctype_digit($_POST['unum_limit'])) {
    $unum_limit = (int)$_POST['unum_limit'];
} else {
    $unum_limit = 0;
}

//=================================================
// あぼーん&NGワード設定読み込み
//=================================================
$GLOBALS['ngaborns'] = NgAbornCtl::loadNgAborns();

//====================================================================
// メイン
//====================================================================

$aThreadList = new ThreadList();

// 板とモードのセット ===================================
$ta_keys = array();
if ($spmode) {
    if ($spmode == 'taborn' or $spmode == 'soko') {
        $aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));
    }
    $aThreadList->setSpMode($spmode);
} else {
    $aThreadList->setIta($host, $bbs, P2Util::getItaName($host, $bbs));

    // スレッドあぼーんリスト読込
    $taborn_file = $aThreadList->getIdxDir() . 'p2_threads_aborn.idx';
    if ($tabornlines = FileCtl::file_read_lines($taborn_file, FILE_IGNORE_NEW_LINES)) {
        $ta_num = sizeof($tabornlines);
        foreach ($tabornlines as $l) {
            $tarray = explode('<>', $l);
            $ta_keys[ $tarray[1] ] = true;
        }
    }
}

// ソースリスト読込
if ($spmode == 'merge_favita') {
    if ($_conf['expack.misc.multi_favs'] && !empty($_conf['m_favita_set'])) {
        $merged_faivta_read_idx = $_conf['pref_dir'] . '/p2_favita' . $_conf['m_favita_set'] . '_read.idx';
    } else {
        $merged_faivta_read_idx = $_conf['pref_dir'] . '/p2_favita_read.idx';
    }
    $lines = FileCtl::file_read_lines($merged_faivta_read_idx);
    if (is_array($lines)) {
        $have_merged_faivta_read_idx = true;
    } else {
        $have_merged_faivta_read_idx = false;
        $lines = $aThreadList->readList();
    }
} else {
    $lines = $aThreadList->readList();
}

// ページヘッダ表示 ===================================
$ptitle_hd = p2h($aThreadList->ptitle);
$ptitle_ht = "{$ptitle_hd} の 新着まとめ読み";
$matomeCache = new MatomeCache($ptitle_hd, $_conf['matome_cache_max']);
ob_start();

// &amp;sb_view={$sb_view}
if ($aThreadList->spmode) {
    $ita_url = "{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}{$_conf['k_at_a']}";
} else {
    $ita_url = "{$_conf['subject_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}{$_conf['k_at_a']}";
}
$sb_ht = <<<EOP
<a href="{$ita_url}">{$ptitle_hd}</a>
EOP;
$sb_ht_btm = <<<EOP
<a href="{$ita_url}"{$_conf['k_accesskey_at']['up']}>{$_conf['k_accesskey_st']['up']}{$ptitle_hd}</a>
EOP;

// iPhone
if ($_conf['iphone']) {
    $_conf['extra_headers_ht'] .= <<<EOS
<script type="text/javascript" src="js/jquery-{$_conf['jquery_version']}.min.js"></script>
<script type="text/javascript" src="js/jquery.skOuterClick.js"></script>
<script type="text/javascript" src="js/respopup_iphone.js?{$_conf['p2_version_id']}"></script>
EOS;
    // ImageCache2
    if ($_conf['expack.ic2.enabled']) {
        $_conf['extra_headers_ht'] .= <<<EOS
<link rel="stylesheet" type="text/css" href="css/ic2_iphone.css?{$_conf['p2_version_id']}">
<script type="text/javascript" src="js/json2.js?{$_conf['p2_version_id']}"></script>
<script type="text/javascript" src="js/ic2_iphone.js?{$_conf['p2_version_id']}"></script>
EOS;
    }
    // SPM
    if ($_conf['expack.spm.enabled']) {
        $_conf['extra_headers_ht'] .= <<<EOS
<script type="text/javascript" src="js/spm_iphone.js?{$_conf['p2_version_id']}"></script>
EOS;
    }
    // Limelight
    if ($_conf['expack.aas.enabled'] || $_conf['expack.ic2.enabled']) {
        $_conf['extra_headers_ht'] .= <<<EOS
<link rel="stylesheet" type="text/css" href="css/limelight.css?{$_conf['p2_version_id']}">
<script type="text/javascript" src="js/limelight.js?{$_conf['p2_version_id']}"></script>
<script type="text/javascript">
// <![CDATA[
document.addEventListener('DOMContentLoaded', function(event) {
    var limelight;
    document.removeEventListener(event.type, arguments.callee, false);
    limelight = new Limelight({ 'savable': true, 'title': true });
    limelight.bind();
    window._IRESPOPG.callbacks.push(function(container) {
        limelight.bind(null, container, true);
    });
}, false);
// ]]>
</script>
EOS;
    }
}

// ========================================================
// require_once P2_LIB_DIR . '/read_header.inc.php';

echo $_conf['doctype'];
echo <<<EOHEADER
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
{$_conf['extra_headers_ht']}
<title>{$ptitle_ht}</title>
</head>
EOHEADER;

if ($_conf['iphone']) {
    echo <<<EOP
<body class="nopad">
<div class="ntoolbar" id="header">
EOP;

    // 板に戻る
    echo toolbar_i_back_button( $ptitle_hd, $ita_url);

    echo '<div id="toolbar_header">';
    echo <<<EOP
<h1 class="ptitle hoverable">新着まとめ読み</span></h1>
</div>
EOP;


    $info_ht = P2Util::getInfoHtml();
    if (strlen($info_ht)) {
        echo "<div class=\"info\">{$info_ht}</div>";
    }

    echo '</div>';
} else{
    echo <<<EOP
<body{$_conf['k_colors']}>
<div id="read_new_header">{$sb_ht}の新まとめ
<a class="button" id="above" name="above" href="#bottom"{$_conf['k_accesskey_at']['bottom']}>{$_conf['k_accesskey_st']['bottom']}▼</a></div>\n
EOP;
    P2Util::printInfoHtml();
}

//==============================================================
// それぞれの行解析
//==============================================================

$linesize = sizeof($lines);
$subject_txts = array();

for ($x = 0; $x < $linesize; $x++) {

    if (isset($GLOBALS['rnum_all_range']) and $GLOBALS['rnum_all_range'] <= 0) {
        break;
    }

    $l = $lines[$x];
    $aThread = new ThreadRead();

    $aThread->torder = $x + 1;

    // データ読み込み
    // spmodeなら
    if ($aThreadList->spmode) {
        switch ($aThreadList->spmode) {
        case "recent":    // 履歴
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "res_hist":    // 書き込み履歴
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "fav":    // お気に
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "taborn":    // スレッドあぼーん
            $aThread->getThreadInfoFromExtIdxLine($l);
            $aThread->host = $aThreadList->host;
            $aThread->bbs = $aThreadList->bbs;
            break;
        case "palace": // スレの殿堂
            $aThread->getThreadInfoFromExtIdxLine($l);
            break;
        case "merge_favita": // お気に板をマージ
            if ($have_merged_faivta_read_idx) {
                $aThread->getThreadInfoFromExtIdxLine($l);
            } else {
                $aThread->key = $l['key'];
                $aThread->setTtitle($l['ttitle']);
                $aThread->rescount = $l['rescount'];
                $aThread->host = $l['host'];
                $aThread->bbs = $l['bbs'];
                $aThread->torder = $l['torder'];
            }
            break;
        }
    // subject (not spmode)の場合
    } else {
        $aThread->getThreadInfoFromSubjectTxtLine($l);
        $aThread->host = $aThreadList->host;
        $aThread->bbs = $aThreadList->bbs;
    }

    // hostもbbsも不明ならスキップ
    if (!($aThread->host && $aThread->bbs)) {
        unset($aThread);
        continue;
    }

    $subject_id = $aThread->host . '/' . $aThread->bbs;

    $aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);
    $aThread->getThreadInfoFromIdx(); // 既得スレッドデータをidxから取得

    // 新着のみ(for subject) =========================================
    if (!$aThreadList->spmode && $sb_view == 'shinchaku' && empty($_GET['word'])) {
        if ($aThread->unum < 1) {
            unset($aThread);
            continue;
        }
    }

    // スレッドあぼーんチェック =====================================
    if ($aThreadList->spmode != 'taborn' && !empty($ta_keys[$aThread->key])) {
        unset($ta_keys[$aThread->key]);
        continue; // あぼーんスレはスキップ
    }

    // spmode(殿堂入りを除く)なら ====================================
    if ($aThreadList->spmode && $sb_view != "edit") {

        // subject.txtが未DLなら落としてデータを配列に格納
        if (empty($subject_txts[$subject_id])) {
            $aSubjectTxt = new SubjectTxt($aThread->host, $aThread->bbs);

            $subject_txts[$subject_id] = $aSubjectTxt->subject_lines;
        }

        // スレ情報取得 =============================
        if (!empty($subject_txts[$subject_id])) {
            $thread_key = (string)$aThread->key;
            $thread_key_len = strlen($thread_key);
            foreach ($subject_txts[$subject_id] as $l) {
                if (strncmp($l, $thread_key, $thread_key_len) == 0) {
                    $aThread->getThreadInfoFromSubjectTxtLine($l); // subject.txt からスレ情報取得
                    break;
                }
            }
        }

        // 新着のみ(for spmode) ===============================
        if ($sb_view == 'shinchaku' && empty($_GET['word'])) {
            if ($aThread->unum < 1) {
                unset($aThread);
                continue;
            }
        }
    }

    // 未読数制限
    if ($unum_limit > 0 && $aThread->unum >= $unum_limit) {
        unset($aThread);
        continue;
    }

    if ($aThread->isonline) { $online_num++; } // 生存数set

    P2Util::printInfoHtml();

    $matomeCache->concat(ob_get_flush());
    flush();
    ob_start();

    if (($aThread->readnum < 1) || $aThread->unum) {
        readNew($aThread);
        $matomeCache->addReadThread($aThread);
    } elseif ($aThread->diedat) {
        echo $aThread->getdat_error_msg_ht;
        echo "<hr>\n";
    }

    $matomeCache->concat(ob_get_flush());
    flush();
    ob_start();

    // リストに追加 ========================================
    // $aThreadList->addThread($aThread);
    $aThreadList->num++;
    unset($aThread);
}

//$aThread = new ThreadRead();

//======================================================================
// スレッドの新着部分を読み込んで表示する
//======================================================================
function readNew($aThread)
{
    global $_conf, $newthre_num, $STYLE;
    global $spmode, $word, $newtime;

    $orig_no_label = !empty($_conf['expack.iphone.toolbars.no_label']);
    $_conf['expack.iphone.toolbars.no_label'] = true;

    $newthre_num++;

    //==========================================================
    // idxの読み込み
    //==========================================================

    //hostを分解してidxファイルのパスを求める
    $aThread->setThreadPathInfo($aThread->host, $aThread->bbs, $aThread->key);

    //FileCtl::mkdirFor($aThread->keyidx); // 板ディレクトリが無ければ作る //この操作はおそらく不要

    $aThread->itaj = P2Util::getItaName($aThread->host, $aThread->bbs);
    if (!$aThread->itaj) { $aThread->itaj = $aThread->bbs; }

    // idxファイルがあれば読み込む
    if ($lines = FileCtl::file_read_lines($aThread->keyidx, FILE_IGNORE_NEW_LINES)) {
        $data = explode('<>', $lines[0]);
    } else {
        $data = array_fill(0, 12, '');
    }
    $aThread->getThreadInfoFromIdx();

    //==================================================================
    // DATのダウンロード
    //==================================================================
    if (!($word and file_exists($aThread->keydat))) {
        $aThread->downloadDat();
    }

    // DATを読み込み
    $aThread->readDat();
    $aThread->setTitleFromLocal(); // ローカルからタイトルを取得して設定

    //===========================================================
    // 表示レス番の範囲を設定
    //===========================================================
    // 取得済みなら
    if ($aThread->isKitoku()) {
        $from_num = $aThread->readnum +1 - $_conf['respointer'] - $_conf['before_respointer_new'];
        if ($from_num > $aThread->rescount) {
            $from_num = $aThread->rescount - $_conf['respointer'] - $_conf['before_respointer_new'];
        }
        if ($from_num < 1) {
            $from_num = 1;
        }

        //if (!$aThread->ls) {
            $aThread->ls = "{$from_num}-";
        //}
    }

    $aThread->lsToPoint();

    //==================================================================
    // ヘッダ 表示
    //==================================================================
    $motothre_url = $aThread->getMotoThread();

    $ttitle_en = UrlSafeBase64::encode($aThread->ttitle);
    $ttitle_en_q = '&amp;ttitle_en=' . $ttitle_en;
    $bbs_q = '&amp;bbs=' . $aThread->bbs;
    $key_q = '&amp;key=' . $aThread->key;
    $host_bbs_key_q = 'host=' . $aThread->host . $bbs_q . $key_q;
    $popup_q = '&amp;popup=1';

    $itaj_hd = p2h($aThread->itaj);
    if ($spmode) {
        $read_header_itaj_ht = "({$itaj_hd})";
    } else {
        $read_header_itaj_ht = '';
    }

    if ($_conf['iphone']) {
        if ($read_header_itaj_ht !== '') {
            $read_header_itaj_ht = "<span class=\"btitle\">{$read_header_itaj_ht}</span>";
        }

        $read_header_ht = <<<EOP
<div class="ntoolbar mtoolbar mtoolbar_top" id="ntt{$newthre_num}">
<h2 class="ttitle">{$aThread->ttitle_hd} {$read_header_itaj_ht}</h2>
EOP;
        $read_header_ht .= '<div class="mover">';
        $read_header_ht .= toolbar_i_standard_button('img/gp2-down.png', '', sprintf('#ntt%d', $newthre_num + 1));
        $read_header_ht .= '</div>';

        $info_ht = P2Util::getInfoHtml();
        if (strlen($info_ht)) {
            $read_header_ht .= "<div class=\"info\">{$info_ht}</div>";
        }
        $read_header_ht .= '</div>';
    } else {
        P2Util::printInfoHtml();
        $read_header_ht = <<<EOP
<hr><div id="ntt{$newthre_num}" name="ntt{$newthre_num}"><font color="{$STYLE['mobile_read_ttitle_color']}"><b>{$aThread->ttitle_hd}</b></font> {$read_header_itaj_ht} <a href="#ntt_bt{$newthre_num}">▼</a></div><hr>
EOP;
    }

    //==================================================================
    // ローカルDatを読み込んでHTML表示
    //==================================================================
    $aThread->resrange['nofirst'] = true;
    $GLOBALS['newres_to_show_flag'] = false;
    $read_cont_ht = '';
    if ($aThread->rescount) {

        if ($_conf['iphone']) {
            $aShowThread = new ShowThreadI($aThread);
            if ($_conf['expack.spm.enabled']) {
                $read_cont_ht .= $aShowThread->getSpmObjJs();
            }

        } else {
            $aShowThread = new ShowThreadK($aThread);
        }

        $read_cont_ht .= $aShowThread->getDatToHtml();

        unset($aShowThread);
    }

    //==================================================================
    // フッタ 表示
    //==================================================================

    // 表示範囲
    if ($aThread->resrange['start'] == $aThread->resrange['to']) {
        $read_range_on = $aThread->resrange['start'];
    } else {
        $read_range_on = "{$aThread->resrange['start']}-{$aThread->resrange['to']}";
    }
    $read_range_ht = "{$read_range_on}/{$aThread->rescount}";

    // ツールバー部分HTML =======
    if ($spmode) {
        $toolbar_itaj_ht = "(<a href=\"{$_conf['subject_php']}?{$host_bbs_key_q}{$_conf['k_at_a']}\">{$itaj_hd}</a>)";
    } else {
        $toolbar_itaj_ht = '';
    }

    if ($_conf['iphone']) {
        if ($toolbar_itaj_ht !== '') {
            $toolbar_itaj_ht = "<span class=\"btitle\">{$toolbar_itaj_ht}</span>";
        }

        $read_footer_ht = <<<EOP
<div class="ntoolbar mtoolbar mtoolbar_bottom" id="ntt_btm{$newthre_num}">
<table><tbody><tr>
EOP;
        // 情報
        $read_footer_ht .= '<td>';
        $escaped_url = "info.php?{$host_bbs_key_q}{$ttitle_en_q}{$_conf['k_at_a']}";
        $read_footer_ht .= toolbar_i_opentab_button('img/gp5-info.png', '', $escaped_url);
        $read_footer_ht .= '</td>';
        // 表示範囲
        $read_footer_ht .= "<td colspan=\"3\"><span class=\"large\">{$read_range_ht}</span></td>";
        // ツール
        $read_footer_ht .= '<td>';
        $escaped_url = "spm_k.php?{$host_bbs_key_q}&amp;ls={$aThread->ls}&amp;spm_default={$aThread->resrange['to']}{$_conf['k_at_a']}";
        $read_footer_ht .= toolbar_i_opentab_button('img/glyphish/icons2/20-gear2.png', '', $escaped_url);
        $read_footer_ht .= '</td>';
        // タイトル等
        $read_footer_ht .= <<<EOP
</tr></tbody></table>
<div class="ttitle"><a href="{$_conf['read_php']}?{$host_bbs_key_q}&amp;offline=1&amp;rescount={$aThread->rescount}{$_conf['k_at_a']}" target="_blank">{$aThread->ttitle_hd}</a> {$toolbar_itaj_ht}</div>
<div class="mover">
EOP;
        $read_footer_ht .= toolbar_i_standard_button('img/gp1-up.png', '', "#ntt{$newthre_num}");
        $read_footer_ht .= '</div></div>';
    } else {
        $read_footer_ht = <<<EOP
<div id="ntt_bt{$newthre_num}" name="ntt_bt{$newthre_num}" class="read_new_toolbar">
{$read_range_ht}
<a href="info.php?{$host_bbs_key_q}{$ttitle_en_q}{$_conf['k_at_a']}">情</a>
<a href="spm_k.php?{$host_bbs_key_q}&amp;ls={$aThread->ls}&amp;spm_default={$aThread->resrange['to']}&amp;from_read_new=1{$_conf['k_at_a']}">特</a>
<br>
<a href="{$_conf['read_php']}?{$host_bbs_key_q}&amp;offline=1&amp;rescount={$aThread->rescount}{$_conf['k_at_a']}#r{$aThread->rescount}">{$aThread->ttitle_hd}</a> {$toolbar_itaj_ht} <a href="#ntt{$newthre_num}">▲</a>
</div>
<hr>\n
EOP;
    }

    // 透明あぼーんや表示数制限で新しいレス表示がない場合はスキップ
    if ($GLOBALS['newres_to_show_flag']) {
        echo $read_header_ht;
        echo $read_cont_ht;
        echo $read_footer_ht;
    }

    //==================================================================
    // key.idxの値設定
    //==================================================================
    if ($aThread->rescount) {

        $aThread->readnum = min($aThread->rescount, max(0, $data[5], $aThread->resrange['to']));

        $newline = $aThread->readnum + 1; // $newlineは廃止予定だが、旧互換用に念のため

        $sar = array($aThread->ttitle, $aThread->key, $data[2], $aThread->rescount, $aThread->modified,
                     $aThread->readnum, $data[6], $data[7], $data[8], $newline,
                     $data[10], $data[11], $aThread->datochiok);
        P2Util::recKeyIdx($aThread->keyidx, $sar); // key.idxに記録
    }

    $_conf['expack.iphone.toolbars.no_label'] = $orig_no_label;
}

//==================================================================
// ページフッタ表示
//==================================================================
$newthre_num++;

if ($unum_limit > 0) {
    $unum_limit_at_a = "&amp;unum_limit={$unum_limit}";
} else {
    $unum_limit_at_a = '';
}

$shinchaku_matome_url = "{$_conf['read_new_k_php']}?host={$aThreadList->host}&amp;bbs={$aThreadList->bbs}&amp;spmode={$aThreadList->spmode}&amp;nt={$newtime}{$unum_limit_at_a}{$_conf['k_at_a']}";

if ($aThreadList->spmode == 'merge_favita') {
    $shinchaku_matome_url .= $_conf['m_favita_set_at_a'];
}


if (!isset($GLOBALS['rnum_all_range']) or $GLOBALS['rnum_all_range'] > 0 or !empty($GLOBALS['limit_to_eq_to'])) {
    if (!empty($GLOBALS['limit_to_eq_to'])) {
        $has_next = -1;
    } else {
        $has_next = 0;
    }
} else {
    $has_next = 1;
    $shinchaku_matome_url .= '&amp;norefresh=1';
}

// {{{ ページフッタ・ツールバー

if ($_conf['iphone']) {
    if (!$aThreadList->num) {
        echo '<p class="empty">新着レスはないぽ</p>';
    }

    echo "<div class=\"ntoolbar\" id=\"footer\"><table id=\"ntt{$newthre_num}\"><tbody><tr>";

    // トップに戻る
    echo '<td>';
    echo toolbar_i_standard_button('img/glyphish/icons2/53-house.png', 'TOP', "index.php{$_conf['k_at_q']}");
    echo '</td>';

    // 板に戻る
    echo '<td colspan="2">';
    echo toolbar_i_standard_button('img/glyphish/icons2/104-index-cards.png', $ptitle_hd, $ita_url);
    echo '</td>';

    // 更新/続き
    echo '<td>';
    if ($has_next === 1) {
        $icon = 'img/gp4-next.png';
        $label = '続き';
    } elseif ($has_next === 0) {
        $icon = 'img/glyphish/icons2/01-refresh.png';
        $label = '更新';
    } else {
        $icon = 'img/glyphish/icons2/01-refresh.png';
        $label = '更新/続き';
    }
    echo toolbar_i_standard_button($icon, $label, $shinchaku_matome_url);
    echo '</td>';

    // 上へ
    echo '<td>';
    echo toolbar_i_standard_button('img/gp1-up.png', '上', '#header');
    echo '</td>';

    echo '</tr></tbody></table></div>';
} else {
    if (!$aThreadList->num) {
        echo '新着ﾚｽはないぽ<hr>';
    }

    if ($has_next === 1) {
        $str = '新着まとめの続き';
    } elseif ($has_next === 0) {
        $str = '新まとめを更新';
    } else {
        $str = '新まとめの更新/続き';
    }
    echo <<<EOP
<div id="read_new_footer">{$sb_ht_btm}の<a href="{$shinchaku_matome_url}"{$_conf['k_accesskey_at']['next']}>{$_conf['k_accesskey_st']['next']}{$str}</a>
<a class="button" id="bottom" name="bottom" href="#above"{$_conf['k_accesskey_at']['above']}>{$_conf['k_accesskey_st']['above']}▲</a></div>
<hr>
<div class="center">{$_conf['k_to_index_ht']}</div>
EOP;
}

// }}}

// iPhone
if ($_conf['iphone']) {
    // ImageCache2
    if ($_conf['expack.ic2.enabled']) {
        if (!function_exists('ic2_loadconfig')) {
            include P2EX_LIB_DIR . '/ImageCache2/bootstrap.php';
        }
        $ic2conf = ic2_loadconfig();
        if ($ic2conf['Thumb1']['width'] > 80) {
            include P2EX_LIB_DIR . '/ImageCache2/templates/info-v.tpl.html';
        } else {
            include P2EX_LIB_DIR . '/ImageCache2/templates/info-h.tpl.html';
        }
    }
    // SPM
    if ($_conf['expack.spm.enabled']) {
        echo ShowThreadI::getSpmElementHtml();
    }
}

echo '</body></html>';

$matomeCache->concat(ob_get_flush());

// NGあぼーんを記録
NgAbornCtl::saveNgAborns();

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
