<?php
$namespace = 'mia\miagroup';
$app_class = '\\Ice\\Frame\\App';

$root_path = __DIR__ . '/..';
$var_path  = $root_path . '/../var';
$run_path  = $var_path . '/run';
$log_path  = $var_path . '/logs';

//聊天室消息记录日志
$chatroom_log_path = $run_path.'/live';

@include(__DIR__ . '/web.inc');
@include(__DIR__ . '/service.inc');
@include(__DIR__ . '/daemon.inc');

$runner = array(
    'web' => array(
        'frame'       => $web_frame,
        'routes'      => $web_routes,
        'temp_engine' => $web_temp_engine,
        'log'         => $web_logger,
        'filter'      => $web_filter,
    ),
    'service' => array(
        'log'    => $service_logger,
        'filter' => $service_filter,
    ),
    'daemon' => array(
        'log'    => $daemon_logger,
        'filter' => $daemon_filter,
    ),
);

$url = [
    'img_url'=>'https://img.miyabaobei.com/',
    'qiniu_url'=>'https://video1.miyabaobei.com/',
];