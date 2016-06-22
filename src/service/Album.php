<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Album as AlbumModel;
use mia\miagroup\Util\QiniuUtil;
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
        $params['page'] = (int)$page;
        $params['iPageSize'] = (int)$iPageSize;
        
        $response = array();
        $response['article_list'] = array();
        $response['album_list'] = array();
        $articleIDList = $this->abumModel->getArticleList($params);
        if ($articleIDList) {
            $articleSubject = new \mia\miagroup\Service\Subject();
            $articleResult = $articleSubject->getBatchSubjectInfos($articleIDList, $params['user_id']);
            if( isset($articleResult['data']) && $articleResult['data']){
                $response['article_list'] = array_values($articleResult['data']);
            }
        }
        
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
        $params = array();
        $params['page'] = (int)$page;
        $params['iPageSize'] = (int)$iPageSize;
        
        $response = array();
        $response['article_list'] = array();
        $response['users'] = array();
        $articleIDList = $this->abumModel->getRecommendAlbumArticleList($params);
        if ($articleIDList ) {
            $articleSubjectIdList = new \mia\miagroup\Service\Subject();
            $articleResult = $articleSubjectIdList->getBatchSubjectInfos($articleIDList); 
            if( isset($articleResult['data']) && $articleResult['data']){
                $response['article_list'] = array_values($articleResult['data']);
            }
        }
        
        $userIdRes = $this->abumModel->getGroupDoozerList();
        $User = new \mia\miagroup\Service\User();
        $userInfo = $User->getUserInfoByUids($userIdRes);
        if( isset($userInfo['data']) && $userInfo['data']){
            $response['users'] = array_values($userInfo['data']);
        }
        
        return $this->succ($response);
    }

    /**
     * 批量查文章信息列表
     * @params array() $subjectIds 蜜呀圈帖子ID
     * @return array() 文章信息列表
     */
    public function getBatchAlbumBySubjectId($subjectIds) {
        if (empty($subjectIds)) {
            return $this->succ(array());
        }
        $res = $this->abumModel->getBatchAlbumBySubjectId($subjectIds);
        return $this->succ($res);
    }
    
    /**
     * 查用户下专栏数
     * @params array() $userIds 用户ID
     * @return array() 用户专栏个数
     */
    public function getAlbumNum($userIds) {
        if (empty($userIds)) {
            return $this->succ(array()); 
        }
        $res = $this->abumModel->getAlbumNum($userIds);
        return $this->succ($res);
    }
    
    
    /*
     * ---------------------------------下面是PC接口----------------------------------------------
     */
    
    /**
     * 查专栏辑接口
     * @params array() $userId 用户ID
     * @params array() page int 当前页码
     * @params array() iPageSize int 每页显示多少
     * @return array() 专栏辑信息
     */
    public function getAlbumFile($userId,$iPageSize=10,$page=1) {
        $res = array();
        if(empty($userId)){
            return $this->succ($res);
        }
        $params = array();
        $params['user_id'] = $userId;
        $params['iPageSize'] = (int)$iPageSize;
        $params['page'] = (int)$page;
        $res = $this->abumModel->getAlbumList($params);
        return $this->succ($res);
    }
    
    /**
     * 查专栏接口
     * @params array() $album_id 专栏辑ID
     * @params array() $userId 用户ID
     * @return array() 专栏信息
     */
    public function getAlbum($userId,$albumId,$iPageSize=10,$page=1) {
        $res = array();
        if(empty($userId) || empty($albumId)){
            return $this->succ($res);
        }

        $params = array();
        $params['user_id'] = $userId;
        $params['album_id'] = $albumId;
        $params['iPageSize'] = (int)$iPageSize;
        $params['page'] = (int)$page;
        $res = $this->abumModel->getSimpleArticleList($params);
        return $this->succ($res);
    }
    
    /**
     * 查文章详情接口
     * @params array() $article_id 文章ID
     * @params array() $userId 用户ID
     * @return array() 文章详情
     */
    public function getAlbumArticle($userId,$articleId) {
        $res = array();
        if(empty($userId) || empty($articleId)){
            return $this->succ($res);
        }

        $params = array();
        $params['user_id'] = array($userId);
        $params['articleId'] = array($articleId);
        $res = $this->abumModel->getArticleInfo($params);
        return $this->succ($res);
    }
    
    
    /**
     * 更新专栏辑接口
     * @params array() user_id 用户ID
     * @set    array() title 标题
     * @return array() 专栏辑信息
     */
    public function updateAlbumFile($con,$set) {
        $res = array();
        if(empty($con) || empty($set)){
            return $this->succ($res);
        }

        $params = array();
        $params['user_id'] = $con['user_id'];
        $params['id'] = $con['id'];
        
        $data = array();
        $data['title'] = $set['title'];
        $res = $this->abumModel->updateAlbumFile($params,$data);
        return $this->succ($res);
    }
    
    
    /**
     * 更新专栏接口
     * @params array() $album_id 专栏辑ID
     * @params array() $userId 用户ID
     * @return array() 专栏信息
     */
    public function updateAlbum($album_id,$userId) {
        
    }
    
    
    /**
     * 更新文章详情接口
     * @params array() $article_id 文章ID
     * @params array() $userId 用户ID
     * @return array() 文章详情
     */
    public function updateAlbumArticle($article_id,$userId) {
        
    }
    
    
    
    
    /**
     * 删除专栏辑接口(如果删除，该专栏辑下所有文章删除)
     * @params array() $userId 用户ID
     * @params array() $id   ID
     * @return array() 专栏辑信息
     */
    public function delAlbumFile($userId) {
        
    }
    
    /**
     * 删除专栏接口
     * @params array() $id  ID
     * @params array() $userId 用户ID
     * @return array() 专栏信息
     */
    public function delAlbum($id,$userId) {
        
    }
    
    
    /**
     * 删除文章详情接口
     * @params array() $id 文章ID
     * @params array() $userId 用户ID
     * @return array() 文章详情
     */
    public function delAlbumArticle($id,$userId) {
        
    }
    
    /**
     * PC端获取上传token和key
     * @params array() $filePath 上传目录
     * @return array() 文章详情
     */
    public function getUploadTokenAndKey($filePath) {
        $res = array();
        if(empty($filePath)){
            return $this->succ($res);
        }
        $qiNiuSDK = new QiniuUtil();
        $res = $qiNiuSDK -> getUploadTokenAndKey($filePath);
        return $this->succ($res);
    }
}
