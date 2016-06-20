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

class Subject extends \FS_Service {

    public $subjectModel = null;

    public $labelService = null;

    public $userService = null;

    public $commentService = null;

    public $praiseService = null;
    
    public $albumService = null;

    public function __construct() {
        $this->subjectModel = new SubjectModel();
        $this->labelService = new LabelService();
        $this->userService = new UserService();
        $this->commentService = new CommentService();
        $this->praiseService = new PraiseService();
        $this->albumService = new AlbumService();
    }

    /**
     * 批量获取帖子信息
     * $currentUid 当前用户ID
     * $field 包括 'user_info', 'count', 'comment', 'group_labels',
     * 'praise_info', 'share_info'
     */
    public function getBatchSubjectInfos($subjectIds, $currentUid = 0, $field = array('user_info', 'count', 'comment', 'group_labels', 'praise_info', 'album'), $status = array()) {
        if (empty($subjectIds) || !is_array($subjectIds)) {
            return $this->succ(array());
        }
        // 获取帖子基本信息
        $subjectInfos = $this->subjectModel->getSubjectByIds($subjectIds);
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
            $userArr = $this->userService->getUserInfoByUids($userIds, $currentUid, array("relation", "count"))['data'];
        }
        //获取评论信息
        if (in_array('comment', $field)) {
            $comments = $this->commentService->getBatchCommentList($subjectIds, 2)['data'];
        }
        // 获取标签信息
        if (in_array('group_labels', $field)) {
            $subjectLabels = $this->labelService->getBatchSubjectLabels($subjectIds)['data'];
        }
        // 获取计数信息
        if (in_array('count', $field)) {
            $commentCounts = $this->commentService->getBatchCommentNums($subjectIds)['data'];
            $praiseCounts = $this->praiseService->getBatchSubjectPraises($subjectIds)['data'];
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
            // 处理帖子图片地址
            $imageUrl = array();
            $smallImageUrl = array();
            $bigImageUrl = array();
            if (!empty($subjectInfo['image_url']) && empty($subjectInfo['ext_info'])) {
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
            if (!empty($subjectInfo['ext_info'])) {
                $imageInfos = json_decode(trim(stripslashes($subjectInfo['ext_info']), '"'), true);
                if (is_array($imageInfos['image']) && !empty($imageInfos['image'])) {
                    foreach ($imageInfos['image'] as $key => $image) {
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
            }
            if (in_array('praise_info', $field)) {
                $subjectRes[$subjectInfo['id']]['praise_user_info'] = is_array($praiseInfos[$subjectInfo['id']]) ? array_values($praiseInfos[$subjectInfo['id']]) : array();
            }
            // 获取专栏信息
            if (in_array('album', $field)) {
                if (!empty($albumArticles[$subjectInfo['id']])) {
                    $subjectRes[$subjectInfo['id']]['album_article '] = $albumArticles[$subjectInfo['id']];
                }
            }
            if (in_array('share_info', $field)) {
                // 分享内容
                $this->config->load('wish_config');
                $share = $this->config->item('group_share', 'group');
                $this->config->load('mobile_app');
                $shareUrl = $this->config->item('group_share');
                $shareTitle = !empty($subjectInfo['title']) ? "【{$subjectInfo['title']}】 " : '';
                $shareDesc = !empty($subjectInfo['text']) ? $subjectInfo['text'] : "超过20万妈妈正在蜜芽圈热聊，快来看看~";
                if (isset($subjectRes[$subjectInfo['id']]['video_info']['cover_image']) && !empty($subjectRes[$subjectInfo['id']]['video_info']['cover_image'])) {
                    $shareImage = $subjectRes[$subjectInfo['id']]['video_info']['cover_image'];
                } else {
                    $shareImage = 'http://o6ov54mbs.bkt.clouddn.com/d1/p3/2016/04/21/fc/fd4/fcf4b48fe16504ed8812f014e5d0b266.png';
                }
                // 替换搜索关联数组
                $replace = array('{|title|}' => $shareTitle, '{|desc|}' => $shareDesc, '{|image_url|}' => $shareImage, '{|wap_url|}' => sprintf($shareUrl['subject_wap_url'], $subjectInfo['id']));
                // 进行替换操作
                foreach ($share as $keys => $sh) {
                    // $share[$keys] = $this->_buildGroupShare($sh, $replace);
                    $share[$keys]['share_img_list'] = array();
                    if (!empty($subjectRes[$subjectInfo['id']]['image_url'])) {
                        $share[$keys]['share_img_list'] = $subjectRes[$subjectInfo['id']]['image_url'];
                    }
                }
                $subjectRes[$subjectInfo['id']]['share_info'] = $share;
            }
            if (intval($currentUid) > 0) {
                $subjectRes[$subjectInfo['id']]['fancied_by_me'] = $isPraised[$subjectInfo['id']] ? true : false;
            }
        }
        return $this->succ($subjectRes);
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
            return false;
        }
        $subjectSetInfo = array();
        if (!isset($subjectInfo['user_info']) || empty($subjectInfo['user_info'])) {
            return false;
        }
        // 添加视频
        if ($subjectInfo['video_url']) {
            $videoInfo['user_id'] = $subjectInfo['user_info']['user_id'];
            $videoInfo['video_origin_url'] = $subjectInfo['video_url'];
            $videoInfo['source'] = 'qiniu';
            $videoInfo['status'] = 2;
            $videoId = $this->addSubjectVideo($videoInfo, true)['data'];
            if ($videoId > 0) {
                // 如果有视频，subject状态置为转码中
                $subjectSetInfo['status'] = 2;
            }
        }
        $subjectSetInfo['user_id'] = $subjectInfo['user_info']['user_id'];
        if (isset($subjectInfo['title']) && trim($subjectInfo['title']) != "") {
            $subjectSetInfo['title'] = trim($subjectInfo['title']);
            $subjectSetInfo['title'] = $subjectInfo['title'];
        }
        if (isset($subjectInfo['text']) && trim($subjectInfo['text']) != "") {
            $subjectSetInfo['text'] = trim($subjectInfo['text']);
            $subjectSetInfo['text'] = $subjectInfo['text'];
        } else {
            $subjectSetInfo['text'] = '';
        }
        $subjectSetInfo['created'] = date("Y-m-d H:i:s", time());
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
            return $this->succ();
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
                $savelab = $labelInfo['title'];
                $labelRelationSetInfo = array("subject_id" => $subjectId, "label_id" => 0, "create_time" => $subjectSetInfo['created'], "user_id" => $subjectInfo['user_info']['user_id']);
                if (isset($labelInfo['id']) && $labelInfo['id'] > 0) {
                    $labelRelationSetInfo['label_id'] = $labelInfo['id'];
                } else {
                    // 如果没有便签id，则需要验证该标签标题是否已经存在
                    $labelResult = $this->labelService->checkIsExistByLabelTitle($labelInfo['title'])['data'];
                    if (empty($labelResult)) {
                        // 如果没有存在，则保存该自定义标签
                        $insertId = $this->labelService->addLabel($savelab)['data'];
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
            return false;
        }
        if (intval($videoInfo['subject_id']) > 0) {
            $setInfo[]['subject_id'] = $videoInfo['subject_id'];
        }
        if (in_array($videoInfo['status'], array(1, 2))) {
            $setInfo[]['status'] = $videoInfo['status'];
        }
        // update视频
        $where[] = ['id', $videoInfo['id']];
        $this->subjectModel->updateVideoBySubject($setInfo, $where);
        
        if (isset($videoInfo['subject_status']) && in_array($videoInfo['subject_status'], array(-1, 0, 1, 2)) && intval($videoInfo['subject_id']) > 0) {
            // 更新视频状态，同步更新帖子
            $s_where[] = ['id', $videoInfo['subject_id']];
            $s_setData = [['status' => $videoInfo['subject_status']]];
            $this->subjectModel->updateSubject($s_setData, $s_where);
        }
        
        return true;
    }

    /**
     * 批量查询视频信息
     */
    public function getBatchVideoInfos($videoIds, $videoType = 'm3u8') {
        $data = $this->subjectModel->getBatchVideoInfos($videoIds, $videoType);
        return $this->succ($data);
    }
}

