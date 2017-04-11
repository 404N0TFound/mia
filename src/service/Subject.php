<?php
namespace mia\miagroup\Service;

use \F_Ice;
use mia\miagroup\Remote\RecommendNote;
use mia\miagroup\Util\QiniuUtil;
use mia\miagroup\Model\Subject as SubjectModel;
use mia\miagroup\Service\Label as LabelService;
use mia\miagroup\Service\User as UserService;
use mia\miagroup\Service\Comment as CommentService;
use mia\miagroup\Service\Praise as PraiseService;
use mia\miagroup\Service\Album as AlbumService;
use mia\miagroup\Service\Koubei as KoubeiService;
use mia\miagroup\Util\NormalUtil;
use mia\miagroup\Service\PointTags as PointTagsService;
use mia\miagroup\Remote\RecommendedHeadline as HeadlineRemote;
use mia\miagroup\Service\Active as ActiveService;
use mia\miagroup\Service\Feed as FeedServer;

class Subject extends \mia\miagroup\Lib\Service
{
    public $subjectModel = null;
    public $labelService = null;
    public $userService = null;
    public $commentService = null;
    public $praiseService = null;
    public $albumService = null;
	public $tagsService = null;
    private $headlineRemote;
    private $config;

    public function __construct() {
        parent::__construct();
        $this->subjectModel = new SubjectModel();
        $this->labelService = new LabelService();
        $this->userService = new UserService();
        $this->praiseService = new PraiseService();
        $this->albumService = new AlbumService();
		$this->tagsService = new PointTagsService();
        $this->headlineRemote = new HeadlineRemote();

        $this->config = \F_Ice::$ins->workApp->config->get('busconf.subject');
    }

    /**
     * 首页导航分类标签
     * @param $userId
     * @return mixed
     */
    public function indexTabList($userId)
    {
        //起始固定位，“发现”，“关注”
        $beginning_tabs = $this->config['group_fixed_tab_first'];
        //配置位3个
        //$operation_tabs = $this->config['group_index_operation_tab'];
        $operation_tabs = [];//暂时不需要了
        //个性化推荐位6个
        $noteRemote = new RecommendNote($this->ext_params);
        $userTabNames = $noteRemote->getRecommendTabList($userId);
        //获取对应的一级分类

        //个性化和配置位去重
        foreach ($operation_tabs as $v) {
            foreach ($userTabNames as $key => $val){
                if($v['name'] == $val) {
                    unset($userTabNames[$key]);
                }
            }
        }

        $showTabs = $this->getFirstLevel($userTabNames);

        //最后固定位，“育儿”
        $last_tabs = $this->config['group_fixed_tab_last'];

        $tab_list['navList'] = array_merge($beginning_tabs, $operation_tabs, $showTabs, $last_tabs);
        return $this->succ($tab_list);
    }

    /**
     * 获取二级分类对应展示的一级分类
     * @param $secondLevel
     * @return array
     */
    public function getFirstLevel($secondLevel)
    {
        $cate = $this->config['second_level'];//所有二级分类
        $show = $this->config['first_level'];//所有一级分类

        $tab_infos = $this->subjectModel->getBatchTabInfos($secondLevel);//标签信息

        $firstLevel = [];
        foreach ($secondLevel as $val) {
            if (!array_key_exists($val, $cate)) {
                continue;
            }
            $key = array_search($cate[$val], $show);
            if (array_key_exists($key, $firstLevel)) {
                $firstLevel[$key]['extend_id'] .= "_" . $tab_infos[$val]['id'];
            } else {
                $firstLevel[$key] =
                    [
                        'name' => $cate[$val],
                        'url' => '',
                        'type' => 'miagroup',
                        'extend_id' => $tab_infos[$val]['id'],
                    ];
            }
        }
        return $firstLevel;
    }

    /**
     * 批量获取导航分类标签信息
     * @param $tabNames
     * @return array
     */
    public function getBatchTabInfos($tabNames)
    {
        if (!is_array($tabNames) || empty($tabNames)) {
            return [];
        }
        $tab_infos = $this->subjectModel->getBatchTabInfos($tabNames);
        if (empty($tab_infos)) {
            return [];
        }
        foreach ($tabNames as $v) {
            $res[] = [
                'name' => $tab_infos[$v]['show_name']?$tab_infos[$v]['show_name']:$tab_infos[$v]['tab_name'],
                'url' => '',
                'type' => 'miagroup',
                'extend_id' => $tab_infos[$v]['id'],
            ];
        }
        return $res;
    }

    /**
     * 每个分类下，笔记瀑布流列表
     * @param $userId
     * @param $tabId
     * @param $action [init,refresh,next]
     * @param $count
     * @param $page
     * @return mixed
     */
    public function noteList($tabId, $action, $page = 1, $count = 20, $userId = 0)
    {
        if (empty(intval($tabId)) || empty($action)) {
            return $this->succ([]);
        }
        //普通列表
        if ($action == 'init' || $action == 'refresh') {
            //推荐会自动去除展示过后的数据，所以刷新只要重复请求第一页就行
            $page = 1;
        }
        switch ($tabId) {
            //育儿
            case $this->config['group_fixed_tab_last'][0]['extend_id']:
                $noteRemote = new RecommendNote($this->ext_params);
                $userNoteListIds = $noteRemote->getYuerNoteList($this->config['yuer_labels'], $page, $count);
                if (empty($userNoteListIds)) {
                    $yuerSubjectIds = $this->labelService->getBatchSubjectIdsByLabelIds($this->config['yuer_label_ids'], 0, $page, $count, 0, [])['data'];
                    $yuerSubjectIds = array_keys($yuerSubjectIds);
                    if (empty($yuerSubjectIds)) {
                        $userNoteListIds = [];
                    } else {
                        $userNoteListIds = array_map(function ($v) {
                            return $v . "_subject";
                        }, $yuerSubjectIds);
                    }
                }
                break;
            //订阅
            case $tabId == $this->config['group_fixed_tab_first'][1]['extend_id']:
                $feedService = new FeedServer();
                $feedSubject = $feedService->getFeedSubject($userId, $userId, $page, $count)['data'];
                if (empty($feedSubject['subject_lists'])) {
                    $userNoteListIds = [];
                } else {
                    $userNoteListIds = array_map(function ($v) {
                        return $v['id'] . "_subject";
                    }, $feedSubject['subject_lists']);
                }
                break;
            //发现
            case $tabId == $this->config['group_fixed_tab_first'][0]['extend_id']:
                $noteRemote = new RecommendNote($this->ext_params);
                $userNoteListIds = $noteRemote->getRecommendNoteList($page, $count);
                break;
            default:
                $tabId = explode("_", $tabId);
                $noteRemote = new RecommendNote($this->ext_params);
                $tabName = $this->subjectModel->getTabInfos($tabId);
                $tabName = implode(",", array_keys($tabName));
                $userNoteListIds = $noteRemote->getNoteListByCate($tabName, $page, $count);
        }

        //发现列表，增加运营广告位
        $operationNoteData = [];
        if ($tabId == $this->config['group_fixed_tab_first'][0]['extend_id']) {
            $operationNoteData = $this->subjectModel->getOperationNoteData($tabId, $page);
            //运营数据和普通数据去重
            $userNoteListIds = array_diff($userNoteListIds, array_intersect(array_keys($operationNoteData), $userNoteListIds));
        }
        //合并数据Ids
        $combineIds = $this->combineOperationIds($userNoteListIds, $operationNoteData);

        $res['content_lists'] = $this->formatNoteData($combineIds, $operationNoteData);

        return $this->succ($res);
    }

    /**
     * 合并Id
     * @param $userNoteListIds
     * @param $operationNoteData
     * @return array
     */
    public function combineOperationIds(array $userNoteListIds, array $operationNoteData = [])
    {
        //按row，从小到大排序
        uasort($operationNoteData, function ($left, $right) {
            return $left['row'] > $right['row'];
        });
        //把运营数据插入到指定位置
        foreach ($operationNoteData as $noteId => $operationInfo) {
            $offset = $operationInfo['row'] - 1;
            $replacement = $noteId;
            array_splice($userNoteListIds, $offset, 0, $replacement);
        }
        return $userNoteListIds;
    }

    /**
     * 根据展示id，运营数据，拼凑出数据
     * @param array $ids
     * @param array $operationNoteData
     * @return array
     */
    public function formatNoteData(array $ids, $operationNoteData = [])
    {
        $subjectIds = array();
        $doozerIds = array();
        foreach ($ids as $key => $value) {
            list($relation_id, $relation_type) = explode('_', $value, 2);
            if($relation_type == 'link'){
                continue;
            }
            //帖子
            if ($relation_type == 'subject') {
                $subjectIds[] = $relation_id;
                //达人
            } elseif ($relation_type == 'doozer') {
                $doozerIds[] = $relation_id;
            }
        }
        
        //批量获取帖子信息
        if(!empty($subjectIds)){
            $subjects = $this->getBatchSubjectInfos($subjectIds)['data'];
        }
        
        //批量获取达人信息
        if (!empty($doozerIds)) {
            $doozerInfo = $this->userService->getUserInfoByUids($doozerIds, 0, ['count'])['data'];
        }

        $return = [];
        foreach ($ids as $value) {
            list($relation_id, $relation_type) = explode('_', $value, 2);
            //使用运营配置信息
            $is_opearation = 0;
            if (array_key_exists($value, $operationNoteData)) {
                $relation_desc = $operationNoteData[$value]['ext_info']['desc'] ? $operationNoteData[$value]['ext_info']['desc'] : '';
                $relation_title = $operationNoteData[$value]['ext_info']['title'] ? $operationNoteData[$value]['ext_info']['title'] : '';
                $relation_cover_image = !empty($operationNoteData[$value]['ext_info']['cover_image']) ? $operationNoteData[$value]['ext_info']['cover_image'] : '';
                $is_opearation = 1;
                $tmpData['config_data'] = $operationNoteData[$value];
            }
            switch ($relation_type) {
                //目前只有口碑帖子，蜜芽圈帖子。
                case 'subject':
                    if (isset($subjects[$relation_id]) && !empty($subjects[$relation_id])) {
                        //有无视频不区分，video_info有值代表有视频
                        $subject = $subjects[$relation_id];
                        $tmpData['id'] = $subject['id'] . '_subject';
                        $tmpData['type'] = 'subject';
                        $tmpData['type_name'] = '口碑';

                        $subject['title'] = $relation_title ? $relation_title : $subject['title'];
                        if(!empty($relation_cover_image)){
                            $subject['cover_image'] = $relation_cover_image;
                        }
                        $tmpData['subject'] = $subject;
                        $tmpData['is_opearation'] = $is_opearation;
                    }
                    break;
                case 'doozer':
                    if (isset($doozerInfo[$relation_id]) && !empty($doozerInfo[$relation_id])) {
                        $user = $doozerInfo[$relation_id];
                        $tmpData['id'] = $user['user_id'] . '_doozer';
                        $tmpData['type'] = 'doozer';
                        $tmpData['type_name'] = '达人';

                        //配置了用配置的title，否则用group_doozer里的intro
                        if(!empty($relation_title)){
                            $user['doozer_intro'] = $relation_title ? $relation_title : $doozerInfo[$relation_id]['intro'];
                        }
                        if(!empty($relation_cover_image)){
                            $user['doozer_recimage'] = $relation_cover_image;
                        }
                        $tmpData['doozer'] = $user;
                        $tmpData['is_opearation'] = $is_opearation;
                    }
                    break;
                case 'link':
                        //链接跳转，只是运营配置的
                        $linkInfo = $operationNoteData[$relation_id.'_'.$relation_type];
                        $tmpData['id'] = $relation_id;
                        $tmpData['type'] = 'link';
                        $tmpData['type_name'] = '链接';

                        $link['title'] = $relation_title ? $relation_title : '';
                        $link['id'] = $relation_id;
                        $link['image'] = $relation_cover_image;
                        $link['url'] = $linkInfo['ext_info']['url'];
                        $link['desc'] = $relation_desc;
                        $tmpData['link'] = $link;
                        $tmpData['is_opearation'] = $is_opearation;
                    break;
            }
            if (!empty($tmpData)) {
                $return[] = $tmpData;
            }
            unset($subject);
            unset($tmpData);
            unset($relation_title);
            unset($relation_cover_image);
        }
        return $return;
    }


    /**
     * 批量获取帖子信息
     * $currentUid 当前用户ID
     * $field 包括 'user_info', 'count', 'comment', 'group_labels',
     * 'praise_info', 'share_info', 'item', 'koubei'
     */
    public function getBatchSubjectInfos($subjectIds, $currentUid = 0, $field = array('user_info', 'count', 'content_format', 'praise_info', 'album'), $status = array(1, 2)) {
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
        //帖子关联商品信息
        if(in_array('item', $field) || in_array('koubei', $field)){
            $pointTag = new \mia\miagroup\Service\PointTags();
            $subjectItemIds = $pointTag->getBatchSubjectItmeIds($subjectIds)['data'];
            $itemIds = array();
            foreach ($subjectItemIds as $subjectItem) {
                $itemIds = is_array($subjectItem) ? array_merge($itemIds, $subjectItem) : $itemIds;
            }
            $itemService = new \mia\miagroup\Service\Item();
            $subjectItemInfos = $itemService->getBatchItemBrandByIds($itemIds)['data'];
            $itemInfoById = array();
            foreach ($subjectItemIds as $subjectId => $subjectItem) {
                foreach ($subjectItem as $itemId) {
                    if (!empty($subjectItemInfos[$itemId])) {
                        $itemInfoById[$subjectId][$itemId] = $subjectItemInfos[$itemId];
                    }
                }
            }
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
            if (in_array('content_format', $field)) {
                $text = $subjectInfo['text'];
                $text = str_replace("\r\n", ' ', $text);
                $text = str_replace("\n", ' ', $text);
                if (mb_strlen($text, 'utf8') > 50) {
                    $text = mb_substr($text, 0, 50, 'utf8');
                }
                $subjectRes[$subjectInfo['id']]['text'] = $text;
            } else {
                $subjectRes[$subjectInfo['id']]['text'] = $subjectInfo['text'];
            }
            $subjectRes[$subjectInfo['id']]['status'] = $subjectInfo['status'];
            $subjectRes[$subjectInfo['id']]['source'] = $subjectInfo['source'];
            $subjectRes[$subjectInfo['id']]['is_top'] = $subjectInfo['is_top'];
            $subjectRes[$subjectInfo['id']]['user_id'] = $subjectInfo['user_id'];
            $subjectRes[$subjectInfo['id']]['is_fine'] = $subjectInfo['is_fine'];
            $subjectRes[$subjectInfo['id']]['show_age'] = $subjectInfo['show_age'];
            $subjectRes[$subjectInfo['id']]['share_count'] = $subjectInfo['share_count'];
            // 处理帖子图片地址
            $imageUrl = array();
            $smallImageUrl = array();
            $bigImageUrl = array();
            $smallImageInfos = array();
            if (!empty($subjectInfo['image_url']) && empty($subjectInfo['ext_info']['image'])) {
                $imageUrlArr = explode("#", $subjectInfo['image_url']);
                if (!empty($imageUrlArr[0])) {
                    foreach ($imageUrlArr as $k => $image) {
                        $img_info = NormalUtil::buildImgUrl($image,'watermark',640,640);
                        $imageUrl[$k]['url'] = $img_info['url'];
                        $imageUrl[$k]['height'] = $img_info['height'];
                        $imageUrl[$k]['width'] = $img_info['width'];
                        $small_img_info = NormalUtil::buildImgUrl($image,'koubeismall',640,640);
                        $smallImageInfos[$k]['width'] = $small_img_info['width'];
                        $smallImageInfos[$k]['height'] = $small_img_info['height'];
                        $smallImageInfos[$k]['url'] = $small_img_info['url'];
                        $smallImageUrl[$k] = NormalUtil::buildImgUrl($image, 'small')['url'];
                        $bigImageUrl[$k] = $img_info['url'];
                    }
                }
            }
            // 美化图片
            if (!empty($subjectInfo['ext_info']['beauty_image'])) {
                $imageInfos = $subjectInfo['ext_info']['beauty_image'];
                if (is_array($imageInfos) && !empty($imageInfos)) {
                    foreach ($imageInfos as $key => $image) {
                        if(!empty($image['width'])){
                            $img_info = NormalUtil::buildImgUrl($image['url'],'watermark',$image['width'],$image['height']);
                            $imageUrl[$key]['width'] = $img_info['width'];
                            $imageUrl[$key]['height'] = $img_info['height'];
                            $imageUrl[$key]['url'] = $img_info['url'];
                            $smallImageUrl[$key] = NormalUtil::buildImgUrl($image['url'],'small')['url'];
                            $bigImageUrl[$key] = $img_info['url'];
                            $small_img_info = NormalUtil::buildImgUrl($image['url'],'koubeismall',$image['width'],$image['height']);
                            $smallImageInfos[$key]['width'] = $small_img_info['width'];
                            $smallImageInfos[$key]['height'] = $small_img_info['height'];
                            $smallImageInfos[$key]['url'] = $small_img_info['url'];
                        }
                    }
                }
            }
            if (!empty($subjectInfo['ext_info']['image']) && empty($subjectInfo['ext_info']['beauty_image'])) {
                $imageInfos = $subjectInfo['ext_info']['image'];
                if (is_array($imageInfos) && !empty($imageInfos)) {
                    foreach ($imageInfos as $key => $image) {
                        if(!empty($image['width'])){
                            $img_info = NormalUtil::buildImgUrl($image['url'],'watermark',$image['width'],$image['height']);
                            $imageUrl[$key]['width'] = $img_info['width'];
                            $imageUrl[$key]['height'] = $img_info['height'];
                            $imageUrl[$key]['url'] = $img_info['url'];
                            $smallImageUrl[$key] = NormalUtil::buildImgUrl($image['url'],'small')['url'];
                            $bigImageUrl[$key] = $img_info['url'];
                            $small_img_info = NormalUtil::buildImgUrl($image['url'],'koubeismall',$image['width'],$image['height']);
                            $smallImageInfos[$key]['width'] = $small_img_info['width'];
                            $smallImageInfos[$key]['height'] = $small_img_info['height'];
                            $smallImageInfos[$key]['url'] = $small_img_info['url'];
                        }
                    }
                }
            }
            $subjectRes[$subjectInfo['id']]['image_infos'] = $imageUrl;
            $subjectRes[$subjectInfo['id']]['small_image_url'] = $smallImageUrl;
            $subjectRes[$subjectInfo['id']]['image_url'] = $bigImageUrl;
            if (!empty($smallImageInfos[0])) {
                $subjectRes[$subjectInfo['id']]['smallImageInfos'] = $smallImageInfos[0];
            }
            if (!empty($smallImageInfos)) {
                $subjectRes[$subjectInfo['id']]['small_image_infos'] = $smallImageInfos;
            }
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
            if (in_array('item', $field)) {
                $subjectRes[$subjectInfo['id']]['items'] =  is_array($itemInfoById[$subjectId]) ? array_values($itemInfoById[$subjectId]) : array();
            }
            if (in_array('koubei', $field) && intval($subjectInfos[$subjectId]['koubei_id']) > 0) {
                $subjectRes[$subjectInfo['id']]['items'] =  is_array($itemInfoById[$subjectId]) ? array_values($itemInfoById[$subjectId]) : array();
            }
            if (in_array('share_info', $field)) {
                // 分享内容
                $shareConfig = \F_Ice::$ins->workApp->config->get('busconf.subject');
                $share = $shareConfig['groupShare'];
                if (!empty($albumArticles[$subjectInfo['id']])) { //专栏
                    $shareDefault = $shareConfig['defaultShareInfo']['album'];
                    $shareTitle = strlen($albumArticles[$subjectInfo['id']]['title']) > 0 ? $albumArticles[$subjectInfo['id']]['title'] . '_育儿头条_蜜芽 ' : $shareDefault['title'];
                    $shareDesc = $albumArticles[$subjectInfo['id']]['content'];
                    if (isset($albumArticles[$subjectInfo['id']]['cover_image']) && !empty($albumArticles[$subjectInfo['id']]['cover_image'])) {
                        $shareImage = $albumArticles[$subjectInfo['id']]['cover_image']['url'];
                    } else {
                        $shareImage = $shareDefault['img_url'];
                    }
                    $h5Url = sprintf($shareDefault['wap_url'], $albumArticles[$subjectInfo['id']]['id'], $albumArticles[$subjectInfo['id']]['album_id']);
                } elseif (!empty($subjectRes[$subjectInfo['id']]['video_info'])) {
                    $shareDefault = $shareConfig['defaultShareInfo']['video'];
                    $shareTitle = !empty($subjectInfo['title']) ? "【视频】{$subjectInfo['title']}_育儿头条_蜜芽" : $shareDefault['title'];
                    $shareDesc = !empty($subjectInfo['text']) ? $subjectInfo['text'] : $shareDefault['desc'];
                    if (isset($subjectRes[$subjectInfo['id']]['video_info']['cover_image']) && !empty($subjectRes[$subjectInfo['id']]['video_info']['cover_image'])) {
                        $shareImage = $subjectRes[$subjectInfo['id']]['video_info']['cover_image'];
                    } else {
                        $shareImage = $shareDefault['img_url'];
                    }
                    $h5Url = sprintf($shareDefault['wap_url'], $subjectInfo['id']);
                
                } else { //普通帖子
                    $shareDefault = $shareConfig['defaultShareInfo']['subject'];
                    $shareTitle = !empty($subjectInfo['title']) ? "【{$subjectInfo['title']}】 " : $shareDefault['title'];
                    $shareDesc = !empty($subjectInfo['text']) ? $subjectInfo['text'] : $shareDefault['desc'];
                    $shareImage = $shareDefault['img_url'];
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
    public function getSingleSubjectById($subjectId, $currentUid = 0, $field = array('count', 'group_labels', 'item', 'praise_info', 'album','share_info'), $dmSync = array(), $status = array(1, 2)) {
        $subjectInfo = $this->getBatchSubjectInfos(array($subjectId), $currentUid, $field, $status);
        $subjectInfo = $subjectInfo['data'][$subjectId];
        if (empty($subjectInfo)) {
            return $this->succ(array());
        }
        
        //作者信息
        $userInfo = $this->userService->getUserInfoByUserId($subjectInfo['user_id'], array("relation","count"), $currentUid)['data'];
        $subjectInfo['user_info'] = $userInfo;
        //评论信息
        $this->commentService = new CommentService();
        $commentInfo = $this->commentService->getCommentBySubjectId($subjectId, 0, 3)['data'];
        $subjectInfo['comment_info'] = $commentInfo;
        
        /*蜜芽帖、口碑贴相关逻辑开始*/
        if (in_array($subjectInfo['source'], array(1, 2)) && empty($subjectInfo['album_article'])) {
            //获取商品推荐
            if (in_array('item', $field)) {
                $itemRecommendService = new \mia\miagroup\Remote\RecommendItem($this->ext_params);
                if (!empty($subjectInfo['items'])) {
                    $relateItemIds = array();
                    foreach ($subjectInfo['items'] as $item) {
                        $relateItemIds[] = $item['item_id'];
                    }
                    $relateItemIds = count($relateItemIds) > 3 ? array_splice($relateItemIds, 0, 3) : $relateItemIds;
                    $itemIds = $itemRecommendService->getRecommedItemList('item', 9 - count($relateItemIds), $relateItemIds);
                } else {
                    $itemIds = $itemRecommendService->getRecommedItemList('home', 9);
                }
                $itemService = new \mia\miagroup\Service\Item();
                $ItemInfos = $itemService->getBatchItemBrandByIds($itemIds)['data'];
                $subjectInfo['relate_items'] = array_values($ItemInfos);
            }
            //获取相关帖子
            $noteRecommendService = new \mia\miagroup\Remote\RecommendNote($this->ext_params);
            $relatedIds = $noteRecommendService->getRelatedNote($subjectId);
            $relatedSubjects = $this->getBatchSubjectInfos($relatedIds, 0, array('user_info', 'count'))['data'];
            if (!empty($relatedSubjects)) {
                $subjectInfo['related_subject'] = array_values($relatedSubjects);
            }
        }
        /*蜜芽帖、口碑贴相关逻辑结束*/
        
        /*专栏、头条相关逻辑开始*/
        if (!empty($subjectInfo['album_article'])) { 
            $con = [
                'user_id'   => $subjectInfo['user_id'],
                'iPageSize' => 5,
            ];
            //如果是专栏，获取作者的其他专栏
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
            if (!empty($dmSync['refer_channel_id'])) {
                $dmSync['refer_subject_id'] = $subjectId;
                $headlineRemote = new HeadlineRemote();
                //阅读告知
                if (intval($currentUid) > 0) {
                    $uniqueFlag = $currentUid;
                } else { //不登录情况下用户的唯一标识
                    $uniqueFlag = $this->ext_params['dvc_id'] ? $this->ext_params['dvc_id'] : $this->ext_params['cookie'];
                }
                $headlineRemote->headlineRead($dmSync['refer_channel_id'], $subjectId, $uniqueFlag);
                //相关帖子
                $subjectIds = $headlineRemote->headlineRelate($dmSync['refer_channel_id'], $dmSync['refer_subject_id'], $uniqueFlag, 6);
                $recommendArticle = $this->getBatchSubjectInfos($subjectIds)['data'];
                $recommendArticle = array_values($recommendArticle);
                $subjectInfo['recommend_article'] = count($recommendArticle) > 5 ? array_slice($recommendArticle, 0, 5) : $recommendArticle;
            }
        }
        /*专栏、头条相关逻辑结束*/
        
        //阅读量计数
        if (in_array('view_num_record', $field)) {
            $this->subjectModel->viewNumRecord($subjectId);
        }
        return $this->succ($subjectInfo);
    }
    
    /**
     * 分页获取笔记的相关笔记
     */
    public function getRelatedNoteList($subjectId, $page = 1, $count = 10) {
        //获取相关帖子
        $noteRecommendService = new \mia\miagroup\Remote\RecommendNote($this->ext_params);
        $relatedIds = $noteRecommendService->getRelatedNote($subjectId, $page, $count);
        $relatedSubjects = $this->getBatchSubjectInfos($relatedIds, 0, array('user_info', 'count'))['data'];
        if (!empty($relatedSubjects)) {
            return $this->succ(array_values($relatedSubjects));
        } else {
            return $this->succ();
        }
    }
    
    /**
     * 批量获取用户发布的帖子数
     */
    public function getBatchUserSubjectCounts($userIds) {
        if (empty($userIds) || !is_array($userIds)) {
            return $this->succ(array());
        }
        $largePublishCountUser = \F_Ice::$ins->workApp->config->get('busconf.user.largePublishCountUser');
        $diffUserIds = array_diff($userIds, $largePublishCountUser);
        $data = $this->subjectModel->getBatchUserSubjectCounts($diffUserIds);
        if (array_intersect($userIds, $largePublishCountUser)) {
            foreach ($largePublishCountUser as $uid) {
                $data[$uid] = 10000;
            }
        }
        return $this->succ($data);
    }

    /**
     * 发布帖子
     * @param unknown $subjectInfo
     * @param unknown $pointInfo
     * @param unknown $labelInfos
     * @param unknown $koubeiId
     */
    public function issue($subjectInfo, $pointInfo = array(), $labelInfos = array(), $koubeiId = 0, $isValidate = 0) {
        if (empty($subjectInfo)) {
            return $this->error(500);
        }
        //判断登录用户是否是被屏蔽用户
        if(!empty($subjectInfo['user_info']['user_id'])){
            $audit = new \mia\miagroup\Service\Audit();
            $is_shield = $audit->checkUserIsShield($subjectInfo['user_info']['user_id'])['data'];
            if($is_shield['is_shield']){
                return $this->error(1104);
            }
        }
        //口碑不经过数美验证
        if ($isValidate == 1) {
            //启用数美验证
            if(!empty($subjectInfo['title'])){
                //过滤敏感词
                $sensitive_res = $audit->checkSensitiveWords($subjectInfo['title'], 1)['data'];
                if ($sensitive_res['code'] == 1127) {
                    return $this->error(1127);
                }
                if(!empty($sensitive_res['sensitive_words'])){
                    return $this->error(1112);
                }
                //过滤xss、过滤html标签
                $subjectInfo['title'] = strip_tags($subjectInfo['title'], '<span><p>');
            }
            if(!empty($subjectInfo['text'])){
                //过滤敏感词
                $sensitive_res = $audit->checkSensitiveWords($subjectInfo['text'], 1)['data'];
                if ($sensitive_res['code'] == 1127) {
                    return $this->error(1127);
                }
                if(!empty($sensitive_res['sensitive_words'])){
                    return $this->error(1112);
                }
                //过滤脚本
                $subjectInfo['text'] = strip_tags($subjectInfo['text'], '<span><p>');
            }
            //蜜芽圈标签
            if (!empty($labelInfos)) {
                $labelTitleArr = array_column($labelInfos, 'title');
                $labelStr = implode(',', $labelTitleArr);
                //过滤敏感词
                if(!empty($labelStr)){
                    $sensitive_res = $audit->checkSensitiveWords($labelStr, 1)['data'];
                    if ($sensitive_res['code'] == 1127) {
                        return $this->error(1127);
                    }
                    if(!empty($sensitive_res['sensitive_words'])){
                        return $this->error(1112);
                    }
                }
            }
        }
        
        $subjectSetInfo = array();
        $subjectSetInfo['active_id'] = 0;
        if (!isset($subjectInfo['user_info']) || empty($subjectInfo['user_info'])) {
            return $this->error(500);
        }
        if (strtotime($subjectInfo['created']) > 0) {
            $subjectSetInfo['created'] = $subjectInfo['created'];
        } else {
            $subjectSetInfo['created'] = date("Y-m-d H:i:s", time());
        }
        
        if (intval($subjectInfo['source']) > 0) {
            $subjectSetInfo['source'] = $subjectInfo['source'];
        }else{
            $subjectSetInfo['source'] = 1;
        }
        if (!empty($subjectInfo['ext_info'])) {
            $subjectSetInfo['ext_info'] = $subjectInfo['ext_info'];
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

        if (isset($subjectInfo['status'])) {
            $subjectSetInfo['status'] = $subjectInfo['status'];
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
        
        //只有当帖子带图的时候才能参加活动
        if(!empty($imgUrl)){
            $activeService = new ActiveService();
            $relationSetInfo = array();
            //如果参加活动，检查活动是否有效
            if (intval($subjectInfo['active_id']) > 0) {
                //获取活动信息
                $activeInfo = $activeService->getSingleActiveById($subjectInfo['active_id'])['data'];
                $currentTime = date("Y-m-d H:i:s",time());
                if(!empty($activeInfo) && $currentTime >= $activeInfo['start_time'] && $currentTime <= $activeInfo['end_time']){
                    $subjectSetInfo['active_id'] = $subjectInfo['active_id'];
                    $relationSetInfo['active_id'] = $subjectInfo['active_id'];
                }
            }
        
            //如果为普通发帖，检查标签中是否有参加在线活动的
            if(!empty($labelInfos) && intval($subjectInfo['active_id']) == 0){
                //获取在线的蜜芽圈活动
                $activeInfos = $activeService->getCurrentActive()['data'];
                foreach($activeInfos as $key=>$activeInfo){
                    //取帖子标签中参加活动的标签
                    if(!empty($activeInfo['ext_info'])){
                        $activeExtInfos = json_decode($activeInfo['ext_info'],true);
                        if(!empty($activeExtInfos['labels'])){
                            $activeLabelTitles = array_column($activeExtInfos['labels'], 'title');
                            $labelTitles = array_column($labelInfos, 'title');
                            $attendLabels = array_intersect($activeLabelTitles,$labelTitles);
                            //活动id为在线活动的索引(key)，如果标签中有参加活动的，则保存活动id
                            if(!empty($attendLabels)){
                                $subjectSetInfo['active_id'] = $key;
                                $relationSetInfo['active_id'] = $key;
                                break;
                            }
                        }
                    }
                }
            }
        }
        
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
                $subjectSetInfo['image_infos'][$key] = $image;
                $subjectSetInfo['image_infos'][$key]['url'] = NormalUtil::buildImgUrl($image['url'], 'watermark' , $image['width'] , $image['height'])['url'];
                $subjectSetInfo['small_image_url'][] = NormalUtil::buildImgUrl($image['url'], 'small')['url'];
            }
        }
        if ($koubeiId <= 0) { //口碑不再发蜜豆
            // 赠送用户蜜豆
            $mibean = new \mia\miagroup\Remote\MiBean();
            $param['user_id'] = $subjectSetInfo['user_id'];
            $param['relation_type'] = 'publish_pic';
            $param['relation_id'] = $subjectId;
            $param['to_user_id'] = $subjectSetInfo['user_id'];
            $mibean->add($param);
        }
        
        // 添加蜜芽圈标签
        if (!empty($labelInfos)) {
            $labelArr = array();
            foreach ($labelInfos as $key => $labelInfo) {
                $labelRelationSetInfo = array("subject_id" => $subjectId, "label_id" => 0, "create_time" => $subjectSetInfo['created'], "user_id" => $subjectInfo['user_info']['user_id']);
                if (isset($labelInfo['id']) && $labelInfo['id'] > 0) {
                    $labelRelationSetInfo['label_id'] = $labelInfo['id'];
                } else {
                    // 如果没有存在，则保存该自定义标签
                    $insertId = $this->labelService->addLabel($labelInfo['title'])['data'];
                    $labelRelationSetInfo['label_id'] = $insertId;
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

        //发布帖子同时，保存一份未同步到口碑的相关帖子信息，用于后台同步到口碑贴用
        $koubeiSubject = array();
        $koubeiSubject['subject_id'] = $subjectId;
        $koubeiSubject['user_id'] = $subjectSetInfo['user_id'];
        $koubeiSubject['is_audited'] = 0;
        $koubeiSubject['create_time'] = $subjectSetInfo['created'];
        $koubeiService = new KoubeiService();
        $koubeiService->addKoubeiSubject($koubeiSubject);
        
        //插入标记
        if(!empty($pointInfo[0])){
            foreach ($pointInfo as $itemPoint) {
                //插入帖子标记信息
                $this->tagsService->saveSubjectTags($subjectId,$itemPoint);
            }
        }
        
        //组装活动帖子关联表信息
        if(isset($relationSetInfo['active_id'])){
            $relationSetInfo['subject_id'] = $subjectId;
            $relationSetInfo['user_id'] = $subjectSetInfo['user_id'];
            $relationSetInfo['create_time'] = $subjectSetInfo['created'];
            $activeService->addActiveSubjectRelation($relationSetInfo);
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

            // 获取视频元信息
            $avInfo = $qiniusdk->getVideoFileInfo($videoInfo['video_origin_url']);
            if (!empty($avInfo['duration'])) {
                $videoInfo['video_time'] = $avInfo['duration'];
            }

            // 从七牛获取缩略图
            $qiniuConfig = F_Ice::$ins->workApp->config->get('busconf.qiniu');
            $second = floor($avInfo['duration']/2);
            $videoInfo['cover_image'] = $qiniusdk->getVideoThumb($qiniuConfig['video_host'] . $videoInfo['video_origin_url'], $second);

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
     * 更新帖子信息
     */
    public function updateSubject($subjectId, $subjectInfo) {
        $subject = $this->subjectModel->getSubjectByIds(array($subjectId), array());
        $subject = $subject[$subjectId];
        if (empty($subject)) {
            return $this->error('1107');
        }
        if (!empty($subjectInfo['ext_info'])) { //处理ext_info
            $extinfoField = \F_Ice::$ins->workApp->config->get('busconf.subject.extinfo_field');
            if (is_array($subjectInfo['ext_info'])) {
                $extinfo = $subject['ext_info'];
                foreach ($subjectInfo['ext_info'] as $k => $v) {
                    if (in_array($k, $extinfoField)) {
                        $extinfo[$k] = $v;
                    }
                }
                $subjectInfo['ext_info'] = json_encode($extinfo);
            }
        }
        $setData = array();
        if (is_array($subjectInfo)) {
            foreach ($subjectInfo as $k => $v) {
                $setData[] = [$k, $v];
            }
        }
        $this->subjectModel->updateSubject($setData, $subjectId);
        return $this->succ(true);
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
     * 根据用户ID获取帖子信息
     */
    public function getSubjectsByUid($userId,$currentId = 0, $page = 1, $iPageSize = 20){
        $data = array("subject_lists" => array(), "status" => 0);
        //校验是否是屏蔽用户
        $audit = new \mia\miagroup\Service\Audit();
        $isShieldStatus = $audit->checkUserIsShield($userId)['data'];
        if($isShieldStatus['is_shield']) {
            $data['status'] = -1;
            return $this->succ($data);
        }
        //获取帖子ID
        $subject_ids = $this->subjectModel->getSubjectInfoByUserId($userId,$currentId,$page,$iPageSize);
        if(empty($subject_ids)){
            return $this->succ($data);
        }
        $data['subject_lists'] = array_values($this->getBatchSubjectInfos($subject_ids,$currentId)['data']);
        $data['status'] = 1;
        
        return $this->succ($data);
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
        return $this->succ($subjectCountArr);
    }
    
    /**
     * 删除帖子
     */
    public function delete($subjectId,$userId){
        $status = array();
        $subjectInfo = $this->subjectModel->getSubjectByIds([$subjectId],$status)[$subjectId];
        if($subjectInfo['status'] == 0) {
            return $this->succ(true);
        }
        //删除帖子
        $result = $this->subjectModel->delete($subjectId, $userId);
        
        //如果是口碑，同时删除口碑
        if(!empty($subjectInfo['ext_info']['koubei']['id'])){
            $koubei = new \mia\miagroup\Service\Koubei();
            $koubei->delete($subjectInfo['ext_info']['koubei']['id'], $userId);
        }
        //检验帖子是否参加了活动
        $activeService = new ActiveService();
        $activeSubject = $activeService->getActiveSubjectBySids(array($subjectId));
        //如果参加了活动，删除活动帖子关联表记录
        if(!empty($activeSubject['data'][$subjectId])){
            $activeService->upActiveSubject(array('status'=>0), $activeSubject['data'][$subjectId]['id']);
        }
        
        if($result){
            return $this->succ(true);
        }else{
            return $this->error(6104);
        }
    }
    
    /**
     * 精选帖子
     */
    public function getRecommendsubject($userId, $iPage=1, $iPageSize=21){
        $data = ['label_lists'=>[],'subject_lists'=>[]];
        $subjectIds = $this->subjectModel->getRrecommendSubjectIds($iPage,$iPageSize);
        //获取推荐的标签
        if ($iPage == 1) {
            $labels = $this->labelService->getRecommendLabels($iPage, $iPageSize)['data'];
            $data['label_lists'] = array_values($labels);
        }
        if(!empty($subjectIds)) {
            $subjects = $this->getBatchSubjectInfos($subjectIds,$userId)['data'];
            $data['subject_lists'] = !empty($subjects) ? array_values($subjects) : array();
        }
        return $this->succ($data);
    }
    
    /**
     * 根据用户ID获取帖子信息
     */
    public function getSubjectDataByUserId($subjectId, $userId, $status = array(1,2)){
        $data = $this->subjectModel->getSubjectDataByUserId($subjectId, $userId, $status);
        return $data;
    }
    
    /**
     * 分享
     */
    public function share($sourceId, $userId, $type, $platform,$status){
        //记录分享
        $shareId = $this->subjectModel->addShare($sourceId, $userId, $type, $platform,$status);
//         if ($status == 1) {
//             #赠送用户蜜豆
//             $param['relation_type'] = 'share';
//             $param['user_id'] = $userId;
//             $param['to_user_id'] = $userId;
//             $param['relation_id'] = $shareId;
//             $param['mibean'] = 1;
//             $mibean = new \mia\miagroup\Remote\MiBean();
//             $mibean->add($param);
//         }
        return $this->succ($shareId);
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
    
    //查出某用户的所有帖子
    public function getSubjects($userId){
        if(!is_numeric($userId) || intval($userId) <= 0){
            return $this->error(500);
        }
        $arrSubjects = $this->subjectModel->getSubjectsByUid($userId);
        return $this->succ($arrSubjects);
    }
    
    /**
     * 删除或屏蔽帖子
     */
    public function delSubjects($subjectIds,$status,$shieldText=''){
        if(empty($subjectIds)){
            return $this->error(500);
        }
        //如果是一个字符串的帖子id，则转换成数组
        if(!is_array($subjectIds)){
            $subjectIds = array($subjectIds);
        }
        
        foreach($subjectIds as $subjectId){
            //查出帖子信息
            $subjectInfo = $this->subjectModel->getSubjectByIds(array($subjectId),array())[$subjectId];
            //判断该帖子是否被删除或屏蔽过，如果是，则无需处理
            if($subjectInfo['status'] == $status){
                continue;
            }
            //屏蔽或者删除帖子
            $result = $this->subjectModel->deleteSubjects(array($subjectId),$status,$shieldText);
            if(!empty($subjectInfo['ext_info']['koubei']['id']) && in_array($status,array(0,-1))){
                //删除口碑
                $koubei = new \mia\miagroup\Service\Koubei();
                $res = $koubei->delete($subjectInfo['ext_info']['koubei']['id'], $subjectInfo['user_id']);
            }
        }
        
        return $this->succ($result);
    }
    
    /**
     * 批量更新帖子的数量
     */
    public function updateSubjectComment($commentNumArr){
        if(empty($commentNumArr)){
            return $this->error(500);
        }
        $result = $this->subjectModel->updateSubjectComment($commentNumArr);
        return $this->succ($result);
    }

    /**
     * 根据subject_id修改视频首帧
     */
    public function changeVideoFrame($id)
    {
        if (empty($id)) {
            return;
        }
        $subject_info = $this->subjectModel->getSubjectByIds([$id]);
        if (empty($subject_info)) {
            return;
        }
        $qiniusdk = new QiniuUtil();
        $qiniuConfig = F_Ice::$ins->workApp->config->get('busconf.qiniu');
        if(!isset($subject_info[$id]['video_info']['video_origin_url'])) {
            return;
        }

        $video_id = $subject_info[$id]['video_info']['id'];
        //视频信息
        $info = $this->subjectModel->getBatchVideoExtInfos([$video_id]);

        $second = floor($info[$video_id]['ext_info']['video_time']/2);
        $cover_image = $qiniusdk->getVideoThumb($qiniuConfig['video_host'] . $subject_info[$id]['video_info']['video_origin_url'], $second);
        if(empty($cover_image)) {
            return;
        }
        //修改
        $ext_arr = $info[$video_id]['ext_info'];
        $ext_arr['cover_image'] = $cover_image;

        $setInfo[] = ['ext_info', json_encode($ext_arr)];
        $where[] = ['id', $video_id];
        $this->subjectModel->updateVideoBySubject($setInfo, $where);
        unset($where);
        unset($setInfo);
    }

    /**
     * 修改帖子内容
     */
    public function editSubject($type, $editData)
    {
        if (empty($type) || empty($editData) || !is_array($editData)) {

        }
        if ($type == 1) {
            //视频修改
            $setData = array();
            if (isset($editData['title'])) {
                $setData[] = ['title', $editData['title']];
            }
            $editRes = $this->subjectModel->updateSubject($setData, $editData['subject_id']);
            if (!$editRes) {
                return $this->error(20001);
            }
            return $this->succ($editRes);
        } elseif ($type == 2) {
            //专栏修改
            $setData = array();
            if (isset($editData['user_id'])) {
                $data['con']['user_id'] = $editData['user_id'];
            }
            if (isset($editData['album_id'])) {
                $data['con']['album_id'] = $editData['album_id'];
            }
            if (isset($editData['id'])) {
                $data['con']['id'] = $editData['id'];
            }
            if (isset($editData['subject_id'])) {
                $data['con']['subject_id'] = $editData['subject_id'];
            }
            if (isset($editData['title'])) {
                $data['set']['title'] = $editData['title'];
            }
            $editRes = $this->albumService->updateAlbumArticle($data);
            if (!$editRes) {
                return $this->error(20001);
            }
            return $this->succ($editRes);
        } else {
            return $this->error(500);
        }
    }

    /**
     * 帖子搜索
     */
    public function getSearchInfos($keyword, $type, $page = 1)
    {
        if (empty($keyword) || empty($type)) {
            return $this->error(500);
        }
        $start = ($page -1)*10;
        $rows = 10;
        $subjectData = $this->headlineRemote->subjectList($keyword, $type, $start, $rows);
        return $this->succ($subjectData);
    }
    

    /**
     * UMS
     * 批量设置帖子置顶/取消置顶
     * @param unknown $subjectIds
     */
    public function setSubjectTopStatus($subjectIds,$status=1){
        if($status == 1){
            //帖子加精并置顶数不能超过5个
            $num = $this->subjectModel->getSubjectTopNum();
            if($num >= 5){
                return $this->error(90006,'帖子置顶的数量超过限制！');
            }
        }
        $affect = $this->subjectModel->setSubjectTopStatus($subjectIds,$status);
        return $this->succ($affect);
    }
    
    /**
     * UMS
     * 加入推荐池
     */
    public function addRecommentPool($subjectIds,$dateTime){
        $inser_id = $this->subjectModel->addRecommentPool($subjectIds, $dateTime);
        return $this->succ($inser_id);
    }
    
    /**
     * UMS
     * 取消推荐
     */
    public function cacelSubjectIsFine($subjectId){
        //取消推荐专栏合集
        $albumService = new \mia\miagroup\Service\Album();
        $albumService->cacelRecommentBySubjectId($subjectId)['code'];
        $affect = $this->subjectModel->cacelSubjectIsFine($subjectId);
        return $this->succ($affect);
    }
    
    //获取某活动下的所有/精华帖子(该方法迁到活动服务里了)
    public function getActiveSubjects($activeId, $type='all', $currentId = 0, $page=1, $limit=20){
        $data = array('subject_lists'=>array());
        $subjectIds = $this->subjectModel->getSubjectIdsByActiveId($activeId, $type, $page, $limit);

        if(!empty($subjectIds)) {
            $subjects = $this->getBatchSubjectInfos($subjectIds,$currentId)['data'];
            $data['subject_lists'] = !empty($subjects) ? array_values($subjects) : array();
        }
        return $this->succ($data);
    }
    
    /**
     * 新增运营笔记
     */
    public function addOperateNote($noteInfo)
    {

        if(empty($noteInfo) || !is_array($noteInfo)){
            return $this->error(500);
        }

        $relation_type_fields = $this->config['operate_note_fields'];
        $special_fields  = $this->config['operate_note_ext_fields'];

        //relation_type校验
        if(!in_array($noteInfo['relation_type'], $relation_type_fields)){
            return $this->error(500);
        }

        $setData = array();
        foreach($noteInfo as $key => $value) {
            if(in_array($key, $special_fields)) {
                $setData['ext_info'][$key] = $value;
            }else {
                $setData[$key] = $value;
            }
        }
        $data = $this->subjectModel->addOperateNote($setData);
        return $this->succ($data);
    }
    
    /**
     * 编辑运营笔记
     */
    public function editOperateNote($noteId, $noteInfo) {

        if (empty($noteId)) {
            return $this->error(500);
        }
        $noteDetail = $this->subjectModel->getNoteInfoById($noteId);

        if (empty($noteDetail)) {
            return $this->error(500);
        }

        $special_fields  = $this->config['operate_note_ext_fields'];

        $setData = array();
        foreach($noteInfo as $key => $value) {
            if(in_array($key, $special_fields)) {
                $setData['ext_info'][$key] = $value;
            }else {
                $setData[] = [$key, $value];
            }
        }

        if (is_array($setData['ext_info']) && !empty($setData['ext_info'])) {
            $setData[] = ['ext_info',json_encode($setData['ext_info'])];
            unset($setData['ext_info']);
        }

        $data = $this->subjectModel->editOperateNote($noteId, $setData);
        return $this->succ($data);
    }
    
    /**
     * 删除运营笔记
     */
    public function delOperateNote($id) {

        if(empty($id)){
            return $this->error(500);
        }
        $data = $this->subjectModel->delOperateNote($id);
        return $this->succ($data);
    }
    
    /**
     * 通过relation_id/type获取运营笔记
     */
    public function getOperateHeadlineByRelationID($relation_id, $relation_type) {

        $relation_type_fields = $this->config['operate_note_fields'];
        if (empty($relation_id) || !in_array($relation_type, $relation_type_fields)) {
            return false;
        }
        $data = $this->subjectModel->getOperateNoteByRelationId($relation_id, $relation_type);
        return $this->succ($data);
    }
    
    /**
     * 获取蜜芽圈笔记推广位列表
     * @param $tabId
     * @param $page
     */
    public function getOperationNoteList($tabId=1, $page = 1)
    {
        //发现列表，增加运营广告位
        $res = array();
        $operationNoteData = $this->subjectModel->getOperationNoteData($tabId, $page, 'all');
        $operationNoteIds = array_keys($operationNoteData);
        $res['content_lists'] = $this->formatNoteData($operationNoteIds,$operationNoteData);
        return $this->succ($res);
    }
    
}