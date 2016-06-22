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
     * @params array() user_id 用户ID
     * @params array() page int 当前页码
     * @params array() iPageSize int 每页显示多少
     * @return array() 专栏辑信息
     */
    public function getAlbumFile($con) {
        $res = array();
        if(!isset($con['user_id']) || empty($con['user_id'])){
            return $this->succ($res);
        }
        if(!isset($con['iPageSize']) || empty($con['iPageSize'])){
            $con['iPageSize'] = 10;
        }
        if(!isset($con['page']) || empty($con['page'])){
            $con['page'] = 1;
        }
        $params = array();
        $params['user_id'] = $con['user_id'];
        $params['iPageSize'] = $con['iPageSize'];
        $params['page'] = $con['page'];
        $res = $this->abumModel->getAlbumList($params);
        return $this->succ($res);
    }
    
    /**
     * 查专栏接口
     * @params array() album_id 专栏辑ID
     * @params array() user_id 用户ID
     * @return array() 专栏信息
     */
    public function getAlbum($con) {
        $res = array();
        if(!isset($con['user_id']) || empty($con['user_id'])){
            return $this->succ($res);
        }
        if(!isset($con['album_id']) || empty($con['album_id'])){
            return $this->succ($res);
        }
        
        if(!isset($con['iPageSize']) || empty($con['iPageSize'])){
            $con['iPageSize'] = 10;
        }
        if(!isset($con['page']) || empty($con['page'])){
            $con['page'] = 1;
        }
        $params = array();
        $params['user_id'] = $con['user_id'];
        $params['album_id'] = $con['album_id'];
        $params['iPageSize'] = $con['iPageSize'];
        $params['page'] = $con['page'];
        $res = $this->abumModel->getSimpleArticleList($params);
        return $this->succ($res);
    }
    
    /**
     * 查文章详情接口
     * @params array() articleId 文章ID
     * @params array() user_id 用户ID
     * @return array() 文章详情
     */
    public function getAlbumArticle($con) {
        $res = array();
        if(!isset($con['user_id']) || empty($con['user_id'])){
            return $this->succ($res);
        }
        if(!isset($con['articleId']) || empty($con['articleId'])){
            return $this->succ($res);
        }

        $params = array();
        $params['user_id'] = array($con['user_id']);
        $params['articleId'] = array($con['articleId']);
        $res = $this->abumModel->getArticleInfo($params);
        return $this->succ($res);
    }
    
    
    /**
     * 更新专栏辑接口
     * array('user_id'=>'1508587','id'=>3),array('title'=>'我是标题党')
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
     * 更新专栏接口(这里只有title可以改)
     * $con = array('user_id'=>'1145319','id'=>6,'album_id'=>10)
     * $set = array('title'=>'我是标题党')
     * @params array() $album_id 专栏辑ID
     * @params array() $id 专栏ID
     * @params array() $userId 用户ID
     * @return array() 
     */
    public function updateAlbum($con,$set) {
        $res = array();
        if(empty($con) || empty($set)){
            return $this->succ($res);
        }

        $params = array();
        $params['user_id'] = $con['user_id'];
        $params['album_id'] = $con['album_id'];
        $params['id'] = $con['id'];
        
        $data = array();
        $data['title'] = $set['title'];
        $res = $this->abumModel->updateAlbum($params,$data);
        return $this->succ($res);
    }
    
    
    /**
     * 更新文章详情接口
     * $con = array('user_id'=>'1145319','id'=>6,'album_id'=>10)
     * $set = array('content'=>'我是标题党')
     * @params array() $album_id 专栏辑ID
     * @params array() $id 专栏ID
     * @params array() $userId 用户ID
     * @return array() 
     */
    public function updateAlbumArticle($con,$set) {
        $res = array();
        if(empty($con) || empty($set)){
            return $this->succ($res);
        }

        $params = array();
        $params['user_id'] = $con['user_id'];
        $params['album_id'] = $con['album_id'];
        $params['id'] = $con['id'];
        
        $data = array();
        $data['content'] = strip_tags($set['content']);     //过滤标签后台的文章内容
        $data['content_original'] = $set['content'];   //原始文章内容
        
        $res = $this->abumModel->updateAlbumArticle($params,$data);
        return $this->succ($res);
    }
    
    
    /**
     * 删除专栏辑接口(如果删除，该专栏辑下所有文章删除)
     * @params array() $userId 用户ID
     * @params array() $id   ID
     * @return array() true false
     */
    public function delAlbumFile($con) {
        $res = array();
        if(empty($con)){
            return $this->succ($res);
        }

        $params = array();
        $params['user_id'] = $con['user_id'];
        $params['id'] = $con['id'];
        
        $res = $this->abumModel->delAlbumFile($params);
        return $this->succ($res);
    }
    
    /**
     * 删除专栏接口
     * @params array() $id  ID
     * @params array() $userId 用户ID
     * @return array() true false
     */
    public function delAlbum($con) {
        $res = array();
        if(empty($con)){
            return $this->succ($res);
        }

        $params = array();
        $params['user_id'] = $con['user_id'];
        $params['id'] = $con['id'];
        
        $res = $this->abumModel->delAlbum($params);
        return $this->succ($res);
    }
    
    /**
     * 插入专栏辑接口
     * @params array() title  title
     * @params array() user_id 用户ID
     * @return array() 新记录ID
     */
    public function addAlbumFile($insert) {
        $res = array();
        if(empty($insert)){
            return $this->succ($res);
        }

        $params = array();
        $params['user_id'] = $insert['user_id'];
        $params['title'] = $insert['title'];
        $res = $this->abumModel->addAlbumFile($params);
        return $this->succ($res);
    }
    
    /**
     * 插入专栏接口
     * @params array() title  title
     * @params array() user_id 用户ID
     * @params array() album_id 专栏辑ID
     * @return array() 新记录ID false
     */
    public function addAlbum($insert) {
        $res = array();
        if(empty($insert) || empty($insert['title']) || empty($insert['user_id']) || empty($insert['album_id'])){
            return $this->succ($res);
        }

        $params = array();
        $params['user_id'] = $insert['user_id'];
        $params['title'] = strip_tags($insert['title']);
        $params['album_id'] = $insert['album_id'];
        $res = $this->abumModel->addAlbum($params);
        return $this->succ($res);
    }
    
    /**
     * 文章预览接口
     * @params array() user_id 用户ID
     * @params array() album_id 专栏辑ID
     * @params array() article_id 文章ID
     * @return array() 文章内容
     */
    public function getArticlePreview($con) {
       $res = array();
        if(empty($con) || empty($con['album_id']) || empty($con['user_id']) || empty($con['article_id'])){
            return $this->succ($res);
        }

        $params = array();
        $params['user_id'] = $con['user_id'];
        $params['album_id'] = $con['album_id'];
        $params['id'] = $con['article_id'];
        $res = $this->abumModel->getArticlePreview($params);
        return $this->succ($res);
    }
    
    /**
     * 获取标签接口
     * @params array() 
     * @return array() 标签
     */
    public function getLabels() {
        $labelService = new \mia\miagroup\Service\Label();
        $labelIDs = $labelService->getLabelID()['data'];
        $labelInfos = $labelService->getBatchLabelInfos($labelIDs);
        return $this->succ($labelInfos['data']);
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
