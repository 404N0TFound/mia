<?php
namespace mia\miagroup\Service;
class Subject extends \FS_Service {
    
    /**
     * 批量获取帖子信息
     * $currentUid 当前用户ID
     * $field 包括 'user_info', 'count', 'comment', 'group_labels', 
     *            'praise_info', 'share_info'
     */
    public function getBatchSubjectInfos($subjectIds, $currentUid = 0, $field = array('user_info', 'count', 'comment', 'group_labels', 'praise_info'), $status = array()) {
        if (empty($subjectIds) || !is_array($subjectIds)) {
            return $this->succ(array());
        }
        //获取帖子基本信息
        $subjectModel = new \mia\miagroup\Model\Subject();
        $subjectInfos = $subjectModel->getSubjectByIds($subjectIds);
        if (empty($subjectInfos)) {
            return $this->succ(array());
        }
        
        //收集id
        $userIdArr = array();
        foreach ($subjectInfos as $subjectInfo) {
            $userIdArr[] = $subjectInfo['user_id'];
        }
        
//         //用户信息
//         if (in_array('user_info', $field)) {
//             $userIds = array_unique($userIdArr);
//             $userArr = $this->mAccount->getUserInfoByUids($userIds,$currentUid,array("relation","count"));
//         }
//         //获取评论信息
//         if (in_array('comment', $field)) {
//             $comments = $this->mComment->getBatchCommentList($subjectIds, 2);
//         }
        //获取标签信息
        if (in_array('group_labels', $field)) {
            $labelService = new \mia\miagroup\Service\Label();
            $subjectLabels = $labelService->getBatchSubjectLabels($subjectIds);
        }
        //获取计数信息
        if (in_array('count', $field)) {
            $commentCounts = $this->mComment->getBatchCommentNums($subjectIds);
            $praiseCounts = $this->getBatchSubjectPraises($subjectIds);
        }
        //获取赞用户
        if (in_array('praise_info', $field)) {
            $praiseInfos = $this->getBatchSubjectPraiseUsers($subjectIds);
        }
        //获取是否已赞
        if (intval($currentUid) > 0) {
            $isPraised = $this->getBatchSubjectIsPraised($subjectIds, $currentUid);
        }
        
        $subjectRes = array();
        //拼装结果集
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
            //处理帖子图片地址
            $imageUrl = array();
            $smallImageUrl = array();
            $bigImageUrl = array();
            if(!empty($subjectInfo['image_url']) && empty($subjectInfo['ext_info'])){
                $imageUrlArr = explode("#", $subjectInfo['image_url']);
                if(!empty($imageUrlArr[0])){
                    foreach ($imageUrlArr as $k=>$image){
                        $pathInfo = pathinfo($image);
                        $smallImage = $pathInfo['dirname']."/".$pathInfo['filename']."_small.".$pathInfo['extension'];
                        if (strpos($smallImage, "app_group") !== false) {
                            $smallImage = "/d1/p1/" . $smallImage; //以app_group开头的图片其小图在远端，需要加/d1/p1
                        }
                        $imageUrl[$k]['url'] = IMAGE_URL.$image;
                        $imageUrl[$k]['height'] = 640;
                        $imageUrl[$k]['width'] = 640;
                        $smallImageUrl[$k] = IMAGE_URL.$smallImage;
                        $bigImageUrl[$k] = IMAGE_URL.$image;
                    }
                }
            }
            if(!empty($subjectInfo['ext_info'])){
                $imageInfos = json_decode(trim(stripslashes($subjectInfo['ext_info']),'"') , true);
                if (is_array($imageInfos['image']) && !empty($imageInfos['image'])) {
                    foreach($imageInfos['image'] as $key => $image){
                        $pathInfo = pathinfo($image['url']);
                        $small_image_url = $pathInfo['dirname'] . "/" . $pathInfo['filename'] . "_small." . $pathInfo['extension'];
                        if (strpos($small_image_url, "app_group") !== false) {
                            $small_image_url = "/d1/p1/" . $small_image_url; //以app_group开头的图片其小图在远端，需要加/d1/p1
                        }
                        $imageUrl[$key] = $image;
                        $imageUrl[$key]['url'] = IMAGE_URL . $image['url'];
                        $smallImageUrl[$key] = IMAGE_URL . $small_image_url;
                        $bigImageUrl[$key] = IMAGE_URL . $image['url'];
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
                $subjectRes[$subjectInfo['id']]['comment_count'] = $commentCounts[$subjectInfo['id']];
                $subjectRes[$subjectInfo['id']]['fancied_count'] = $praiseCounts[$subjectInfo['id']];
            }
            if (in_array('praise_info', $field)) {
                $subjectRes[$subjectInfo['id']]['praise_user_info'] = is_array($praiseInfos[$subjectInfo['id']]) ? array_values($praiseInfos[$subjectInfo['id']]) : array();
            }
            if (in_array('share_info', $field)) {
                //分享内容
                $this->config->load('wish_config');
                $share = $this->config->item('group_share', 'group');
                $this->config->load('mobile_app');
                $shareUrl = $this->config->item('group_share');
                $shareTitle = !empty($subjectInfo['title']) ? "【{$subjectInfo['title']}】 " : '';
                $shareDesc = !empty($subjectInfo['text']) ? $subjectInfo['text'] : "超过20万妈妈正在蜜芽圈热聊，快来看看~";
                //print_r($imageUrl);exit;
                //$shareImage = "http://o6ov54mbs.bkt.clouddn.com/d1/p3/2016/04/21/fc/fd4/fcf4b48fe16504ed8812f014e5d0b266.png";
        
                 
                if(isset($subjectRes[$subjectInfo['id']]['video_info']['cover_image']) && !empty($subjectRes[$subjectInfo['id']]['video_info']['cover_image'])){
                    $shareImage = $subjectRes[$subjectInfo['id']]['video_info']['cover_image'];
                }else{
                    $shareImage = 'http://o6ov54mbs.bkt.clouddn.com/d1/p3/2016/04/21/fc/fd4/fcf4b48fe16504ed8812f014e5d0b266.png';
                }
        
                //替换搜索关联数组
                $replace = array(
                    '{|title|}'    => $shareTitle,
                    '{|desc|}'  => $shareDesc,
                    '{|image_url|}'  => $shareImage,
                    '{|wap_url|}'  => sprintf($shareUrl['subject_wap_url'], $subjectInfo['id']),
                );
                //进行替换操作
                foreach ($share as $keys => $sh) {
                    $share[$keys] = $this->_buildGroupShare($sh, $replace);
                    $share[$keys]['share_img_list'] = array();
                    if(!empty($subjectRes[$subjectInfo['id']]['image_url'])){
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
}
