<?php
$address = array(
    'MiBean' => array(
        'addresses' => array(
            '172.16.104.182:3333',
        ),
    ),
    'api' => array(
        'host' => '172.16.96.38',
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
        'online_slave' => array(
            'host' => '10.1.51.24',
            'port' => 8983,
            'path' => 'solr',
            'core' => 'koubei'
        ),
        'online' => array(
            'host' => '10.1.117.10',
            'port' => 8983,
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

    'subject_sync' => 'root@10.1.15.14:/opt/article_in_mia/',
);
    