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
     * @return array
     */
    public function noteSearch($keyWords)
    {
        return [];
    }

    /**
     * 用户搜索
     * @param $keyWords
     * @return array
     */
    public function userSearch($keyWords)
    {
        return [];
    }

    /**
     * 商品搜索
     * @param $keyWords
     * @return array
     */
    public function itemSearch($keyWords)
    {
        return [];
    }
}