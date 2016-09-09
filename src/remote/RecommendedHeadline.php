<?php
namespace mia\miagroup\Remote;
class RecommendedHeadline
{

    public function __construct()
    {
        $this->config = \F_Ice::$ins->workApp->config->get('headline.host');
    }

    /**
     * 首次访问列表
     * @return array
     */
    public function listInit()
    {
        $url = $this->config['remote'].$this->config['action']['init'];
        $res = $this->_curlPost($url);
        return json_decode($res,true);
    }

    /**
     * 顶部下拉
     * @return array
     */
    public function listRefresh()
    {
        $url = $this->config['remote'].$this->config['action']['refresh'];
        $res = $this->_curlPost($url);
        return json_decode($res,true);
    }

    /**
     * 底部上拉
     * @return array
     */
    public function listNext()
    {
        $url = $this->config['remote'].$this->config['action']['next'];
        $res = $this->_curlPost($url);
        return json_decode($res,true);
    }

    /**
     * 首页滚动广告
     * @return array
     */
    public function listHomeBanner()
    {
        $url = $this->config['remote'].$this->config['action']['banner'];
        $res = $this->_curlPost($url);
        return json_decode($res,true);
    }



    private function _curlPost($url,$headers=[])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        if($headers){
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER , $headers);
        }
        else {
            curl_setopt($ch, CURLOPT_HEADER, false);
        }
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}