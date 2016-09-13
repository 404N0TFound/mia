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
            'tabid' => $channelId,
            'action' => $action,
        ];
        $url = $this->config['remote'].'list/'.$action;
        $res = $this->_curlPost($url,$params);
        
        $data = [];
        if (!empty($res['list'])) {
            foreach ($res['list'] as $v) {
                $data[] = $v . '_subject';
            }
        }
        
        return $data;
    }



    public function headlineRelate($subjectId, $userId, $channelId)
    {
        $params = [
            'uid'=>$userId,
            'docid'=>$subjectId,
            'tabid'=>$channelId,
        ];
        $url = $this->config['remote'].'doc/relate';
        $res = $this->_curlPost($url,$params);
        return $res['list'];
    }


    public function headlineRead($userId,$subjectId,$channelId)
    {
        $params = [
            'uid'=>$userId,
            'docid'=>$subjectId,
            'tabid'=>$channelId,
        ];
        $url = $this->config['remote'].'doc/read';
        $this->_curlPost($url,$params);
        return true;
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
        $result = json_decode($result, true);
        curl_close($ch);
        
        if ($result['ret'] != 0) {
            return false;
        } else {
            return $result['data'];
        }
    }
}