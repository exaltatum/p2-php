<?php
// {{{ SettingTxt

/**
 * p2 - 2ch の SETTING.TXT を扱うクラス
 * http://news19.2ch.net/newsplus/SETTING.TXT
 *
 * @since 2006/02/27
 */
class SettingTxt
{
    // {{{ properties

    public $setting_array; // SETTING.TXTをパースした連想配列

    private $_host;
    private $_bbs;
    private $_url;           // SETTING.TXT のURL
    private $_setting_txt;   // SETTING.TXT ローカル保存ファイルパス
    private $_setting_srd;   // p2_kb_setting.srd $this->setting_array を serialize() したデータファイルパス
    private $_cache_interval;

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     */
    public function __construct($host, $bbs)
    {
        global $_conf;
        $this->_cache_interval = 60 * 60 * 12; // キャッシュは12時間有効

        $this->_host = $host;
        $this->_bbs =  $bbs;

        $dat_host_bbs_dir_s = P2Util::datDirOfHostBbs($host, $bbs);
        $this->_setting_txt = $dat_host_bbs_dir_s . 'SETTING.TXT';
        $this->_setting_srd = $dat_host_bbs_dir_s . 'p2_kb_setting.srd';

        // 接続先が2ch.netならばSSL通信を行う(pinkは対応していないのでしない)
        if (P2Util::isHost2chs($host) && ! P2Util::isHostBbsPink($host) && $_conf['2ch_ssl.subject']) {
            $this->_url = 'https://' . $host . '/' . $bbs . '/SETTING.TXT';
        } else {
            $this->_url = 'http://' . $host . '/' . $bbs . '/SETTING.TXT';
        }
        //$this->_url = P2Util::adjustHostJbbs($this->_url); // したらばのlivedoor移転に対応。読込先をlivedoorとする。

        $this->setting_array = array();

        // SETTING.TXT をダウンロード＆セットする
        $this->dlAndSetData();
    }

    // }}}
    // {{{ dlAndSetData()

    /**
     * SETTING.TXT をダウンロード＆セットする
     *
     * @return boolean セットできれば true、できなければ false
     */
    public function dlAndSetData()
    {
        $this->downloadSettingTxt();

        return $this->setSettingArray();
    }

    // }}}
    // {{{ downloadSettingTxt()

    /**
     * SETTING.TXT をダウンロードして、パースして、キャッシュする
     *
     * @return boolean 実行成否
     */
    public function downloadSettingTxt()
    {
        global $_conf;

        // まちBBS・したらば は SETTING.TXT が存在しないものとする
        if (P2Util::isHostMachiBbs($this->_host) || P2Util::isHostJbbsShitaraba($this->_host)) {
            return false;
        }

        FileCtl::mkdirFor($this->_setting_txt); // 板ディレクトリが無ければ作る

        if (file_exists($this->_setting_srd) && file_exists($this->_setting_txt)) {
            // 更新しない場合は、その場で抜けてしまう
            if (!empty($_GET['norefresh']) || isset($_REQUEST['word'])) {
                return true;
            // キャッシュが新しい場合も抜ける
            } elseif ($this->isCacheFresh()) {
                return true;
            }
            $modified = http_date(filemtime($this->_setting_txt));
        } else {
            $modified = false;
        }

        // DL
        try {
            $req = P2Util::getHTTPRequest2($this->_url, HTTP_Request2::METHOD_GET);
            $modified && $req->setHeader("If-Modified-Since", $modified);

            $response = $req->send();

            $code = $response->getStatus();
            if ($code == 302) {
                // ホストの移転を追跡
                $new_host = BbsMap::getCurrentHost($this->host, $this->bbs);
                if ($new_host != $this->host) {
                    $aNewSettingTxt = new SettingTxt($new_host, $this->_bbs);
                    return $aNewSettingTxt->downloadSettingTxt();
                }
            } elseif ($code == 200 || $code == 206) {
                //var_dump($req->getResponseHeader());
                $body = $response->getBody();
                // したらば or be.2ch.net ならEUCをSJISに変換
                if (P2Util::isHostJbbsShitaraba($this->host) || P2Util::isHostBe2chNet($this->host)) {
                    $body = mb_convert_encoding($body, 'CP932', 'CP51932');
                }
                if (FileCtl::file_write_contents($this->_setting_txt, $body) === false) {
                    p2die('cannot write file');
                }
                // パースしてキャッシュを保存する
                if (!$this->cacheParsedSettingTxt()) {
                    return false;
                }
            } elseif ($code == 304) {
                // touchすることで更新インターバルが効くので、しばらく再チェックされなくなる
                // （変更がないのに修正時間を更新するのは、少し気が進まないが、ここでは特に問題ないだろう）
                touch($this->_setting_txt);
                // 同時にキャッシュもtouchしないと、_setting_txtと_setting_srdで更新時間がずれ、
                // 毎回ここまで処理が来る（サーバへのヘッダリクエストが飛ぶ）場合がある。
                touch($this->_setting_srd);
            } else {
                $error_msg = $code;
            }
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
        }

        // DLエラー
        if (isset($error_msg) && strlen($error_msg) > 0) {
            $url_t = P2Util::throughIme($this->_url);
            $info_msg_ht = "<p class=\"info-msg\">Error: {$error_msg}<br>";
            $info_msg_ht .= "rep2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$this->_url}</a> に接続できませんでした。</p>";
            P2Util::pushInfoHtml($info_msg_ht);
            touch($this->_setting_txt); // DL失敗した場合も touch
            return false;

        }

        return true;
    }

    // }}}
    // {{{ isCacheFresh()

    /**
     * キャッシュが新鮮なら true を返す
     *
     * @return boolean 新鮮なら true。そうでなければ false。
     */
    public function isCacheFresh()
    {
        // キャッシュがある場合
        if (file_exists($this->_setting_srd)) {
            // キャッシュの更新が指定時間以内なら
            // clearstatcache();
            if (filemtime($this->_setting_srd) > time() - $this->_cache_interval) {
                return true;
            }
        }

        return false;
    }

    // }}}
    // {{{ cacheParsedSettingTxt()

    /**
     * SETTING.TXT をパースしてキャッシュ保存する
     *
     * 成功すれば、$this->setting_array がセットされる
     *
     * @return boolean 実行成否
     */
    public function cacheParsedSettingTxt()
    {
        global $_conf;

        $this->setting_array = array();

        if (!$lines = FileCtl::file_read_lines($this->_setting_txt)) {
            return false;
        }

        foreach ($lines as $line) {
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                $this->setting_array[$key] = $value;
            }
        }
        $this->setting_array['p2version'] = $_conf['p2version'];

        // パースキャッシュファイルを保存する
        if (FileCtl::file_write_contents($this->_setting_srd, serialize($this->setting_array)) === false) {
            return false;
        }

        return true;
    }

    // }}}
    // {{{ setSettingArray()

    /**
     * SETTING.TXT のパースデータを読み込む
     *
     * 成功すれば、$this->setting_array がセットされる
     *
     * @return boolean 実行成否
     */
    public function setSettingArray()
    {
        global $_conf;

        if (!file_exists($this->_setting_srd)) {
            return false;
        }

        $this->setting_array = unserialize(file_get_contents($this->_setting_srd));

        /*
        if ($this->setting_array['p2version'] != $_conf['p2version']) {
            unlink($this->_setting_srd);
            unlink($this->_setting_txt);
        }
        */

        return (bool)$this->setting_array;
    }

    // }}}
}

// }}}

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
