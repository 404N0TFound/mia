<?php
namespace mia\miagroup\Model;

use mia\miagroup\Data\Album\AlbumArticle as AlbumArticleData;
use mia\miagroup\Data\Album\Album as AlbumData;
use mia\miagroup\Data\Album\GroupDoozer as GroupDoozer;

class Album {

    public $albumArticleData = '';

    public $albumData = '';

    public $albumGroupDoozerData = '';

    public function __construct() {
        $this->albumArticleData = new AlbumArticleData();
        $this->albumData = new AlbumData();
        $this->albumGroupDoozerData = new GroupDoozer();
    }

    public function getBatchAlbumBySubjectId($subjectIds) {
        $res = $this->albumArticleData->getBatchAlbumBySubjectId($subjectIds);
        $idArr = array();
        if ($res) {
            foreach ($res as $val) {
                $idArr[] = $val['id'];
            }
        }
        return $this->getBatchArticle($idArr);
    }

    public function getBatchArticle($articleIds) {
        $res = $this->albumArticleData->getBatchArticle($articleIds);
        foreach ($res as $key => $value) {
            $res[$key]['cover_image'] = F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $value['cover_image'];
        }
        return $res;
    }

    public function getArticleList($params) {
        $response = array();
        $articleList = $this->albumArticleData->getArticle($params);
        $articleSubjectIdList = new \mia\miagroup\Service\Subject();
        $articleResult = $subjectService->getBatchSubjectInfos($articleSubjectIdList, $params['user_id']);
        $response['article_list'] = $articleResult['data'];
        
        if (isset($params['page']) && $params['page'] == 1) {
            $albumResult = $this->albumData->getAlbumList($params['user_id']);
            $response['album_list'] = $albumResult;
        }
        
        return $response;
    }

    public function getRecommendAlbumArticleList($params) {
        $response = array();
        $articleList = $this->albumArticleData->getRecommendAlbumArticleList($params);
        $articleSubjectIdList = new \mia\miagroup\Service\Subject();
        $articleResult = $articleSubjectIdList->getBatchSubjectInfos($articleSubjectIdList, $params['user_id']); // $field 参数待定
        $response['article_list'] = $articleResult['data'];
        
        $userIdRes = $this->albumGroupDoozerData->getGroupDoozerList();
        
        $userIdArr = array();
        if ($userIdRes) {
            foreach ($userIdRes as $value) {
                $userIdArr[] = $value['user_id'];
            }
        }
        $User = new \mia\miagroup\Service\User();
        $userInfo = $User->getUserInfoByUids($userIdArr);
        $response['users'] = $userInfo['data'];
        
        return $response;
    }
}
