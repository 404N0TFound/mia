<?php
$pool = array(
    'demo-local' => array(
        'proxy'  => 'local',
        'config' => array(
            'project_group' => 'mia',
            'project_name'  => 'miagroup',
        ),
    ),
    'demo-remote' => array(
        'proxy'  => 'remote',
        'config' => array(
            'resource' => 'curl://service',
        ),
    ),
);
