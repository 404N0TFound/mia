<?php
namespace mia\miagroup\Model;

use mia\miagroup\Data\Search\SearchWords as SearchWordsData;


class Search
{

    private $searchWordsData;

    public function __construct()
    {
        $this->searchWordsData = new SearchWordsData();
    }

    /*
     * 查询通用的search_key
     */
    public function getNoteSearchKey()
    {
        $searchKeys = $this->searchWordsData->getNoteSearchKey();
        return $searchKeys;
    }

}