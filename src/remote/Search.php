<?php
namespace mia\miagroup\Remote;
use mia\miagroup\Lib\RemoteCurl;

class Search
{
    public function __construct($session_info)
    {
        $this->session_info = $session_info;
    }

    /**
     * 笔记搜索
     * @param $searchArr
     * @return array
     */
    public function noteSearch($searchArr)
    {
        //return ['267344', '267343', '267342', '267341', '267339', '267338', '267337'];
        $remote_curl = new RemoteCurl('subject_search');

        $result = $remote_curl->curl_remote('', $searchArr);

        if ($result['disp_num'] > 0) {
            return $result;
        } else {
            return [];
        }
    }

    /**
     * 用户搜索
     * @param $searchArr
     * @return array
     */
    public function userSearch($searchArr)
    {
        $remote_curl = new RemoteCurl('user_search');
        $param['q'] = $searchArr['key'];
        $param['start'] = $searchArr['page'] - 1;
        $param['rows'] = $searchArr['count'];
        $param['wt'] = 'json';
        $result = $remote_curl->curl_remote('', $param);
        if ($result['response']['numFound'] > 0) {
            return $result['response']['docs'];
        } else {
            return [];
        }
    }

    /**
     * 商品搜索
     * @param $searchArr
     * @return array
     */
    public function itemSearch($searchArr)
    {
        $remote_curl = new RemoteCurl('item_search');
        $result = $remote_curl->curl_remote('', $searchArr);
        return $result;
    }
}