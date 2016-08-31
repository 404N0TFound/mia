<?php 
namespace mia\miagroup\Daemon\Temp;

use mia\miagroup\Data\Subject\Subject;
use mia\miagroup\Service\Label;
use mia\miagroup\Data\Label\SubjectLabelRelation;

class ActiveSubject extends \FD_Daemon {
    
    public function execute() {
        $activeLabelRelation = array(
            '孕期护肤品' => array(419, 209, 443),
            '待产包清单' => array(289),
            '宝宝帽' => array(415),
            '第一件玩具' => array(167),
            '好好吃饭' => array(302,297),
            '宝贝出行座驾' => array(248,389,368,458),
            '最爱的奶瓶' => array(258,278,370),
            '睡得香香好物' => array(176,171,307,342,269,357,406,392,391,401,436),
            '我的母乳喂养' => array(133,239,447),
            '养娃神器' => array(170,193,254,396,382,362,411,243),
            '宝宝小药箱' => array(435,385),
            '宝宝的第一双鞋' => array(311,344),
            '卡哇伊爬服' => array(155,446),
            '宝宝爱吃面' => array(157),
            '冬季宝宝护肤' => array(257),
            '牙胶安度磨牙期' => array(144,264,360),
            '爱上洗手的小物' => array(150),
            '洗澡玩具' => array(199),
            '安抚公仔就差它' => array(363,371),
            '花样餐具' => array(148,169,206,319),
            '好好喝水' => array(152,377,211,160,451),
            '学步装备' => array(311,313),
            '早教玩这些' => array(214,365,442),
            '爱上安全座椅' => array(215),
            '夏季降温妙物' => array(415,156),
            '腰凳' => array(141,166,305,437),
            '夏日防晒' => array(423),
            '敏感肌宝宝护肤' => array(265),
            '按摩油乳液' => array(200,347,292),
            '柔软亲肤好物' => array(314,274,403,432,433,387),
            '万用妈咪包' => array(412,441,393,449),
            '家家必备早教机' => array(233),
            '保护小乳牙' => array(213,440,288),
            '宝宝洗护好物' => array(195,386,414,426),
            '宝贝的贴身内衣' => array(201,321,431),
            '宝宝如厕神器' => array(163),
            '不再画地图' => array(188),
            '音乐玩具' => array(266),
            '装扮超萌婴儿房' => array(343),
            '出行装备' => array(183,359,355,388,389,368,434,410),
            '出街LOOK' => array(162,181,182,230,259,290,320,390,380,438,459),
            '萌娃家居服' => array(358),
            '玩具总动员' => array(282,424,442,367,322),
            '我家的绘本' => array(249,205,356,341,407,453),
            '冬季温暖穿搭' => array(202,241,272,273,280),
            '积木大赏' => array(212,287),
            '最拉风玩具车' => array(149,172,458),
            '家庭游乐园' => array(142,404),
            '亲子游不可缺' => array(225,228,208,328,359,361,355,388,434,462),
            '上学郎的装备' => array(352),
            '我是小画家' => array(221,255),
            '大人也爱的创意玩具' => array(194),
            '手工DIY' => array(189),
            '宝宝戏水装备' => array(146),
            '小公举最爱' => array(444,250),
            '运动玩具' => array(324),
            '宝宝鞋' => array(216,268,379,445),
            '冬季保暖神器' => array(318),
            '夏季凉爽穿搭' => array(173),
            '春装搭配' => array(348,366,429,416,398),
            '产后恢复必备' => array(303),
            '护肤笔记' => array(197,270,276,236,384,364),
            '美妆功课' => array(417,457,455,450),
            '吃出美丽' => array(296),
            '减肥瘦身' => array(336),
            '贵妇梳妆台' => array(349),
            '防晒' => array(420),
            '妈咪化妆包' => array(399),
            '去毛躁护发' => array(378),
            '美容小神器' => array(323,425),
            '妈妈的“手护神”' => array(263),
            '一言不合买口红' => array(293,395),
            '护发安利好货' => array(164,284),
            '“包”治百病' => array(234),
            '大姨妈驾到' => array(246,240,383),
            '美白' => array(413),
            '只买贵的' => array(143,191,351,397,292),
            '美厨娘选什么' => array(381,232,315),
            '囤年货' => array(312,325),
            '营养品大全' => array(350,408,452),
            '对抗雾霾' => array(281,373),
            '贴身衣物洗护' => array(291,454),
            '衣服洗白白' => array(177,192,321),
            '舒适家离不开' => array(335,376,405,295,397,456),
            '玩美收纳控' => array(405,295),
            '晒日货' => array(147,224),
            '美国货集合' => array(223),
            '台湾好货' => array(218),
            '思密达好物' => array(251,220,428),
            '欧洲货集合' => array(222),
            '星二代的育娃神器' => array(190),
            '海淘爆款' => array(196,250,256),
            '辅食笔记' => array(260,339,304,374,427),
            '春季辅食' => array(339),
            '背奶妈妈' => array(239,447),
            '家有二宝' => array(243),
            '湿疹攻略' => array(231,375),
            '妈妈互帮' => array(116),
            '宝宝发烧' => array(116),
            '冬季辅食' => array(260),
            '圣诞节' => array(283,286),
            '育儿手记' => array(154,217),
            '致敬奶爸' => array(145),
            '下厨房' => array(174),
            '花式晒娃' => array(310),
            '月子食谱' => array(242),
            '下午茶美味' => array(158,400,461),
            '过新年' => array(320,294,327,332,330,331),
            '吃货' => array(285,317,329,374,409,400,346),
            '蜜芽周年庆' => array(238,235),
        );
        
        $subjectData = new Subject();
        $labelService = new Label();
        $relationData = new SubjectLabelRelation();
        foreach ($activeLabelRelation as $label => $activeIds) {
            $where = array();
            $where[] = ['active_id', $activeIds];
            $subjects = $subjectData->getRows($where, 'id, created, user_id');
            foreach ($subjects as $subjectSetInfo) {
                $subjectId = $subjectSetInfo['id'];
                $labelRelationSetInfo = array("subject_id" => $subjectId, "label_id" => 0, "create_time" => $subjectSetInfo['created'], "user_id" => $subjectSetInfo['user_id']);
                $labelResult = $labelService->checkIsExistByLabelTitle($label)['data'];
                if (empty($labelResult)) {
                    $insertId = $labelService->addLabel($label)['data'];
                    $labelRelationSetInfo['label_id'] = $insertId;
                } else {
                    $labelRelationSetInfo['label_id'] = $labelResult['id'];
                }
                $cond = array();
                $cond[] = ['subject_id', $subjectId];
                $cond[] = ['label_id', $labelRelationSetInfo['label_id']];
                $data = $relationData->getRow($cond);
                if (empty($data)) {
                    $labelService->saveLabelRelation($labelRelationSetInfo)['data'];
                }
            }
            
        }
    }
}