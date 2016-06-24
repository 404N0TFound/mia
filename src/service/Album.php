<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Album as AlbumModel;
use mia\miagroup\Util\QiniuUtil;
use mia\miagroup\Service\User as UserService;
use \F_Ice;

class Album extends \FS_Service {

    public $abumModel = '';
    public $userService = '';

    public function __construct() {
        $this->abumModel = new AlbumModel();
        $this->userService = new UserService();
    }
    
    
    /**
     * 获取专栏集下的专栏文章列表
     * @params array() user_id int 用户ID
     * @params array() album_id int 专栏专辑ID
     * @params array() page int 当前页码
     * @params array() iPageSize int 每页显示多少
     * @return array() 获取专栏集下的专栏文章列表
     */
    public function getArticleList($con) {
        
        $params = array();
        $params['user_id'] = $con['user_id'];
        $params['album_id'] = $con['album_id'];
        $params['page'] = isset($con['page'])?(int)$con['page']:1;
        $params['iPageSize'] = isset($con['iPageSize'])?(int)$con['iPageSize']:10;
        
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
        if ($params['page'] == 1) {
            $albumResult = $this->abumModel->getAlbumList(array('user_id'=>$user_id));
            $response['album_list'] = array_values($albumResult);
        }
        return $this->succ($response);
    }

    /**
     * 精选专栏列表
     * @params array() page int 当前页码
     * @params array() iPageSize int 每页显示多少
     * @return array() 精选专栏列表
     */
    public function getRecommendAlbumArticleList($con) {
        $params = array();
        $params['page'] = isset($con['page'])?(int)$con['page']:1;
        $params['iPageSize'] = isset($con['iPageSize'])?(int)$con['iPageSize']:10;
        
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
            return $this->error('500','param user_id is empty');
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
            return $this->error('500','param user_id is empty');
        }
        if(!isset($con['album_id']) || empty($con['album_id'])){
            return $this->error('500','param album_id is empty');
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
            return $this->error('500','param user_id is empty');
        }
        if(!isset($con['articleId']) || empty($con['articleId'])){
            return $this->error('500','param articleId is empty');
        }

        $params = array();
        $params['user_id'] = array($con['user_id']);
        $params['articleId'] = array($con['articleId']);
        $res = $this->abumModel->getArticleInfo($params);
        return $this->succ($res[$con['articleId']]);
    }
    
    
    /**
     * 更新专栏辑接口
     * array('user_id'=>'1508587','id'=>3),array('title'=>'我是标题党')
     * @params array() user_id 用户ID
     * @set    array() title 标题
     * @return array() 专栏辑信息
     */
    public function updateAlbumFile($data) {
        $res = array();
        if(empty($data['con']) || empty($data['set'])){
            return $this->error('500','param condition and set is empty');
        }
        $con = $data['con'];
        $set = $data['set'];
       
        $userPermission = $this->abumModel->getAlbumPermissionByUserId( $con['user_id'] );
        if(!$userPermission){
            return $this->error('500','Function:'.__FUNCTION__.' user do not have permission');
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
    public function updateAlbum($data) {
        $res = array();
        if(empty($data['con']) || empty($data['set'])){
            return $this->error('500','param condition and set is empty');
        }
        $con = $data['con'];
        $set = $data['set'];
        
        $userPermission = $this->abumModel->getAlbumPermissionByUserId( $con['user_id'] );
        if(!$userPermission){
            return $this->error('500','Function:'.__FUNCTION__.' user do not have permission');
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
    public function updateAlbumArticle($data) {
        $res = array();
        if(empty($data['con']) || empty($data['set'])){
            return $this->error('500','param condition and set is empty');
        }
        $con = $data['con'];
        $set = $data['set'];
        
        $userPermission = $this->abumModel->getAlbumPermissionByUserId( $con['user_id'] );
        if(!$userPermission){
            return $this->error('500','Function:'.__FUNCTION__.' user do not have permission');
        }
        $params = array();
        $params['user_id'] = $con['user_id'];
        $params['album_id'] = $con['album_id'];
        $params['id'] = $con['id'];
        
        $data = array();
        $data['content'] = strip_tags($set['content']);     //过滤标签后台的文章内容
        $data['content_original'] = $set['content'];   //原始文章内容
        
        if(isset($set['labels']) && !empty($set['labels'])){
            $labelInfos = array();
            foreach($set['labels'] as $key => $value){
                $labelInfos[$key]['id'] = $value['id'];
                $labelInfos[$key]['title'] = $value['title'];
            }
            $data['ext_info'] = json_encode(array('label'=>$labelInfos));
        }
        if(isset($set['image_infos']) && !empty($set['image_infos'])){
            $data['cover_image'] = json_encode(
                array(
                    'width'=>$set['image_infos']['width'],
                    'height'=>$set['image_infos']['height'],
                    'url'=>$set['image_infos']['url'],
                    'content'=>''
                ));
        }
        
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
        $userPermission = $this->abumModel->getAlbumPermissionByUserId($con['user_id']);
        if(!$userPermission){
            return $this->error('500','Function:'.__FUNCTION__.' user do not have permission');
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
            return $this->error('500','param condition is empty');
        }
        
        $userPermission = $this->abumModel->getAlbumPermissionByUserId($con['user_id']);
        if(!$userPermission){
            return $this->error('500','Function:'.__FUNCTION__.' user do not have permission');
        }
        $params = array();
        $params[] =array(':eq','user_id',$con['user_id']) ;
        $params[] =array(':eq','id',$con['id']) ;
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
            return $this->error('500','param insert is empty');
        }
        
        $userPermission = $this->abumModel->getAlbumPermissionByUserId($insert['user_id']);
        if(!$userPermission){
            return $this->error('500','Function:'.__FUNCTION__.' user do not have permission');
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
            return $this->error('500','param insert is empty');
        }
        $userPermission = $this->abumModel->getAlbumPermissionByUserId($insert['user_id']);
        if(!$userPermission){
            return $this->error('500','Function:'.__FUNCTION__.' user do not have permission');
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
            return $this->error('500','param condition is empty');
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
     * 发布接口
     * @params array() 
     * @return array() 标签
     */
    public function pcIssue($params) {
        $res = array();
        foreach($params as $key => $value){
            if(!in_array($key, array('labels'))){
                if(empty($params[$key])){
                    return $this->error('500','params is empty');
                }
            }
        }
        $subjectInfo = array();
        $subjectInfo['title'] = $params['title'];
        $subjectInfo['text'] = $params['text'];
        $subjectInfo['image_infos'] = array(
            'height' => $params['image_infos']['height'], 
            'url' => $params['image_infos']['url'], 
            'width' => $params['image_infos']['width']
        );
        $user_info = $this->userService->getUserInfoByUserId($params['user_id'])['data'];
        if(!$user_info){
            return $this->error('500','user_info is null');
        }
        $subjectInfo['user_info'] = $user_info;
        if(isset($params['active_id'])){
            $subjectInfo['active_id'] = $params['active_id'];
        }
        
        $labelInfos = array();
        if(isset($params['labels']) &&  $params['labels']){
            foreach($params['labels'] as $key => $value){
                $labelInfos[$key]['id'] = $value['id'];
                $labelInfos[$key]['title'] = $value['title'];
            }
        }
        
        $subjectService = new \mia\miagroup\Service\Subject();
        $subjectRes = $subjectService->issue($subjectInfo, array(), $labelInfos, 0)['data'];
        
        if(isset($subjectRes['id'])){
            $paramsArticle = array();
            $paramsArticle['user_id'] = $params['user_id'];
            $paramsArticle['album_id'] = $params['album_id'];
            $paramsArticle['id'] = $params['article_id'];

            $setArticle = array();
            $setArticle['subject_id'] = $subjectRes['id'];
            $setArticle['content'] = strip_tags($params['text']);
            $setArticle['content_original'] = $params['text'];
            $setArticle['status'] = 1;
            $setArticle['ext_info'] = json_encode(array('label'=>$labelInfos));
            $setArticle['cover_image'] = json_encode(
                    array(
                        'width'=>$params['image_infos']['width'],
                        'height'=>$params['image_infos']['height'],
                        'url'=>$params['image_infos']['url'],
                        'content'=>''
                    ));
            $res = $this->abumModel->updateAlbumArticle($paramsArticle,$setArticle);
            return $this->succ($res);
        }
        return $this->succ($res);
    }
    
    /**
     * PC端获取上传token和key
     * @params array() $filePath 随便传点东西
     * @return array() token和key
     */
    public function getUploadTokenAndKey($filePath) {
        $res = array();
        if(empty($filePath)){
            return $this->error('500','param is empty');
        }
        $qiNiuSDK = new QiniuUtil();
        $res = $qiNiuSDK -> getUploadTokenAndKey($filePath);
        return $this->succ($res);
    }
}
