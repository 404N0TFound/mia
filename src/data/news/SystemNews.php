<?php

namespace mia\miagroup\Data\News;

use \DB_Query;

class SystemNews extends DB_Query
{
    public $dbResource = 'mianews';
    public $tableName = 'system_news';
    public $mapping = [];
}