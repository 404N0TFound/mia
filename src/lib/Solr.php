<?php
namespace mia\miagroup\Lib;

class Solr {
    public $solr_server;
    public $solr;
    public $path;

    public function __construct($cluster='miagroup/default'){
        $dsn = 'solr://'.$cluster;
        $this->solr_server = \F_Ice::$ins->workApp->proxy_resource->get($dsn);
    }

    /**
     * solr 封装参数
     */
    public function handle_transfer_parameters($data = array())
    {
        // 组装URL
        // return url
    }

}