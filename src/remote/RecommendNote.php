<?php
namespace mia\miagroup\Remote;

use mia\miagroup\Lib\RemoteCurl;
use mia\miagroup\Lib\Redis;

class RecommendNote
{
    public function __construct($session_info)
    {
        $this->session_info = $session_info;
        if (empty($this->session_info['dvc_id'])) { //兼容H5调用
            if ($this->session_info['current_uid']) {
                $this->session_info['dvc_id'] = $this->session_info['current_uid'];
            } else {
                $this->session_info['dvc_id'] = date("YmdH");
            }
        }
        if (empty($this->session_info['bi_session_id'])) {
            $this->session_info['bi_session_id'] = $this->session_info['dvc_id'].date("YmdH");
        }
    }

    /**
     * 获取个性化tab列表
     * @return array
     */
    public function getRecommendTabList()
    {
        $remote_curl = new RemoteCurl('index_cate_recommend');

        $params['did'] = $this->session_info['dvc_id'];//设备id
        $params['tp'] = 2;//取得最感兴趣的分类
        $params['sessionid'] = $this->session_info['bi_session_id'];

        $result = $remote_curl->curl_remote('/recommend_result', $params);
        $tabInfo = $result['pl_list'];
        //错误处理
        if ($result['msg'] == 'error' || $result === false || empty($tabInfo)) {
            //去redis 取数据
            $redis = new Redis('recommend/default');
            //这时候的刷新操作有问题
            $res = $redis->get(\F_Ice::$ins->workApp->config->get('busconf.rediskey.recommendCateKey'));
            $tabInfo = explode(' ', $res);
        }

        $tabInfo = array_slice($tabInfo, 0, 6);
        foreach ($tabInfo as $v) {
            if (is_array($v)) {
                $return[] = $v['id'];
            } else {
                $return[] = $v;
            }
        }
        return $return;
    }

    /**
     * 获取发现分类下笔记列表
     * @param $page
     * @param $count
     * @return array [sujectId_subject]  口碑帖子
     */
    public function getRecommendNoteList($page = 1, $count = 20)
    {
        $remote_curl = new RemoteCurl('index_cate_recommend');
        $params['did'] = $this->session_info['dvc_id'];//设备id
        $params['tp'] = 0;
        //取得分类下笔记
        $params['sessionid'] = $this->session_info['bi_session_id'];
        //$params['index'] = $page;  不需要传页数，推荐会把当前session_id下的曝光的id去除掉
        $params['pagesize'] = $count;

        $result = $remote_curl->curl_remote('/recommend_result', $params);
        $noteIds = $result['pl_list'];

        //错误处理
        if ($result['totalcount'] == 0 || $result['msg'] == 'error' || $result === false) {
            //去redis 取数据
            $redis = new Redis('recommend/default');
            //取热门文章，这时候的刷新操作有问题
            $res = $redis->get(\F_Ice::$ins->workApp->config->get('busconf.rediskey.recommendSubjectKey'));
            $idArr = explode(' ', $res);
            $noteIds = array_slice($idArr, ($page - 1) * $count, $count);
        }
        $return = [];
        foreach ($noteIds as $v) {
            if (is_array($v)) {
                $return[] = $v['id'] . "_subject";
            } else {
                $return[] = $v . "_subject";
            }
        }
        return $return;
    }

    /**
     * 获取育儿分类下笔记列表
     * @param $yuer_labels
     * @param $page
     * @param $count
     * @return array [sujectId_subject]  口碑帖子
     */
    public function getYuerNoteList($yuer_labels, $page = 1, $count = 20)
    {
        $remote_curl = new RemoteCurl('index_cate_recommend');
        $params['did'] = $this->session_info['dvc_id'];//设备id
        $params['sessionid'] = $this->session_info['bi_session_id'];
        $yuerTags = '';
        foreach ($yuer_labels as $v) {
            $yuerTags .= "t_".$v." 1 ";
        }
        $params['tagstr'] = trim($yuerTags);
        $params['pagesize'] = $count;
        $result = $remote_curl->curl_remote('/recommend_result', $params);
        $noteIds = $result['pl_list'];

        //错误处理
        if ($result['totalcount'] == 0 || $result['msg'] == 'error' || $result === false) {
            return [];
        }
        $return = [];
        foreach ($noteIds as $v) {
            if (is_array($v)) {
                $return[] = $v['id'] . "_subject";
            } else {
                $return[] = $v . "_subject";
            }
        }
        return $return;
    }

    /**
     * 获取某个分类下得个性化笔记列表
     * @param $tabName
     * @param $page
     * @param $count
     * @return array [sujectId_subject]  口碑帖子
     */
    public function getNoteListByCate($tabName, $page = 1, $count = 20)
    {
        $remote_curl = new RemoteCurl('index_cate_recommend');

        $params['did'] = $this->session_info['dvc_id'];//设备id
        $params['tp'] = 4;
        //取得分类下笔记
        $params['sessionid'] = $this->session_info['bi_session_id'];
        $params['cate'] = $tabName;
        //$params['index'] = $page;  不需要传页数，推荐会把当前session_id下的曝光的id去除掉
        $params['pagesize'] = $count;

        $result = $remote_curl->curl_remote('/recommend_result', $params);
        $noteIds = $result['pl_list'];

        //错误处理
        if ($result['totalcount'] == 0 || $result['msg'] == 'error' || $result === false) {
            //去redis 取数据
            $redis = new Redis('recommend/default');
            $tabName = explode(",",$tabName)[0];
            //取分类下热门文章，这时候的刷新操作有问题
            $res = $redis->get(sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.recommendCateSubjectKey'), $tabName));

            $idArr = explode(' ', $res);
            $noteIds = array_slice($idArr, ($page - 1) * $count, $count);
        }
        $return = [];
        foreach ($noteIds as $v) {
            if (is_array($v)) {
                $return[] = $v['id'] . "_subject";
            } else {
                $return[] = $v . "_subject";
            }
        }
        return $return;
    }

    public function getRelatedNote($subjectId, $page = 1, $limit = 10)
    {
        $remote_curl = new RemoteCurl('index_cate_recommend');

        $params['did'] = $this->session_info['dvc_id'];//设备id
        $params['tp'] = 1; //取相关帖子
        $params['sessionid'] = $this->session_info['bi_session_id'];
        $params['aid'] = $subjectId;
        $params['index'] = $page;
        $params['pagesize'] = $limit;

        $data = $remote_curl->curl_remote('/recommend_result', $params);
        
        $result = array();
        if (!empty($data['pl_list'])) {
            foreach ($data['pl_list'] as $v) {
                $result[] = $v['id'];
            }
        }
        return $result;
    }
}