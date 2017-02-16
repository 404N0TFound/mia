<?php
namespace mia\miagroup\Remote;

class Search
{
    public function __construct()
    {
        //获取配置信息
    }

    /**
     * 笔记搜索
     * @param $keyWords
     * @param $page
     * @param $count
     * @return array
     */
    public function noteSearch($keyWords, $page = 1, $count = 20)
    {
        return ['267344', '267343', '267342', '267341', '267339', '267338', '267337'];
    }

    /**
     * 用户搜索
     * @param $keyWords
     * @param $page
     * @param $count
     * @return array
     */
    public function userSearch($keyWords, $page = 1, $count = 20)
    {
        return ["220103494", "1508587", "7509605", "7509576", "7509596", "7509608", "7509603", "7509614", "7509571", "7509569"];
    }
}