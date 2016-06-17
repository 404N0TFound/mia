<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Album as AlbumModel;
use \F_Ice;

class Album extends \FS_Service {

    public $abumModel = '';

    public function __construct() {
        $this->abumModel = new AlbumModel();
    }

    /**
     * 获取专栏集下的专栏文章列表
     * @params array() user_id int 用户ID
     * @params array() album_id int 专栏专辑ID
     * @params array() page int 当前页码
     * @params array() iPageSize int 每页显示多少
     * @return array() 获取专栏集下的专栏文章列表
     */
    public function getArticleList($user_id, $album_id, $page = 1, $iPageSize = 10) {
        $params = array();
        $params['user_id'] = $user_id;
        $params['album_id'] = $album_id;
        $params['page'] = $page;
        $params['iPageSize'] = $iPageSize;
        $res = $this->abumModel->getArticleList($params);
        return $this->succ($res);
    }

    /**
     * 精选专栏列表
     * @params array() page int 当前页码
     * @params array() iPageSize int 每页显示多少
     * @return array() 精选专栏列表
     */
    public function getRecommendAlbumArticleList($page = 1, $iPageSize = 10) {
        $params = array();
        $params['page'] = $page;
        $params['iPageSize'] = $iPageSize;
        $res = $this->abumModel->getRecommendAlbumArticleList($params);
        return $this->succ($res);
    }

    /**
     * 批量查文章信息列表
     * @params array() $subjectIds 文章IDs
     * @return array() 文章信息列表
     */
    public function getBatchAlbumBySubjectId($subjectIds) {
        $res = $this->abumModel->getBatchAlbumBySubjectId($subjectIds);
        return $this->succ($res);
    }
}
