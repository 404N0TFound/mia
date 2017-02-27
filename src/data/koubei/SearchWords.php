<?php
namespace mia\miagroup\Data\Koubei;

class SearchWords extends \DB_Query
{
    protected $tableName = 'app_search_word';
    protected $dbResource = 'miadefault';
    protected $mapping = [];

    /*
     * 查询通用的search_key
     */
    public function getNoteSearchKey()
    {
        $field = "search_text as key_word,hot_word as recommend,show_doc,show_red";
        $order_by = 'hot_word desc, sort_num desc';
        $where[] = ['type', 1];
        $limit = 20;
        $searchKeyData = $this->getRows($where, $field, $limit, 0, $order_by);
        if (empty($searchKeyData)) {
            return [];
        } else {
            return $searchKeyData;
        }
    }
}