<?php
namespace mia\miagroup\Service;

use \F_Ice;
use mia\miagroup\Util\QiniuUtil;
use mia\miagroup\Model\Subject as SubjectModel;
use mia\miagroup\Service\Label as LabelService;
use mia\miagroup\Service\User as UserService;
use mia\miagroup\Service\Comment as CommentService;
use mia\miagroup\Service\Praise as PraiseService;
use mia\miagroup\Service\Album as AlbumService;
use mia\miagroup\Util\NormalUtil;
use mia\miagroup\Service\PointTags as PointTagsService;
use mia\miagroup\Remote\RecommendedHeadline as HeadlineRemote;

class Subject extends \mia\miagroup\Lib\Service {

    public $subjectModel = null;

    public $labelService = null;

    public $userService = null;

    public $commentService = null;

    public $praiseService = null;
    
    public $albumService = null;

	public $tagsService = null;

    public function __construct() {
        parent::__construct();
        $this->subjectModel = new SubjectModel();
        $this->labelService = new LabelService();
        $this->userService = new UserService();
        $this->praiseService = new PraiseService();
        $this->albumService = new AlbumService();
		$this->tagsService = new PointTagsService();
    }

    /**
     * 批量获取帖子信息
     * $currentUid 当前用户ID
     * $field 包括 'user_info', 'count', 'comment', 'group_labels',
     * 'praise_info', 'share_info'
     */
    public function getBatchSubjectInfos($subjectIds, $currentUid = 0, $field = array('user_info', 'count', 'comment', 'group_labels', 'praise_info', 'album','share_info'), $status = array(1, 2)) {
        if (empty($subjectIds) || !is_array($subjectIds)) {
            return $this->succ(array());
        }
        // 获取帖子基本信息
        $subjectInfos = $this->subjectModel->getSubjectByIds($subjectIds, $status);
        if (empty($subjectInfos)) {
            return $this->succ(array());
        }
        
        // 收集id
        $userIdArr = array();
        foreach ($subjectInfos as $subjectInfo) {
            $userIdArr[] = $subjectInfo['user_id'];
        }
        
        // 用户信息
        if (in_array('user_info', $field)) {
            $userIds = array_unique($userIdArr);
            $userArr = $this->userService->getUserInfoByUids($userIds, $currentUid, array("relation"))['data'];
        }
        // 获取评论信息
        if (in_array('comment', $field)) {
            $this->commentService = new CommentService();
            $comments = $this->commentService->getBatchCommentList($subjectIds, 2)['data'];
        }
        // 获取标签信息
        if (in_array('group_labels', $field)) {
            $subjectLabels = $this->labelService->getBatchSubjectLabels($subjectIds)['data'];
        }
        // 获取计数信息
        if (in_array('count', $field)) {
            $this->commentService = new CommentService();
            $commentCounts = $this->commentService->getBatchCommentNums($subjectIds)['data'];
            $praiseCounts = $this->praiseService->getBatchSubjectPraises($subjectIds)['data'];
            $viewCounts = $this->getBatchSubjectViewCount($subjectIds)['data'];
        }
        // 获取赞用户
        if (in_array('praise_info', $field)) {
            $praiseInfos = $this->praiseService->getBatchSubjectPraiseUsers($subjectIds)['data'];
        }
        // 获取专栏信息
        if (in_array('album', $field)) {
            $albumArticles = $this->albumService->getBatchAlbumBySubjectId($subjectIds)['data'];
        }
        // 获取是否已赞
        if (intval($currentUid) > 0) {
            $isPraised = $this->praiseService->getBatchSubjectIsPraised($subjectIds, $currentUid)['data'];
        }
        
        $subjectRes = array();
        // 拼装结果集
        foreach ($subjectIds as $subjectId) {
            if (!empty($subjectInfos[$subjectId])) {
                $subjectInfo = $subjectInfos[$subjectId];
            } else {
                continue;
            }
            $subjectRes[$subjectInfo['id']]['id'] = $subjectInfo['id'];
            $subjectRes[$subjectInfo['id']]['created'] = $subjectInfo['created'];
            $subjectRes[$subjectInfo['id']]['title'] = $subjectInfo['title'];
            $subjectRes[$subjectInfo['id']]['text'] = $subjectInfo['text'];
            $subjectRes[$subjectInfo['id']]['status'] = $subjectInfo['status'];
            $subjectRes[$subjectInfo['id']]['is_top'] = $subjectInfo['is_top'];
            $subjectRes[$subjectInfo['id']]['user_id'] = $subjectInfo['user_id'];
            $subjectRes[$subjectInfo['id']]['is_fine'] = $subjectInfo['is_fine'];
            $subjectRes[$subjectInfo['id']]['show_age'] = $subjectInfo['show_age'];
            $subjectRes[$subjectInfo['id']]['share_count'] = $subjectInfo['share_count'];
            // 处理帖子图片地址
            $imageUrl = array();
            $smallImageUrl = array();
            $bigImageUrl = array();
            if (!empty($subjectInfo['image_url']) && empty($subjectInfo['ext_info']['image'])) {
                $imageUrlArr = explode("#", $subjectInfo['image_url']);
                if (!empty($imageUrlArr[0])) {
                    foreach ($imageUrlArr as $k => $image) {
                        $pathInfo = pathinfo($image);
                        $smallImage = $pathInfo['dirname'] . "/" . $pathInfo['filename'] . "_small." . $pathInfo['extension'];
                        if (strpos($smallImage, "app_group") !== false) {
                            $smallImage = "/d1/p1/" . $smallImage; // 以app_group开头的图片其小图在远端，需要加/d1/p1
                        }
                        $imageUrl[$k]['url'] = F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $image;
                        $imageUrl[$k]['height'] = 640;
                        $imageUrl[$k]['width'] = 640;
                        $smallImageUrl[$k] = F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $smallImage;
                        $bigImageUrl[$k] = F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $image;
                    }
                }
            }
            if (!empty($subjectInfo['ext_info']['image'])) {
                $imageInfos = $subjectInfo['ext_info']['image'];
                if (is_array($imageInfos) && !empty($imageInfos)) {
                    foreach ($imageInfos as $key => $image) {
                        $pathInfo = pathinfo($image['url']);
                        $small_image_url = $pathInfo['dirname'] . "/" . $pathInfo['filename'] . "_small." . $pathInfo['extension'];
                        if (strpos($small_image_url, "app_group") !== false) {
                            $small_image_url = "/d1/p1/" . $small_image_url; // 以app_group开头的图片其小图在远端，需要加/d1/p1
                        }
                        $imageUrl[$key] = $image;
                        $imageUrl[$key]['url'] = F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $image['url'];
                        $smallImageUrl[$key] = F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $small_image_url;
                        $bigImageUrl[$key] = F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $image['url'];
                    }
                }
            }
            $subjectRes[$subjectInfo['id']]['image_infos'] = $imageUrl;
            $subjectRes[$subjectInfo['id']]['small_image_url'] = $smallImageUrl;
            $subjectRes[$subjectInfo['id']]['image_url'] = $bigImageUrl;
            if (!empty($subjectInfo['ext_info']['koubei']) || !empty($subjectInfo['ext_info']['koubei_id'])) {
                if (!empty($subjectInfo['ext_info']['koubei'])) {
                    $subjectRes[$subjectInfo['id']]['koubei_id'] = $subjectInfo['ext_info']['koubei']['id'];
                } else if (!empty($subjectInfo['ext_info']['koubei_id'])) {
                    $subjectRes[$subjectInfo['id']]['koubei_id'] = $subjectInfo['ext_info']['koubei_id'];
                }
            }
            if (!empty($subjectInfo['video_info'])) {
                $subjectRes[$subjectInfo['id']]['video_info'] = $subjectInfo['video_info'];
            }
            if (in_array('user_info', $field)) {
                $subjectRes[$subjectInfo['id']]['user_info'] = $userArr[$subjectInfo['user_id']];
            }
            if (in_array('comment', $field)) {
                $subjectRes[$subjectInfo['id']]['comment_info'] = is_array($comments[$subjectInfo['id']]) ? array_values($comments[$subjectInfo['id']]) : array();
            }
            if (in_array('group_labels', $field)) {
                $subjectRes[$subjectInfo['id']]['group_labels'] = is_array($subjectLabels[$subjectInfo['id']]) ? array_values($subjectLabels[$subjectInfo['id']]) : array();
            }
            if (in_array('count', $field)) {
                $subjectRes[$subjectInfo['id']]['comment_count'] = intval($commentCounts[$subjectInfo['id']]);
                $subjectRes[$subjectInfo['id']]['fancied_count'] = intval($praiseCounts[$subjectInfo['id']]);
                $subjectRes[$subjectInfo['id']]['view_count'] = intval($viewCounts[$subjectInfo['id']]);
            }
            if (in_array('praise_info', $field)) {
                $subjectRes[$subjectInfo['id']]['praise_user_info'] = is_array($praiseInfos[$subjectInfo['id']]) ? array_values($praiseInfos[$subjectInfo['id']]) : array();
            }
            // 获取专栏信息
            if (in_array('album', $field)) {
                if (!empty($albumArticles[$subjectInfo['id']])) {
                    $subjectRes[$subjectInfo['id']]['album_article'] = $albumArticles[$subjectInfo['id']];
                }
            }
            if (in_array('share_info', $field)) {
                // 分享内容
                $shareConfig = \F_Ice::$ins->workApp->config->get('busconf.subject');
                $share = $shareConfig['groupShare'];
                if (!empty($albumArticles[$subjectInfo['id']])) { //专栏
                    $shareDefault = $shareConfig['defaultShareInfo']['album'];
                    $shareTitle = strlen($albumArticles[$subjectInfo['id']]['title']) > 0 ? $albumArticles[$subjectInfo['id']]['title'] : $shareDefault['title'];
                    $shareDesc = $albumArticles[$subjectInfo['id']]['content'];
                    $shareImage = $shareDefault['img_url'];
                    $h5Url = sprintf($shareDefault['wap_url'], $albumArticles[$subjectInfo['id']]['id'], $albumArticles[$subjectInfo['id']]['album_id']);
                } else { //普通帖子
                    $shareDefault = $shareConfig['defaultShareInfo']['subject'];
                    $shareTitle = !empty($subjectInfo['title']) ? "【{$subjectInfo['title']}】 " : $shareDefault['title'];
                    $shareDesc = !empty($subjectInfo['text']) ? $subjectInfo['text'] : $shareDefault['desc'];
                    if (isset($subjectRes[$subjectInfo['id']]['video_info']['cover_image']) && !empty($subjectRes[$subjectInfo['id']]['video_info']['cover_image'])) {
                        $shareImage = $subjectRes[$subjectInfo['id']]['video_info']['cover_image'];
                    } else {
                        $shareImage = $shareDefault['img_url'];
                    }
                    $h5Url = sprintf($shareDefault['wap_url'], $subjectInfo['id']);
                }
                // 替换搜索关联数组
                $replace = array('{|title|}' => $shareTitle, '{|desc|}' => $shareDesc, '{|image_url|}' => $shareImage, '{|wap_url|}' => $h5Url, '{|extend_text|}' => $shareDefault['extend_text']);
                // 进行替换操作
                foreach ($share as $keys => $sh) {
                    $share[$keys] = NormalUtil::buildGroupShare($sh, $replace);
                    $share[$keys]['share_img_list'] = array();
                    if (!empty($subjectRes[$subjectInfo['id']]['image_url'])) {
                        $share[$keys]['share_img_list'] = $subjectRes[$subjectInfo['id']]['image_url'];
                    }
                }
                $subjectRes[$subjectInfo['id']]['share_info'] = array_values($share);
            }
            if (intval($currentUid) > 0) {
                $subjectRes[$subjectInfo['id']]['fancied_by_me'] = $isPraised[$subjectInfo['id']] ? true : false;
            }
        }
        return $this->succ($subjectRes);
    }
    
    /**
     * 获取单条帖子信息
     */
    public function getSingleSubjectById($subjectId, $currentUid = 0, $field = array('user_info', 'count', 'comment', 'group_labels', 'praise_info', 'album','share_info'), $dmSync = array(), $status = array(1, 2)) {
        $subjectInfo = $this->getBatchSubjectInfos(array($subjectId), $currentUid, $field, $status);
        $subjectInfo = $subjectInfo['data'][$subjectId];
        if (empty($subjectInfo)) {
            return $this->succ(array());
        }

        //如果是专栏，获取作者的其他专栏
        if (!empty($subjectInfo['album_article'])) { 
            $con = [
                'user_id'   => $subjectInfo['user_info']['user_id'],
                'iPageSize' => 5,
            ];
            $albumServiceData = $this->albumService->getArticleList($con);
            $albumServiceData = $albumServiceData['data'];
            $albumArticleList = array();
            if (!empty($albumServiceData['article_list'])) {
                foreach ($albumServiceData['article_list'] as $article) {
                    //排除当前的
                    if ($article['album_article']['subject_id'] != $subjectId) {
                        $albumArticleList[] = $article;
                    }
                }
            }
            if (!empty($albumArticleList)) {
                //最多显示3条，输出4条给客户端显示全部
                $subjectInfo['recent_article'] = count($albumArticleList) > 4 ? array_slice($albumArticleList, 0, 4) : $albumArticleList;
            }
        }

        if (in_array('view_num_record', $field)) {
            //阅读量计数
            $this->subjectModel->viewNumRecord($subjectId);
        }
        if (!isset($dmSync['refer_subject_id']) || empty($dmSync['refer_subject_id'])) {
            $dmSync['refer_subject_id'] = $subjectId;
        }
        if (!empty($dmSync['refer_subject_id']) || !empty($dmSync['refer_channel_id'])) {
            //相关帖子
            $headlineRemote = new HeadlineRemote();
            $subjectIds = $headlineRemote->headlineRelate($dmSync['refer_channel_id'], $dmSync['refer_subject_id'], $currentUid);
            $recommendArticle = $this->getBatchSubjectInfos($subjectIds)['data'];
            
            $subjectInfo['recommend_article'] = count($recommendArticle) > 5 ? array_slice($recommendArticle, 0, 5) : $recommendArticle;
        }
        return $this->succ($subjectInfo);
    }
    
    
    /**
     * 批量获取用户发布的帖子数
     */
    public function getBatchUserSubjectCounts($userIds) {
        $data = $this->subjectModel->getBatchUserSubjectCounts($userIds);
        return $this->succ($data);
    }

    /**
     * 发布帖子
     * @param unknown $subjectInfo
     * @param unknown $pointInfo
     * @param unknown $labelInfos
     * @param unknown $koubeiId
     */
    public function issue($subjectInfo, $pointInfo = array(), $labelInfos = array(), $koubeiId = 0) {
        if (empty($subjectInfo)) {
            return $this->error(500);
        }
        $subjectSetInfo = array();
        if (!isset($subjectInfo['user_info']) || empty($subjectInfo['user_info'])) {
            return $this->error(500);
        }
        if (strtotime($subjectInfo['created']) > 0) {
            $subjectSetInfo['created'] = $subjectInfo['created'];
        } else {
            $subjectSetInfo['created'] = date("Y-m-d H:i:s", time());
        }
        // 添加视频
        if ($subjectInfo['video_url']) {
            $videoInfo['user_id'] = $subjectInfo['user_info']['user_id'];
            $videoInfo['video_origin_url'] = $subjectInfo['video_url'];
            $videoInfo['source'] = 'qiniu';
            $videoInfo['status'] = 2;
            $videoInfo['create_time'] = $subjectSetInfo['created'];
            $videoId = $this->addSubjectVideo($videoInfo, true)['data'];
            if ($videoId > 0) {
                // 如果有视频，subject状态置为转码中
                $subjectSetInfo['status'] = 2;
            }
        }
        $emojiUtil = new \mia\miagroup\Util\EmojiUtil();
        $subjectSetInfo['user_id'] = $subjectInfo['user_info']['user_id'];
        if (isset($subjectInfo['title']) && trim($subjectInfo['title']) != "") {
            $subjectSetInfo['title'] = trim($emojiUtil->emoji_unified_to_html($subjectInfo['title']));
        }
        if (isset($subjectInfo['text']) && trim($subjectInfo['text']) != "") {
            $subjectSetInfo['text'] = trim($emojiUtil->emoji_unified_to_html($subjectInfo['text']));
        } else {
            $subjectSetInfo['text'] = '';
        }
        if (intval($subjectInfo['source']) > 0) {
            $subjectSetInfo['source'] = $subjectInfo['source'];
        }
        // ext_info保存帖子口碑关联信息
        if (intval($koubeiId) > 0) {
            $subjectSetInfo['ext_info']['koubei']['id'] = $koubeiId;
        }
        // ext_info保存帖子的图片宽高信息
        $imageInfo = array();
        $imgUrl = array();
        if (isset($subjectInfo['image_infos']) && !empty($subjectInfo['image_infos'])) {
            foreach ($subjectInfo['image_infos'] as $image) {
                $imgUrl[] = $image['url'];
                $imageInfo[] = $image;
            }
        }
        $subjectSetInfo['image_url'] = implode("#", $imgUrl);
        $subjectSetInfo['ext_info']['image'] = $imageInfo;
        
        $subjectSetInfo['ext_info'] = json_encode($subjectSetInfo['ext_info']);
        $insertSubjectRes = $this->subjectModel->addSubject($subjectSetInfo);
        unset($subjectSetInfo['image_url']);
        unset($subjectSetInfo['ext_info']);
        if (!$insertSubjectRes) {
            // 发布失败
            return $this->error(1101);
        }
        // insert_id
        $subjectId = $insertSubjectRes;
        if ($videoId > 0) {
            // 更新视频的subject_id
            $this->updateSubjectVideo(array('id' => $videoId, 'subject_id' => $subjectId));
            $videoInfo = $this->getBatchVideoInfos(array($videoId), 'm3u8')['data'];
            $subjectSetInfo['video_info'] = $videoInfo[$videoId] ? $videoInfo[$videoId] : (object) array();
        }
        // 处理输出图片
        $subjectSetInfo['image_infos'] = array();
        $subjectSetInfo['small_image_url'] = array();
        if (!empty($subjectInfo['image_infos'])) {
            foreach ($subjectInfo['image_infos'] as $key => $image) {
                $pathInfo = pathinfo($image['url']);
                $small_image_url = $pathInfo['dirname'] . "/" . $pathInfo['filename'] . "_small." . $pathInfo['extension'];
                if (strpos($small_image_url, "app_group") !== false) {
                    $small_image_url = "/d1/p1/" . $small_image_url; // 以app_group开头的图片其小图在远端，需要加/d1/p1
                }
                $subjectSetInfo['image_infos'][$key] = $image;
                $subjectSetInfo['image_infos'][$key]['url'] = F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $image['url'];
                $subjectSetInfo['small_image_url'][] = F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $small_image_url;
            }
        }
        
        #start赠送用户蜜豆
        $mibean = new \mia\miagroup\Remote\MiBean();
        $param['user_id'] = $subjectSetInfo['user_id'];
        $param['relation_type'] = 'publish_pic';
        $param['relation_id'] = $subjectId;
        $param['to_user_id'] = $subjectSetInfo['user_id'];
        $mibean->add($param);
        #end赠送用户蜜豆
        
        // 添加蜜芽圈标签
        if (!empty($labelInfos)) {
            $labelArr = array();
            foreach ($labelInfos as $key => $labelInfo) {
                unset($labelInfo['selected']);
                unset($labelInfo['img_nums']);
                unset($labelInfo['is_focused']);
                unset($labelInfo['is_hot']);
                $labelInfo['title'] = trim($labelInfo['title']);
                $labelInfo['create_time'] = $subjectSetInfo['created'];
                $labelInfo['user_id'] = intval($subjectSetInfo['user_id']);
                $labelRelationSetInfo = array("subject_id" => $subjectId, "label_id" => 0, "create_time" => $subjectSetInfo['created'], "user_id" => $subjectInfo['user_info']['user_id']);
                if (isset($labelInfo['id']) && $labelInfo['id'] > 0) {
                    $labelRelationSetInfo['label_id'] = $labelInfo['id'];
                } else {
                    // 如果没有便签id，则需要验证该标签标题是否已经存在
                    $labelResult = $this->labelService->checkIsExistByLabelTitle($labelInfo['title'])['data'];
                    if (empty($labelResult)) {
                        // 如果没有存在，则保存该自定义标签
                        $insertId = $this->labelService->addLabel($labelInfo['title'])['data'];
                        $labelRelationSetInfo['label_id'] = $insertId;
                    } else {
                        $labelRelationSetInfo['label_id'] = $labelResult['id'];
                    }
                }
                // 保存图片标签关系信息
                $this->labelService->saveLabelRelation($labelRelationSetInfo)['data'];
                // 用于返回的标签结构体（发布后只需要这两个字段）
                $labelArr[$key]['id'] = isset($labelRelationSetInfo['label_id']) ? $labelRelationSetInfo['label_id'] : 0;
                $labelArr[$key]['title'] = $labelInfo['title'];
            }
            // 返回标签结构体
            $subjectSetInfo['group_labels'] = $labelArr;
        }

		//插入标记
        if(!empty($pointInfo[0])){
            foreach ($pointInfo as $itemPoint) {
                //插入帖子标记信息
                $this->tagsService->saveSubjectTags($subjectId,$itemPoint);
            }
        }
        
        $subjectSetInfo['id'] = $subjectId;
        $subjectSetInfo['status'] = 1;
        $subjectSetInfo['user_info'] = $this->userService->getUserInfoByUserId($subjectSetInfo['user_id'])['data'];
        
        return $this->succ($subjectSetInfo);
    }

    /**
     * 添加帖子视频
     */
    public function addSubjectVideo($videoInfo, $needThumb = false) {
        if (empty($videoInfo['user_id']) || empty($videoInfo['video_origin_url'])) {
            return false;
        }
        if ($needThumb === true && $videoInfo['source'] == 'qiniu') {
            
            $qiniusdk = new QiniuUtil();
            
            // 从七牛获取缩略图
            $qiniuConfig = F_Ice::$ins->workApp->config->get('busconf.qiniu');
            
            $videoInfo['cover_image'] = $qiniusdk->getVideoThumb($qiniuConfig['video_host'] . $videoInfo['video_origin_url']);
            // 获取视频元信息
            $avInfo = $qiniusdk->getVideoFileInfo($videoInfo['video_origin_url']);
            if (!empty($avInfo['duration'])) {
                $videoInfo['video_time'] = $avInfo['duration'];
            }
            // 通知七牛对视频转码
            $videoInfo['transcoding_pipe'] = $qiniusdk->videoTrancodingHLS($videoInfo['video_origin_url']);
        }
        $insertData['subject_id'] = intval($videoInfo['subject_id']);
        $insertData['user_id'] = $videoInfo['user_id'];
        $insertData['video_origin_url'] = $videoInfo['video_origin_url'];
        $insertData['create_time'] = date('Y-m-d H:i:s');
        $insertData['source'] = !empty($videoInfo['source']) ? $videoInfo['source'] : '';
        $insertData['status'] = in_array($videoInfo['status'], array(1, 2)) ? $videoInfo['status'] : 0;
        if (strtotime($videoInfo['create_time']) > 0) {
            $insertData['create_time'] = $videoInfo['create_time'];
        } else {
            $insertData['create_time'] = date("Y-m-d H:i:s", time());
        }
        if (!empty($videoInfo['cover_image'])) {
            $insertData['ext_info']['cover_image'] = $videoInfo['cover_image'];
        }
        if (!empty($videoInfo['transcoding_pipe'])) {
            $insertData['ext_info']['transcoding_pipe'] = $videoInfo['transcoding_pipe'];
        }
        if (!empty($videoInfo['video_time'])) {
            $insertData['ext_info']['video_time'] = $videoInfo['video_time'];
        }
        $insertData['ext_info'] = !empty($insertData['ext_info']) ? json_encode($insertData['ext_info']) : '';
        // 添加视频
        $videoId = $this->subjectModel->addVideoBySubject($insertData);
        
        return $this->succ($videoId);
    }

    /**
     * 更新视频信息
     */
    public function updateSubjectVideo($videoInfo) {
        $setInfo = array();
        if (intval($videoInfo['id']) <= 0) {
            return $this->error(500);
        }
        if (intval($videoInfo['subject_id']) > 0) {
            $setInfo[] = ['subject_id', $videoInfo['subject_id']];
        }
        if (in_array($videoInfo['status'], array(1, 2))) {
            $setInfo[] = ['status', $videoInfo['status']];
        }
        // update视频
        $where[] = ['id', $videoInfo['id']];
        $this->subjectModel->updateVideoBySubject($setInfo, $where);
        
        if (isset($videoInfo['subject_status']) && in_array($videoInfo['subject_status'], array(-1, 0, 1, 2)) && intval($videoInfo['subject_id']) > 0) {
            // 更新视频状态，同步更新帖子
            $s_setData = [['status', $videoInfo['subject_status']]];
            $this->subjectModel->updateSubject($s_setData, $videoInfo['subject_id']);
        }
        return $this->succ();
    }
    
    /**
     * 删除帖子
     */
    public function deleteSubject($subjectId, $currentUid) {
        if (intval($subjectId) < 0 || intval($currentUid) < 0) {
            return $this->error(500);
        }
        $subjectInfo = $this->subjectModel->getSubjectByIds(array($subjectId));
        $subjectInfo = $subjectInfo[$subjectId];
        if (empty($subjectInfo) || $subjectInfo['user_id'] != $currentUid) {
            return $this->error(500);
        }
        $s_setData = [['status', 0]];
        $this->subjectModel->updateSubject($s_setData, $subjectId);
        return $this->succ();
    }

    /**
     * 批量查询视频信息
     */
    public function getBatchVideoInfos($videoIds, $videoType = 'm3u8') {
        $data = $this->subjectModel->getBatchVideoInfos($videoIds, $videoType);
        return $this->succ($data);
    }
    
    /**
     * 构建选题分享信息
     */
    private function _buildGroupShare($shareStruct, $replace) {
        //闭包函数,将一个字符串中的所有可替代字符，全部替代
        $func_replace = function($string, $replace) {
            foreach ($replace as $key => $re) {
                $string = str_replace($key, $re, $string);
            }
            return $string;
        };
        foreach ($shareStruct as $k => $s) {
            $shareStruct[$k] = $func_replace($s, $replace);
        }
        return $shareStruct;
    }
    
    /**
     * 批量给帖子加精
     */
    public function subjectAddFine($subjectId){
        $subjectId = is_array($subjectId) ? $subjectId : [$subjectId];
        //查询图片信息
        $subjects_info = $this->subjectModel->getSubjectByIds($subjectId);
        
        $affect = $this->subjectModel->setSubjectRecommendStatus($subjectId);
        if(!$affect){
            return $this->error(201,'帖子加精失败!');
        }
        //送蜜豆
        $mibean = new \mia\miagroup\Remote\MiBean();
        foreach($subjects_info as $subject_info){
            $param = array(
                'user_id'           => $subject_info['user_id'],//操作人
                'mibean'            => 5,
                'relation_type'     => 'fine_pic',
                'relation_id'       => $subject_info['id'],
                'to_user_id'        => $subject_info['user_id']
            );
            //验证是否送过
            $data = $mibean->check($param);
            if(empty($data['data'])){
                $data = $mibean->add($param);
            }
        }
        return $this->succ($data);
    }
    
    /**
     * 批量查询帖子相关商品信息
     */
    public function getBatchSubjectItemInfos($subjectIds) {
        $itemIds = array();
        $pointTag = new \mia\miagroup\Service\PointTags();
        $subjectItemArr = $pointTag->getBatchSubjectItmeIds($subjectIds)['data'];
        foreach ($subjectItemArr as $subjectItem) {
            $itemIds = is_array($subjectItem) ? array_merge($itemIds, $subjectItem) : $itemIds;
        }

        $item = new \mia\miagroup\Service\Item();
        $itemInfos = $item->getBatchItemBrandByIds($itemIds)['data'];
        $result = array();
        foreach ($subjectItemArr as $subjectId => $subjectItem) {
            foreach ($subjectItem as $itemId) {
                if (!empty($itemInfos[$itemId])) {
                    $result[$subjectId][$itemId] = $itemInfos[$itemId];
                }
            }
        }
        return $this->succ($result);
    }
    
    /**
     * 批量查询帖子阅读数
     */
    public function getBatchSubjectViewCount($subjectIds) {
        $subjects = $this->subjectModel->getSubjectByIds($subjectIds, array());
        $subjectCountArr = array();
        $numRatio = 3; //放大倍数
        foreach ($subjects as $subjectId => $subject) {
            if (intval($subject['view_num']) > 0) {
                $subjectCountArr[$subjectId] = $subject['view_num'] * $numRatio;
            } else {
                //如果阅读数为零，设置初始阅读数
                $viewNum = rand(200, 300);
                $subjectCountArr[$subjectId] = $viewNum * $numRatio;
                $this->subjectModel->viewNumRecord($subjectId, $viewNum);
            }
        }
    }
    
    /**
     * 头条导入帖子数据
     */
    public function syncHeadLineSubject($subject, $existCheck = 1) {
        if (empty($subject['user_id'])) {
            return $this->error('500');
        }
        $subjectInfo = array('user_info' => array('user_id' => $subject['user_id']));
        $subjectInfo['source'] = 3;
        if (!empty($subject['title'])) {
            $subjectInfo['title'] = $subject['title'];
        }
        if (!empty($subject['text'])) {
            $subjectInfo['text'] = $subject['text'];
        }
        if (!empty($subject['video_url'])) {
            $subjectInfo['video_url'] = $subject['video_url'];
        }
        if (!empty($subject['created'])) {
            $subjectInfo['created'] = $subject['created'];
        }
        $uniqueFlag = md5($subject['video_url']);
        $redis = new \mia\miagroup\Lib\Redis();
        $key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.headLineKey.syncUniqueFlag.key'), $uniqueFlag);
        $subjectId = $redis->get($key);
        if (!$subjectId) {
            $subjectData = new \mia\miagroup\Data\Subject\Video();
            $subjectId = $subjectData->getRow(array('video_origin_url', $subject['video_url']), 'subject_id');
            $subjectId = $subjectId['subject_id'];
            if (intval($subjectId) > 0) {
                $redis->setex($key, $subjectId, 8640000);
            }
        }
        if ($subjectId) {
            if (!empty($subject['user_id'])) {
                $setData[] = array('user_id', $subject['user_id']);
            }
            if (!empty($subjectInfo['title'])) {
                $setData[] = array('title', $subjectInfo['title']);
            }
            if (!empty($subjectInfo['text'])) {
                $setData[] = array('text', $subjectInfo['text']);
            }
            $this->subjectModel->updateSubject($setData, $subjectId);
            if (!empty($subject['user_id'])) {
                $setVideoData[] = array('user_id', $subject['user_id']);
                $where[] = ['subject_id', $subjectId];
                $this->subjectModel->updateVideoBySubject($setVideoData, $where);
            }
            if ($existCheck) {
                return $this->succ($subjectId);
            }
        }
        $preNode = \DB_Query::switchCluster(\DB_Query::MASTER);
        $result = $this->issue($subjectInfo);
        \DB_Query::switchCluster($preNode);
        $redis->setex($key, $subjectId, 8640000);
        if ($result['code'] > 0) {
            return $this->error($result['code']);
        } else {
            return $this->succ($result['data']['id']);
        }
    }
}

