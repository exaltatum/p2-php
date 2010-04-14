<?php

/**
 * jpgアプリのリモートホスト正規表現とIPアドレス帯域 (2009/06/04 時点)
 *
 * @link http://br.jig.jp/pc/ip_br.html
 */

// br***.jig.jp
$reghost = '/^br\\d+\\.jig\\.jp$/';

// @updated 2009/06/04
$bands = array(
    '59.106.14.175/32',
    '59.106.14.176/32',
    '59.106.23.169/32',
    '59.106.23.170/31',
    '59.106.23.172/31',
    '59.106.23.174/32',
    '112.78.114.171/32',
    '112.78.114.172/30',
    '112.78.114.176/29',
    '112.78.114.184/30',
    '112.78.114.188/31',
    '112.78.114.191/32',
    '112.78.114.192/29',
    '112.78.114.200/30',
    '112.78.114.204/31',
    '112.78.114.206/32',
    '112.78.114.208/32',
    '202.181.96.94/32',
    '202.181.98.153/32',
    '202.181.98.156/32',
    '202.181.98.160/32',
    '202.181.98.179/32',
    '202.181.98.182/32',
    '202.181.98.185/32',
    '202.181.98.196/32',
    '202.181.98.218/32',
    '202.181.98.221/32',
    '202.181.98.223/32',
    '202.181.98.247/32',
    '210.188.205.81/32',
    '210.188.205.83/32',
    '210.188.205.97/32',
    '210.188.205.166/31',
    '210.188.205.168/31',
    '210.188.205.170/32',
    '210.188.220.169/32',
    '210.188.220.170/31',
    '210.188.220.172/30',
    '219.94.133.167/32',
    '219.94.133.192/32',
    '219.94.133.243/32',
    '219.94.144.5/32',
    '219.94.144.6/31',
    '219.94.144.23/32',
    '219.94.144.24/32',
    '219.94.147.35/32',
    '219.94.147.36/30',
    '219.94.147.42/31',
    '219.94.147.44/32',
    '219.94.166.8/30',
    '219.94.166.173/32',
    '219.94.197.196/30',
    '219.94.197.200/30',
    '219.94.197.204/31'
);

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
