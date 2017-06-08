<?php

namespace mia\miagroup\Daemon\Temp;

use mia\miagroup\Data\News\AppNewsInfo as NewsData;

use mia\miagroup\Data\News\SystemNews as SystemNewsData;
use mia\miagroup\Data\News\UserNews as UserNewsData;
use mia\miagroup\Service\News as NewsService;


/**
 * 导入历史消息
 * Class ImportNews
 * @package mia\miagroup\Daemon\Temp
 */
class ImportNews extends \FD_Daemon
{
    public function __construct()
    {
        $this->limit = 500;
        $this->runPath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $this->config = \F_Ice::$ins->workApp->config->get('busconf.news');
        //创建记录文件，maxId
        if (!file_exists($this->runPath . "/lock")) {
            touch($this->runPath . "/lock");
            $f = fopen($this->runPath . "/lock", "r+");
            fwrite($f, "0");
            fclose($f);
        }
    }

    public function execute()
    {
        if (!file_exists($this->runPath . "/lock")) {
            exit;
        }
        $systemNewsData = new SystemNewsData();
        $newsService = new NewsService();
        $newsData = new NewsData;

        while (true) {
            $fp = fopen($this->runPath . "/lock", "r+");
            //进行排它型锁定，防止多个进程同时读文件，阻塞到获取锁
            if (flock($fp, LOCK_EX)) {
                //读取maxId
                $maxId = fread($fp, 1024);
                //操作数据库
                if ($maxId) {
                    $condition = " AND id > $maxId ";
                } else {
                    $condition = " ";
                }
                $sql = "SELECT * FROM app_user_news WHERE 1=1" . $condition . "ORDER BY id ASC LIMIT " . $this->limit;
                $userNewsInfo = $newsData->query($sql);
                if (empty($userNewsInfo)) {
                    break;
                }
                //获取maxId
                $userNewsIds = [];
                array_map(function ($v) use (&$userNewsIds) {
                    $userNewsIds[] = $v["id"];
                }, $userNewsInfo);
                $newMaxId = max($userNewsIds);

                //清空文件内容
                fseek($fp, 0, SEEK_SET);
                ftruncate($fp, 0);
                //写入最新maxId
                fwrite($fp, $newMaxId);
                //释放锁
                flock($fp, LOCK_UN);
                fclose($fp);
            }

            //查询系统消息
            $newsIds = [];
            foreach ($userNewsInfo as $val) {
                $newsIds[] = $val["news_id"];
            }
            $where = [];
            $where[] = ["id", $newsIds];
            $newsInfo = $newsData->getRows($where);

            $newsArr = [];
            foreach ($newsInfo as $v) {
                $newsArr[$v["id"]] = $v;
            }
            unset($newsInfo);
            //插入新数据
            foreach ($userNewsInfo as $userNews) {
                $type = $newsArr[$userNews["news_id"]]['type'];
                $resource_type = $newsArr[$userNews["news_id"]]['resource_type'];
                $resource_sub_type = $newsArr[$userNews["news_id"]]['resource_sub_type'];

                if ($resource_type == "group" && $resource_sub_type == "coupon") {
                    $resource_sub_type = "group_coupon";
                }
                if ($resource_type == "group" && $resource_sub_type == "custom") {
                    $resource_sub_type = "group_custom";
                }
                $newType = $resource_sub_type;

                if (!in_array($newType, $this->config["all_type"])) {
                    continue;
                }

                $where = [];
                $where[] = ["id", $userNews["news_id"]];
                $systemNews = $systemNewsData->getRows($where);

                //插系统消息表，群发的消息才需要插入
                if (in_array($type, ['all', 'group']) && empty($systemNews) && !empty($newsArr[$userNews["news_id"]])) {
                    $systemInsert["id"] = $userNews["news_id"];
                    $systemInsert['news_type'] = $newType;
                    $systemInsert['send_user'] = $newsArr[$userNews["news_id"]]['send_from_id'];
                    $systemInsert['send_time'] = $newsArr[$userNews["news_id"]]['valid_time'];
                    $systemInsert['abandon_time'] = date("Y-m-d H:i:s", strtotime($newsArr[$userNews["news_id"]]['valid_time']) + 30 * 24 * 3600);
                    //标题，图片，内容，url
                    if (!empty($newsArr[$userNews["news_id"]]['custom_title'])) {
                        $ext_arr["title"] = $newsArr[$userNews["news_id"]]['custom_title'];
                    }
                    if (!empty($newsArr[$userNews["news_id"]]['content'])) {
                        $ext_arr["content"] = $newsArr[$userNews["news_id"]]['content'];
                    }
                    if (!empty($newsArr[$userNews["news_id"]]['custom_photo'])) {
                        $ext_arr["photo"] = $newsArr[$userNews["news_id"]]['custom_photo'];
                    }
                    if (!empty($newsArr[$userNews["news_id"]]['custom_url'])) {
                        $ext_arr["url"] = $newsArr[$userNews["news_id"]]['custom_url'];
                    }
                    if (!empty($newsArr[$userNews["news_id"]]['resource_id'])) {
                        $ext_arr["source_id"] = $newsArr[$userNews["news_id"]]['resource_id'];
                    }
                    if (!empty($ext_arr)) {
                        $systemInsert['ext_info'] = json_encode($ext_arr);
                        unset($ext_arr);
                    }
                    $systemInsert['status'] = $newsArr[$userNews["news_id"]]['status'];
                    $systemInsert['create_time'] = $newsArr[$userNews["news_id"]]['created'];

                    $res = $systemNewsData->addSystemNews($systemInsert);
                    unset($systemInsert);
                }
                //插用户信息表
                $content_info = [];
                $content_info["id"] = $userNews["id"];
                if (!in_array($type, ['single'])) {
                    $content_info["news_id"] = $userNews["news_id"];//系统消息(批量消息)才有，单发的消息没有
                }
                //个人消息的自定义内容记录在ext_info里面，系统小心在系统消息表里面
                if (in_array($type, ['single'])) {
                    if (!empty($newsArr[$userNews["news_id"]]['custom_title'])) {
                        $content_info["title"] = $newsArr[$userNews["news_id"]]['custom_title'];
                    }
                    if (!empty($newsArr[$userNews["news_id"]]['content'])) {
                        $content_info["content"] = $newsArr[$userNews["news_id"]]['content'];
                    }
                    if (!empty($newsArr[$userNews["news_id"]]['custom_photo'])) {
                        $content_info["photo"] = $newsArr[$userNews["news_id"]]['custom_photo'];
                    }
                    if (!empty($newsArr[$userNews["news_id"]]['custom_url'])) {
                        $content_info["url"] = $newsArr[$userNews["news_id"]]['custom_url'];
                    }
                }
                $content_info["is_read"] = $userNews['is_read'];
                $content_info['status'] = $userNews["status"];
                $content_info['create_time'] = $userNews["created"];

                $res = $newsService->addUserNews($newType, $newsArr[$userNews["news_id"]]['send_from_id'], $userNews['user_id'], $newsArr[$userNews["news_id"]]['resource_id'], $content_info);
                unset($systemInsert);
            }
        }
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}