<?php
namespace mia\miagroup\Model;
use \F_Ice;
use mia\miagroup\Data\Album\AlbumArticle as AlbumArticleData;
use mia\miagroup\Data\Album\Album as AlbumData;
use mia\miagroup\Data\User\GroupDoozer as GroupDoozer;

class Album {

    public $albumArticleData = '';
    public $albumData = '';
    public $userGroupDoozerData = '';

    public function __construct() {
        $this->albumArticleData = new AlbumArticleData();
        $this->albumData = new AlbumData();
        $this->userGroupDoozerData = new GroupDoozer();
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
        foreach ($res as $key => $value) {
            $img_pic = array();
            if(isset($value['cover_image']) && $value['cover_image']){
                $img_pic = json_decode($value['cover_image'],true);
            }
            if($img_pic){
                $res[$key]['cover_image'] = array();
                $res[$key]['cover_image']['url'] = F_Ice::$ins->workApp->config->get('app')['url']['img_url'] .$img_pic['url'];
                $res[$key]['cover_image']['width'] = $img_pic['width'];
                $res[$key]['cover_image']['height'] = $img_pic['height'];
                $res[$key]['cover_image']['content'] = $img_pic['content'];
            }
            $res[$key]['album_info'] = array();
            $AlbumInfo = $this->albumData->getAlbumInfo(array('album_id'=>$value['album_id']));
            if($AlbumInfo){
                $res[$key]['album_info']['id'] = $AlbumInfo['id'];
                $res[$key]['album_info']['user_id'] = $AlbumInfo['user_id'];
                $res[$key]['album_info']['title'] = $AlbumInfo['title'];
            }
            $res[$key]['view_num'] = $this->readNum($value['create_time']);
            $res[$key]['content'] = strip_tags(mb_substr($value['content'],0,50,'utf-8')).'....';
            unset($res[$key]['create_time']);
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
}
