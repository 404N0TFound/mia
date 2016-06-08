<?php
namespace mia\miagroup\Model;
class User extends \DB_Query {
    protected $tableName = 'user';
    protected $mapping   = array(
        'id'       => 'i',
        'name'     => 's',
        'passwd'   => 's',
        'location' => 's',
    );
    protected $dbResource = 'demo';
}
