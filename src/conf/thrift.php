<?php
$address = array(
    'MiBean' => array(
        'addresses' => array(
            '172.16.104.182:3333',
        ),
    ),
    'api' => array(
        'host' => '172.16.104.185',
        'port' => 81,
        'service_name' => 'coupon_api_service',
        'recv_timeout' => 20000
    ),

    'solr' => array(
        'default' => array(
            'host' => '172.16.96.75',
            'port' => 9001,
            'path' => 'solr',
            'core' => 'koubei'
        ),
    ),

    'headline' => [
        //'remote' => 'http://alpha_stage.recommend.mia.com/api/',
        'remote' => 'zhanghaibo_dev.recommend.mia.com/api/',
    ],

    'subject' => [
        'remote' => 'http://172.16.96.75:8983/solr/toutiao/select',
    ],

);
    