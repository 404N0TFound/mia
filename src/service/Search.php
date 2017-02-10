<?php
namespace mia\miagroup\Service;

use \mia\miagroup\Lib\Service;

/** 蜜芽圈搜索服务类
 * Class Search
 * @package mia\miagroup\Service
 */
class Search extends Service
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 搜索
     * @param $keyWords
     * @param $type 1.笔记搜索。2.用户搜索。3.商品搜索。
     * @return mixed
     */
    public function search($keyWords, $type)
    {
        return $this->succ();
    }

    public function noteHotWordsList()
    {
        //可以不过服务直接在api里写
    }

    public function userHotList()
    {
        //推荐池里选
    }

    public function itemHotWordsList()
    {
        //可以不过服务直接在api里写
    }
}