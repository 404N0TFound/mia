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
        if (!(int)$user_id || !(int)$album_id) {
            return $this->error(10008,array('参数错误'));
        }
        
        $params = array();
        $params['user_id'] = $user_id;
        $params['album_id'] = (int)$album_id;
        $params['page'] = (int)$page;
        $params['iPageSize'] = (int)$iPageSize;
        $articleIDList = $this->abumModel->getArticleList($params);
        
        if (empty($articleIDList) || !is_array($articleIDList)) {
            return $this->error(10006,array('没有专栏'));
        }
        $articleSubject = new \mia\miagroup\Service\Subject();
        $articleResult = $articleSubject->getBatchSubjectInfos($articleIDList, $params['user_id']);
        if( !isset($articleResult['data']) || empty($articleResult['data'])){
            return $this->error(10007,array('专辑下专栏格式生成失败')) ;
        }
        $response['article_list'] = $articleResult['data'];
        
        //第一页 返回专辑列表信息
        if ($page == 1) {
            $albumResult = $this->abumModel->getAlbumList(array('user_id'=>$user_id));
            $response['album_list'] = $albumResult;
        }
        return $this->succ($response);
    }

    /**
     * 精选专栏列表
     * @params array() page int 当前页码
     * @params array() iPageSize int 每页显示多少
     * @return array() 精选专栏列表
     */
    public function getRecommendAlbumArticleList($page = 1, $iPageSize = 10) {
        $response = array();
        $params = array();
        $params['page'] = (int)$page;
        $params['iPageSize'] = (int)$iPageSize;
        $articleIDList = $this->abumModel->getRecommendAlbumArticleList($params);
        if (empty($articleIDList) || !is_array($articleIDList)) {
            return $this->error(10001,array('没有精选文章'));
        }
        
        $articleSubjectIdList = new \mia\miagroup\Service\Subject();
        $articleResult = $articleSubjectIdList->getBatchSubjectInfos($articleIDList); 
        
        if( !isset($articleResult['data']) || empty($articleResult['data'])){
            return $this->error(10002,array('文章格式生成失败')) ;
        }
        $response['article_list'] = $articleResult['data'];
        
        $userIdRes = $this->abumModel->getGroupDoozerList();
        $userIdArr = array();
        if ($userIdRes) {
            foreach ($userIdRes as $value) {
                $userIdArr[] = $value['user_id'];
            }
        }
        $User = new \mia\miagroup\Service\User();
        $userInfo = $User->getUserInfoByUids($userIdArr);
        if( !isset($userInfo['data']) || empty($userInfo['data'])){
            return $this->error(10005,array('用户信息拉取失败')) ;
        }
        $response['users'] = $userInfo['data'];
        return $this->succ($response);
    }

    /**
     * 批量查文章信息列表
     * @params array() $subjectIds 蜜呀圈帖子ID
     * @return array() 文章信息列表
     */
    public function getBatchAlbumBySubjectId($subjectIds) {
        if (empty($subjectIds) || !is_array($subjectIds)) {
            return $this->error(10003,array('参数有误'));
        }
        $res = $this->abumModel->getBatchAlbumBySubjectId($subjectIds);
        if(empty($res)){
            return $this->error(10004,array('文章列表没有'));
        }
        return $this->succ($res);
    }
}
