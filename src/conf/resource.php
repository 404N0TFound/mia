<?php
$mapping = array(
    'connector' => array(),
    'handler'   => array(),
    'strategy'  => array(),
);
$pool = array(
    'mysqli' => array(
        'options' => array(
            'deny_empty_update_delete' => TRUE,
            'warn_sql_length' => 51200,
            'fatal_sql_length' => 2097152,
        ),
        'miagroup' => array(
            'master' => array(
                array(
                    'host' => '172.16.104.207', 
                    'port' => 3307, 
                    'options' => array(
                        'user' => 'write_user', 
                        'passwd' => 'write_pwd'
                    ),
                )
            ),
            'slave' => array(
                array(
                    'host' => '172.16.104.207', 
                    'port' => 3307, 
                    'options' => array(
                        'user' => 'write_user', 
                        'passwd' => 'write_pwd'
                    ),
                )
            ),
            'options' => array(
                'timeout' => 1,
                'dbname'  => 'mia_group_test',
                'charset' => 'utf8',
            ),
        ),
        'miadefault' => array(
            'master' => array(
                array(
                    'host' => '172.16.104.207',
                    'port' => 3307,
                    'options' => array(
                        'user' => 'write_user',
                        'passwd' => 'write_pwd'
                    ),
                )
            ),
            'slave' => array(
                array(
                    'host' => '172.16.104.207',
                    'port' => 3307,
                    'options' => array(
                        'user' => 'write_user',
                        'passwd' => 'write_pwd'
                    ),
                )
            ),
            'options' => array(
                'timeout' => 1,
                'dbname'  => 'mia_test2',
                'charset' => 'utf8',
            ),
        ),
        'mianews' => array(
            'master' => array(
                array(
                    'host' => '172.16.104.207',
                    'port' => 3307,
                    'options' => array(
                        'user' => 'write_user',
                        'passwd' => 'write_pwd'
                    ),
                )
            ),
            'slave' => array(
                array(
                    'host' => '172.16.104.207',
                    'port' => 3307,
                    'options' => array(
                        'user' => 'write_user',
                        'passwd' => 'write_pwd'
                    ),
                )
            ),
            'options' => array(
                'timeout' => 1,
                'dbname'  => 'mia_test2',
                'charset' => 'utf8',
            ),
        ),
        'log' => array(
            'master' => array(
                array(
                    'host' => '10.1.3.201',
                    'port' => 3306,
                    'options' => array(
                        'user' => 'apilog_write',
                        'passwd' => 'apilog_write'
                    ),
                )
            ),
            'slave' => array(
                array(
                    'host' => '10.1.3.203',
                    'port' => 3306,
                    'options' => array(
                        'user' => 'apilog_read',
                        'passwd' => 'apilog_read'
                    ),
                )
            ),
            'options' => array(
                'timeout' => 1,
                'dbname'  => 'log',
                'charset' => 'utf8',
            ),
        ),
    ),
    'curl' => array(
        'service' => array(
            'default' => array(
                array('host' => 'service.host.com') // 修改为service的host
            ),
        ),
    ),
    'redis'=>array(
        'miagroup'=>array(
            'default'=>array(
                array(
                    'host'=>'172.16.104.236',
                    'port'=>6379,
                ),
            ),
        ),
        'redbag'=>array(
            'default'=>array(
                array(
                    'host'=>'10.1.50.187',
                    'port'=>6379,
                ),
            ),
            'options' => array(
                'timeout' => 2,
                'persistent'  => false,
        	),
        ),
    ),
    'rabbitmq'=>array(
        'miagroup'=>array(
            'default'=>array(
                array(
                    'host'    => '172.16.104.236',
                    'port'    => 5672,
                    'vhost'   => '/',
                    'options' => array(
                        'user'   => 'yue',
                        'passwd' => '123qwe',
                    ),
                ),
            ),
        ),
    ),
);
