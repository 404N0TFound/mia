<?php

namespace mia\miagroup\Data\News;

use \DB_Query;

class UserNews extends DB_Query
{
    public $dbResource = 'mianews';
    public $mapping = [];

    private $shard_field = 'user_id';//分表字段

    public function __construct()
    {

    }

}