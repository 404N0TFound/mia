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
        $result = array();
        
        $where = array();
        $where[] = array(':eq', 'status', '1');
        if (is_array($subjectIds)) {
            $where[] = array(':in', 'subject_id', $subjectIds);
        } else {
            $where[] = array(':eq', 'subject_id', $subjectIds);
        }
        
        $orderBy = array('create_time DESC');
        $experts = $this->getRows($where, array('id'), $limit = FALSE, $offset = 0, $orderBy);
        return $experts;
    }

    /**
     * 批量查文章内容
     * @params array() $articleIds 文章IDs
     * @return array() 文章信息列表
     */
    public function getBatchArticle($articleIds) {
        $result = array();
        $where = array();
        $where[] = array(':eq', 'status', '1');
        if (is_array($articleIds)) {
            $where[] = array(':in', 'id', $articleIds);
        } else {
            $where[] = array(':eq', 'id', $subjectIds);
        }
        $orderBy = array('create_time DESC');
        $experts = $this->getRows($where, array('id,album_id,user_id,subject_id,title,cover_image,content,is_recommend,h5_url'), $limit = FALSE, $offset = 0, $orderBy);
        return $experts;
    }

    /**
     * 批量查文章内容
     * @params array() $articleIds 文章IDs
     * @return array() 文章信息列表
     */
    public function getArticle($params) {
        $result = array();
        $limit = 10;
        $offset = 0;
        $where = array();
        
        $where[] = array(':eq', 'status', '1');
        $where[] = array(':in', 'user_id', $params['user_id']);
        $where[] = array(':eq', 'album_id', $params['album_id']);
        if (intval($params['iPageSize']) > 0) {
            $offset = ($params['page'] - 1) > 0 ? (($params['page'] - 1) * $params['iPageSize']) : 0;
            $limit = $params['iPageSize'];
        }
        $orderBy = array('create_time DESC');
        $experts = $this->getRows($where, array('subject_id'), $limit, $offset, $orderBy);
        return $experts;
    }

    /**
     * 精选文章列表
     * @return array() 精选文章列表
     */
    public function getRecommendAlbumArticleList($params) {
        $result = array();
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
        $experts = $this->getRows($where, array('subject_id'), $limit, $offset, $orderBy);
        return $experts;
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
