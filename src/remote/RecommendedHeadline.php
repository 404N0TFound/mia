<?php
namespace mia\miagroup\Remote;
class RecommendedHeadline
{

    public function __construct()
    {
        $this->config = \F_Ice::$ins->workApp->config->get('thrift.address.headline');
    }

    /**
     * 根据action获取头条列表
     * @param  init $userId
     * @param  init $channelId
     * @param  string $action  [init,refresh,next,home_banner]
     * @return array
     */
    public function headlineList($userId,$channelId,$action='init')
    {
        $params = [
            'uid'    => $userId,
            'tab_id' => $channelId,
            'action' => $action,
        ];
        $url = $this->config['remote'].'list/'.$action;
        $res = $this->_curlPost($url,$params);
        return json_decode($res,true);
    }



    public function headlineRelate($subjectId, $channelId, $userId)
    {
        $params = [
            'uid'=>$userId,
            'doc_id'=>$subjectId,
            'tab_id'=>$channelId,
        ];
        $url = $this->config['remote'].'doc/relate';
        $res = $this->_curlPost($url,$params);
        return json_decode($res,true);
    }


    public function headlineRead($userId,$subjectId,$channelId)
    {
        $params = [
            'uid'=>$userId,
            'doc_id'=>$subjectId,
            'tab_id'=>$channelId,
        ];
        $url = $this->config['remote'].'doc/read';
        $res = $this->_curlPost($url,$params);
        return json_decode($res,true);
    }



    private function _curlPost($url,$params,$headers=[])
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}