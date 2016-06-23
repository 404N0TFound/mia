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
        if(isset($params['articleId']) && $params['articleId']){
            $where[] = array(':in', 'id', $params['articleId']);
        }
        if(isset($params['articleId']) && $params['articleId']){
            $where[] = array(':in', 'user_id', $params['user_id']);
        }
        $data = $this->getRows($where, array('id,album_id,user_id,subject_id,title,cover_image,content,content_original,is_recommend,h5_url,ext_info,create_time'), $limit = FALSE, $offset = 0, $orderBy);
        foreach ($data as $v) {
            $v['label'] = json_decode($v['ext_info'],true)['label'];
            $v['cover_image'] = json_decode($v['cover_image'],true);
            unset($v['ext_info']);
            $result[$v['id']] = $v;
        }
        return $result;
    }
    
    /**
     * 文章预览接口
     * @params array() user_id 用户ID
     * @params array() album_id 专栏辑ID
     * @params array() article_id 文章ID
     * @return array() 文章内容
     */
    public function getArticlePreview($params) {
        if (empty($params)) {
            return array();
        }
        $where = array();
        $where[] = array(':eq', 'status', '0');
        if(isset($params['id']) && $params['id']){
            $where[] = array(':eq', 'id', $params['id']);
        }
        if(isset($params['album_id']) && $params['album_id']){
            $where[] = array(':eq', 'album_id', $params['album_id']);
        }
        if(isset($params['user_id']) && $params['user_id']){
            $where[] = array(':eq', 'user_id', $params['user_id']);
        }
        $data = $this->getRow($where, array('content_original'), $limit = FALSE, $offset = 0);
        return $data;
    }
    
    /**
     * 更新专栏接口(这里只有title可以改)
     * @params array() $album_id 专栏辑ID
     * @params array() $id 专栏ID
     * @params array() $userId 用户ID
     * @return array() true false
     */
    public function updateAlbum($whereCon,$setData,$orderBy = FALSE, $limit = FALSE) {
        $where = array();
        if(isset($whereCon['id']) && $whereCon['id']){
            $where[] = array('id',$whereCon['id']);
        }
        if(isset($whereCon['user_id']) && $whereCon['user_id']){
            $where[] = array('user_id',$whereCon['user_id']);
        }
        if(isset($whereCon['album_id']) && $whereCon['album_id']){
            $where[] = array('album_id',$whereCon['album_id']);
        }
        $set = array();
        if(isset($setData['title']) && $setData['title']){
            $set[] = array('title',$setData['title']);
        }
        $data = $this->update($set, $where, $orderBy, $limit);
        return $data;
    }
    
    /**
     * 更新文章详情接口
     * $con = array('user_id'=>'1145319','id'=>6,'album_id'=>10)
     * $set = array('title'=>'我是标题党')
     * @params array() $album_id 专栏辑ID
     * @params array() $id 专栏ID
     * @params array() $userId 用户ID
     * @return array() true false
     */
    public function updateAlbumArticle($whereCon,$setData,$orderBy = FALSE, $limit = FALSE) {
        $where = array();
        if(isset($whereCon['id']) && $whereCon['id']){
            $where[] = array('id',$whereCon['id']);
        }
        if(isset($whereCon['user_id']) && $whereCon['user_id']){
            $where[] = array('user_id',$whereCon['user_id']);
        }
        if(isset($whereCon['album_id']) && $whereCon['album_id']){
            $where[] = array('album_id',$whereCon['album_id']);
        }
        
        
        $set = array();
        if(isset($setData['content']) && $setData['content']){
            $set[] = array('content',$setData['content']);
        }
        if(isset($setData['content_original']) && $setData['content_original']){
            $set[] = array('content_original',$setData['content_original']);
        }
        if(isset($setData['status']) && $setData['status']){
            $set[] = array('status',$setData['status']);
        }
        if(isset($setData['subject_id']) && $setData['subject_id']){
            $set[] = array('subject_id',$setData['subject_id']);
        }
        if(isset($setData['ext_info']) && $setData['ext_info']){
            $set[] = array('ext_info',$setData['ext_info']);
        }
        if(isset($setData['cover_image']) && $setData['cover_image']){
            $set[] = array('cover_image',$setData['cover_image']);
        }
        if(isset($setData['video_url']) && $setData['video_url']){
            $set[] = array('video_url',$setData['video_url']);
        }
        $set[] = array('update_time',date("Y-m-d H:i:s"));
        $data = $this->update($set, $where, $orderBy, $limit);
        return $data;
    }
    
    
    /**
     * 删除专栏辑接口(如果删除，该专栏辑下所有文章删除)
     * @params array() $userId 用户ID
     * @params array() $id   ID
     * @return array() true false
     */
    public function delAlbum($where){
        return $this->delete( $where);
    }
    
    /**
     * 插入专栏接口
     * @params array() title  title
     * @params array() user_id 用户ID
     * @params array() subject_id 蜜芽圈帖子ID
     * @params array() album_id 专栏辑ID
     * @return array() 新记录ID false
     */
    public function addAlbum($insert) {
        return $this->insert($insert);
    }
}