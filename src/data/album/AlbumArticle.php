<?php
namespace mia\miagroup\Data\Album;

use \DB_Query;

class AlbumArticle extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_article';

    protected $mapping = array();

    /**
     * 批量查文章id
     * @params array() $subjectIds 蜜呀圈帖子IDs
     * @return array() 文章id列表
     */
    public function getBatchAlbumBySubjectId($subjectIds) {
        $where = array();
        $where[] = array(':eq', 'status', '1');
        if (is_array($subjectIds)) {
            $where[] = array(':in', 'subject_id', $subjectIds);
        } else {
            $where[] = array(':eq', 'subject_id', $subjectIds);
        }
        
        $orderBy = array('create_time DESC');
        $data = $this->getRows($where, 'subject_id, id', $limit = FALSE, $offset = 0, $orderBy);
        $result = array();
        if ($data) {
            foreach ($data as $val) {
                $result[$val['subject_id']] = $val['id'];
            }
        }
        return $result;
    }

    /**
     * 批量查文章内容
     * @params array() $articleIds 文章IDs
     * @return array() 文章信息列表
     */
    public function getBatchArticle($articleIds) {
        if (empty($articleIds)) {
            return array();
        }
        $result = array();
        $where = array();
        $where[] = array(':eq', 'status', '1');
        if (is_array($articleIds)) {
            $where[] = array(':in', 'id', $articleIds);
        }
        $orderBy = array('create_time DESC');
        $data = $this->getRows($where, array('id,album_id,user_id,subject_id,title,cover_image,content,is_recommend,h5_url,create_time'), $limit = FALSE, $offset = 0, $orderBy);
        foreach ($data as $v) {
            $result[$v['id']] = $v;
        }
        return $result;
    }

    /**
     * 批量查文章内容
     * @params array() $articleIds 文章IDs
     * @return array() 文章信息列表
     */
    public function getArticle($params) {
        $articleList = array();
        $limit = 10;
        $offset = 0;
        
        $where = array();
        $where[] = array(':eq', 'status', '1');
        if (is_array($params['user_id'])) {
            $where[] = array(':in', 'user_id', $params['user_id']);
        } else {
            $where[] = array(':eq', 'user_id', $params['user_id']);
        }
        if(isset($params['album_id']) && $params['album_id']){
            $where[] = array(':eq', 'album_id', $params['album_id']);
        }
        if (intval($params['iPageSize']) > 0) {
            $offset = ($params['page'] - 1) > 0 ? (($params['page'] - 1) * $params['iPageSize']) : 0;
            $limit = $params['iPageSize'];
        }
        $orderBy = array('create_time DESC');
        $idArr = $this->getRows($where, array('subject_id'), $limit, $offset, $orderBy);
        if($idArr){
            foreach($idArr as $value){
                $articleList[] = $value['subject_id'];
            }
        }
        return $articleList;
    }
    
    /**
     * 查文章简版内容（主要用于展示专栏）
     * @params array() user_id int 用户ID
     * @params array() album_id int 专栏专辑ID
     * @params array() page int 当前页码
     * @params array() iPageSize int 每页显示多少
     * @return array() 查文章简版内容（主要用于展示专栏）
     */
    public function getSimpleArticleList($params){
        $articleList = array();
        $limit = 10;
        $offset = 0;
        
        $where = array();
        $where[] = array(':eq', 'status', '1');
        if (isset($params['user_id']) && $params['user_id']) {
            $where[] = array(':eq', 'user_id', $params['user_id']);
        }
        if(isset($params['album_id']) && $params['album_id']){
            $where[] = array(':eq', 'album_id', $params['album_id']);
        }
        if (intval($params['iPageSize']) > 0) {
            $offset = ($params['page'] - 1) > 0 ? (($params['page'] - 1) * $params['iPageSize']) : 0;
            $limit = $params['iPageSize'];
        }
        $orderBy = array('create_time DESC');
        $SimpleArticleList = $this->getRows($where, array('id,album_id,subject_id,user_id,title,content'), $limit, $offset, $orderBy);
        if($SimpleArticleList){
            foreach($SimpleArticleList as $value){
                $value['content'] = mb_substr($value['content'],0,50,'utf-8').'....';
                $articleList[$value['album_id']] = $value;
            }
        }
        return $articleList;
    }

    /**
     * 精选文章列表
     * @return array() 精选文章列表
     */
    public function getRecommendAlbumArticleList($params) {
        $articleList = array();
        $limit = 10;
        $offset = 0;
        $where = array();
        
        $where[] = array(':eq', 'is_recommend', '1');
        $where[] = array(':eq', 'status', '1');
        if (intval($params['iPageSize']) > 0) {
            $offset = ($params['page'] - 1) > 0 ? (($params['page'] - 1) * $params['iPageSize']) : 0;
            $limit = $params['iPageSize'];
        }
        $orderBy = array('create_time DESC');
        $idArr = $this->getRows($where, array('subject_id'), $limit, $offset, $orderBy);
        if($idArr){
            foreach($idArr as $value){
                $articleList[] = $value['subject_id'];
            }
        }
        return $articleList;
    }
    
    /**
     * 查文章详情
     * @params array() $article_id 文章ID
     * @params array() $userId 用户ID
     * @return array() 文章详情
     */
    public function getArticleInfo($params) {
        if (empty($params)) {
            return array();
        }
        $result = array();
        $where = array();
        $where[] = array(':eq', 'status', '1');
        $where[] = array(':in', 'id', $params['articleId']);
        $where[] = array(':in', 'user_id', $params['user_id']);
        $data = $this->getRows($where, array('id,album_id,user_id,subject_id,title,cover_image,content,content_original,is_recommend,h5_url,create_time'), $limit = FALSE, $offset = 0, $orderBy);
        foreach ($data as $v) {
            $result[$v['id']] = $v;
        }
        return $result;
    }

    /*
     * 增加文章
     */
    public function addArticle($insertData) {
        $data = $this->insert($insertData);
        return $data;
    }

    /**
     * 更新文章
     * @param type $setData
     * @param type $where
     * @param type $orderBy
     * @param type $limit
     * @return int
     */
    public function updateSubject($setData, $where = []) {
        $data = $this->update($setData, $where);
        return $data;
    }
}
