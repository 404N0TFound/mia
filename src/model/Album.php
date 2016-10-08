<?php
namespace mia\miagroup\Model;
use \F_Ice;
use mia\miagroup\Data\Album\AlbumArticle as AlbumArticleData;
use mia\miagroup\Data\Album\Album as AlbumData;
use mia\miagroup\Data\Album\AlbumPermission as AlbumPermissionData;
use mia\miagroup\Data\User\GroupDoozer as GroupDoozer;

class Album {

    public $albumArticleData = '';
    public $albumData = '';
    public $userGroupDoozerData = '';
    public $albumPermissionData = '';
    

    public function __construct() {
        $this->albumArticleData = new AlbumArticleData();
        $this->albumData = new AlbumData();
        $this->userGroupDoozerData = new GroupDoozer();
        $this->albumPermissionData = new AlbumPermissionData();
    }

    /**
     * 批量查文章信息列表
     * @params array() $subjectIds 蜜呀圈帖子ID
     * @return array() 文章ID列表
     */
    public function getBatchAlbumBySubjectId($subjectIds) {
        $articleIds = $this->albumArticleData->getBatchAlbumBySubjectId($subjectIds);
        $articles = $this->getBatchArticle($articleIds);
        $subjectArticle = array();
        foreach ($articleIds as $subjectId => $articleId) {
            if (isset($articles[$articleId])) {
                 if(empty($articles[$articleId]['cover_image'])){
                    unset($articles[$articleId]['cover_image']);
                 }
                $subjectArticle[$subjectId] = $articles[$articleId];
            }
        }
        return $subjectArticle;
    }

    /**
     * 批量查文章信息列表
     * @params array() $articleIds 文章IDs
     * @return array() 文章信息列表
     */
    public function getBatchArticle($articleIds) {
        $res = $this->albumArticleData->getBatchArticle($articleIds);
        if($res){
            $AlbumInfo = array();
            $albumIdArr = array_column($res,'album_id');
            $AlbumInfo = $this->albumData->getAlbumInfo($albumIdArr);
            foreach ($res as $key => $value) {
                $img_pic = array();
                if(isset($value['cover_image']) && $value['cover_image']){
                    $img_pic = json_decode($value['cover_image'],true);
                }
                if($img_pic){
                    $res[$key]['cover_image'] = array();
                    if (strpos($img_pic['url'], 'http') === 0) {
                        $res[$key]['cover_image']['url'] = $img_pic['url'];
                    } else if($img_pic['source'] == 'local') {
                        $res[$key]['cover_image']['url'] = F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $img_pic['url'];
                    } else {
                        $res[$key]['cover_image']['url'] = F_Ice::$ins->workApp->config->get('app')['url']['qiniu_url'] . $img_pic['url'];
                    }
                    $res[$key]['cover_image']['width'] = $img_pic['width'];
                    $res[$key]['cover_image']['height'] = $img_pic['height'];
                    $res[$key]['cover_image']['content'] = $img_pic['content'];
                }
                $res[$key]['album_info'] = array();
                if (!empty($value['ext_info']['images'])) {
                    foreach ($value['ext_info']['images'] as $image) {
                        if (strpos($image['url'], 'http') === 0) {
                            $image['url'] = $image['url'];
                        } else if ($image['source'] == 'local') {
                            $image['url'] = F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $image['url'];
                        } else {
                            $image['url'] = F_Ice::$ins->workApp->config->get('app')['url']['qiniu_url'] . $image['url'];
                        }
                        $res[$key]['images'][] = $image;
                    }
                }
                if($AlbumInfo){
                    $res[$key]['album_info']['id'] = $AlbumInfo[$value['album_id']]['id'];
                    $res[$key]['album_info']['user_id'] = $AlbumInfo[$value['album_id']]['user_id'];
                    $res[$key]['album_info']['title'] = $AlbumInfo[$value['album_id']]['title'];
                }
                $res[$key]['h5_url'] = sprintf(\F_Ice::$ins->workApp->config->get('busconf.subject')['album']['h5_url'],$value['id'],$value['album_id']);;//待续
                $res[$key]['view_num'] = $this->readNum($value['create_time']);
                $res[$key]['content'] = strip_tags(mb_substr($value['content'],0,50,'utf-8')).'....';
                unset($res[$key]['create_time']);
            }
        }
            
        
        return $res;
    }
    
    //阅读量 大约48小时  8K多
    public function readNum($date){
        $system = 20;
        $diference = time()-strtotime($date);
        if($diference < 2000){
            $system = 3;
        }
        $num = bcdiv($diference,$system);
        if($num > 10000){
            $num = '10000+';
        }
        return $num;
    }

    /**
     * 获取专栏集下的专栏文章ID列表
     * @params array() user_id int 用户ID
     * @params array() album_id int 专栏专辑ID
     * @params array() page int 当前页码
     * @params array() iPageSize int 每页显示多少
     * @return array() 获取专栏集下的专栏文章ID列表
     */
    public function getArticleList($params) {
        return $this->albumArticleData->getArticle($params);
    }
    
    /**
     * 批量查文章简版内容（主要用于展示专栏）
     * @params array() user_id int 用户ID
     * @params array() album_id int 专栏专辑ID
     * @params array() page int 当前页码
     * @params array() iPageSize int 每页显示多少
     * @return array() 文章简版内容（主要用于展示专栏）
     */
    public function getSimpleArticleList($params) {
        $articleList = array();
        $SimpleArticleList = $this->albumArticleData->getSimpleArticleList($params);
        if($SimpleArticleList){
            foreach($SimpleArticleList as $value){
                $value['content'] = mb_substr($value['content'],0,50,'utf-8').'....';
                $value['cover_image'] = json_decode($value['cover_image'],true);
                $articleList[str_replace('-','年',substr(($value['create_time']),0,7)).'月'][] = $value;
            }
        }
        return $articleList;
    }
    /**
     * 搜索专栏（主要用于展示专栏）
     * @params array() user_id int 用户ID
     * @params array() album_id int 专栏专辑ID
     * @params array() search string 搜索内容
     * @params array() page int 当前页码
     * @params array() iPageSize int 每页显示多少
     * @return array() 文章简版内容（主要用于展示专栏）
     */
    public function getSearchSimpleArticleList($params) {
        $articleList = array();
        $SimpleArticleList = $this->albumArticleData->getSimpleArticleList($params);
        if($SimpleArticleList){
            foreach($SimpleArticleList as &$value){
                $value['content'] = mb_substr($value['content'],0,50,'utf-8').'....';
                $value['cover_image'] = json_decode($value['cover_image'],true);
            }
        }
        return $SimpleArticleList;
    }

    /**
     * 专辑列表
     * @params array() user_id 用户ID
     * @return array() 专辑列表
     */
    public function getAlbumList($params){
        return $this->albumData->getAlbumList($params);
    }

    /**
     * 查标精文章列表
     * @params array() 
     * @return array() 标精文章列表
     */
    public function getRecommendAlbumArticleList($params) {
        return $this->albumArticleData->getRecommendAlbumArticleList($params);
    }
    
    /**
     * 查推荐用户列表
     * @params array() 
     * @return array() 推荐用户列表
     */
    public function getGroupDoozerList()
    {
        return $this->userGroupDoozerData->getGroupDoozerList();
    }
    
    
    /**
     * 查用户下专栏数
     * @params array() $userIds 用户ID
     * @return array() 用户专栏个数
     */
    public function getAlbumNum($userIds)
    {
        return $this->albumData->getAlbumNum($userIds);
    }
    
    /**
     * 查用户下文章数
     * @params array() $userIds 用户ID
     * @return array() 用户文章个数
     */
    public function getArticleNum($userIds)
    {
        return $this->albumArticleData->getArticleNum($userIds);
    }
    
    /**
     * 查文章详情接口
     * @params array() $article_id 文章ID
     * @params array() $userId 用户ID
     * @return array() 文章详情
     */
    public function getArticleInfo($param) {
        return $this->albumArticleData->getArticleInfo($param);
    }
    
    /**
     * 文章预览接口
     * @params array() user_id 用户ID
     * @params array() album_id 专栏辑ID
     * @params array() article_id 文章ID
     * @return array() 文章内容
     */
    public function getArticlePreview($params) {
        return $this->albumArticleData->getArticlePreview($params);
    }
    
    
    /**
     * 更新专栏辑接口
     * @params array() user_id 用户ID
     * @set    array() title 标题
     * @return array() 专栏辑信息
     */
    public function updateAlbumFile($where,$set) {
        return $this->albumData->updateAlbumFile($where,$set);
    }
    
    /**
     * 更新专栏接口(这里只有title可以改)
     * @params array() $album_id 专栏辑ID
     * @params array() $id 专栏ID
     * @params array() $userId 用户ID
     * @return array() 专栏信息
     */
    public function updateAlbum($where,$set){
        return $this->albumArticleData->updateAlbum($where,$set);
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
    public function updateAlbumArticle($where,$set){
        return $this->albumArticleData->updateAlbumArticle($where,$set);
    }
    
    /**
     * 删除专栏辑接口(如果删除，该专栏辑下所有文章删除)
     * @params array() $userId 用户ID
     * @params array() $id   ID
     * @return array() true false
     */
    public function delAlbumFile($where){
        $params = array();
        $params[] =array(':eq','user_id',$where['user_id']) ;
        $params[] =array(':eq','id',$where['id']) ;
        $res = $this->albumData->delAlbumFile($params);
        if($res){
            $con = array();
            $con[] =array(':eq','user_id',$where['user_id']) ;
            $con[] =array(':eq','album_id',$where['id']) ;
            $delRes = $this->delAlbum($con);
            if($delRes){
                return true;
            }
        }
        return false;
    }
    
    /**
     * 删除专栏
     * @params array() $userId 用户ID
     * @params array() $id   ID
     * @return array() true false
     */
    public function delAlbum($where){
        return $this->albumArticleData->delAlbum($where);
    }
    
    /**
     * 帖子id
     * @params array() user_id int 用户ID
     * @params array() album_id int 专栏专辑ID
     * @params array() id int 文章IDs
     * @return array() 帖子id列表
     */
    public function getSubjectId($params) {
        return $this->albumArticleData->getSubjectId($params);
    }
    
    /**
     * 插入专栏辑接口
     * @params array() title  title
     * @params array() user_id 用户ID
     * @return array() true false
     */
    public function addAlbumFile($insert){
        $albumFile = $this->albumData->getAlbumFileByUidAndTitle($insert['user_id'], $insert['title']);
        if (!empty($albumFile)) {
            return $albumFile['id'];
        }
        $albumFileId = $this->albumData->addAlbumFile($insert);
        return $albumFileId;
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
        $AlbumInfo = $this->albumData->getAlbumInfo(array($insert['album_id']));
        if(!$AlbumInfo || $AlbumInfo[$insert['album_id']]['user_id'] != $insert['user_id']){
            return false;
        }
        $data = array(
            'title'=>$insert['title'],
            'subject_id'=>$insert['subject_id'],
            'user_id'=>$insert['user_id'],
            'album_id'=>$insert['album_id'],
            'title'=>$insert['title'],
            'cover_image'=>$insert['cover_image'],
            'content'=>$insert['content'],
            'content_original'=>$insert['content_original'],
            'ext_info'=>json_encode($insert['ext_info']),
            'status'=>$insert['status'],
            'create_time'=>$insert['create_time'] ? $insert['create_time'] : date("Y-m-d H:i:s")
        );
        $res = $this->albumArticleData->addAlbum($data);
        return $res;
    }
    
    /**
     * 查用户编辑文章权限
     * @params array() $userId 用户ID
     * @return array() id
     */
    public function getAlbumPermissionByUserId($user_id){
        return $this->albumPermissionData->getAlbumPermissionByUserId($user_id);
    }
    
    /**
     * 添加用户专栏权限
     */
    public function addAlbumPermission($userId, $source = 'ums', $reason = '', $operator = 0) {
        $data = $this->getAlbumPermissionByUserId($userId);
        if (!empty($data)) {
            return $data['id'];
        }
        $data = $this->albumPermissionData->addAlbumPermission($userId, $source, $reason, $operator);
        return $data;
    }
}
