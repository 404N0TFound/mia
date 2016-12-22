<?php
namespace mia\miagroup\Remote;
class RecommendedHeadline
{

    public function __construct()
    {
        $this->config = \F_Ice::$ins->workApp->config->get('thrift.address.headline');
        $this->sconfig = \F_Ice::$ins->workApp->config->get('thrift.address.subject');
    }

    /**
     * 根据action获取头条列表
     * @param  init $userId
     * @param  init $channelId
     * @param  string $action [init,refresh,next,home_banner]
     * @return array
     */
    public function headlineList($channelId, $action = 'init', $userId = 0, $count = 10, $headlineIds = [])
    {
        $params = [
            'user_id' => $userId,
            'tab_id' => $channelId,
            'action' => $action,
            'num' => $count,
            'refer_ids' => json_encode($headlineIds),
        ];
        $url = $this->config['remote'] . 'list/' . $action;
        $res = $this->_curlPost($url, $params);
        $data = [];
        //4.9之前只返回ID
        if (!empty($res['list']) && strpos($res['list'][0], '_') === false) {
            foreach ($res['list'] as $v) {
                $data[] = $v . '_subject';
            }
        }
        //4.9之前只返回ID+类型
        if (!empty($res['list']) && strpos($res['list'][0], '_') !== false) {

            $data = $res['list'];

        }
        return $data;
    }


    public function promotionList(array $ids)
    {
        $params = [
            'id' => json_encode($ids),
            'type' => 'promotion',
        ];
        $url = $this->config['remote'] . 'list/getInfoById';
        $res = $this->_curlPost($url, $params);
        $data = [];
        if (!empty($res['list'])) {
            $data = $res['list'];
        }
        return $data;
    }

    public function subjectList($keyword, $type, $start, $rows = 10, $fl = 'id')
    {
        if ($type == 1) {
            //视频
            $df = 'video';
        } elseif ($type == 2) {
            //专栏
            $df = 'text';
        } else {
            $df = '';
        }
        $params = [
            'q' => $keyword,
            'wt' => 'json',
            'start' => $start,
            'rows' => $rows,
            'fl' => $fl,
            'df' => $df,
        ];
        $ext_paramer = http_build_query($params);
        $url = $this->sconfig['remote'] . "?" . $ext_paramer;
        $res = $this->_curlPost2($url);
        return $res;
    }

    public function headlineRelate($channelId, $subjectId, $userId = 0, $count = 10)
    {
        $params = [
            'user_id' => $userId,
            'doc_id' => $subjectId,
            'tab_id' => $channelId,
            'num' => $count,
        ];
        $url = $this->config['remote'] . 'doc/relate';
        $res = $this->_curlPost($url, $params);
        return $res['list'];
    }


    public function headlineRead($channelId, $subjectId, $userId = 0)
    {
        $params = [
            'user_id' => $userId,
            'doc_id' => $subjectId,
            'tab_id' => $channelId,
        ];
        $url = $this->config['remote'] . 'doc/read';
        $this->_curlPost($url, $params);
        return true;
    }


    private function _curlPost($url, $params, $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($headers) {
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        } else {
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
        $error_no = curl_errno($ch);
        $error_str = curl_error($ch);
        $getCurlInfo = curl_getinfo($ch);

        $result = json_decode($result, true);
        curl_close($ch);

        //记录日志
        \F_Ice::$ins->mainApp->logger_remote->info(array(
            'third_server' => 'headline',
            'type' => 'INFO',
            'request_param' => $params,
            'response_code' => $error_no,
            'response_data' => $result,
            'response_msg' => $error_str,
            'resp_time' => $getCurlInfo['total_time'],
        ));

        if ($result['ret'] != 0) {
            return false;
        } else {
            return $result['data'];
        }
    }

    private function _curlPost2($url, $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($headers) {
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        } else {
            curl_setopt($ch, CURLOPT_HEADER, false);
        }
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);

        $result = curl_exec($ch);
        $result = json_decode($result, true);
        curl_close($ch);

        if ($result['response']['numFound'] == 0) {
            return false;
        } else {
            return $result['response'];
        }
    }
}