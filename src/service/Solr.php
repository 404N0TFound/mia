<?php
namespace mia\miagroup\Service;


class Solr extends \mia\miagroup\Lib\Service {

    public $solr;
    public $options;
    public $solrConfig;

    public function __construct() {
        $this->solrConfig = \F_Ice::$ins->workApp->config->get('solr.solr');
        $this->solr = new \SolrClient($this->solrConfig['test']);
    }

    public function koubeiList(){

    }

}
