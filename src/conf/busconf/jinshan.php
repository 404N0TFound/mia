<?php

//金山密钥
$access_key = 'viyItWWbR0VgFI9ya06O';
$secret_key = '232uA/VLWjFwbSZkW13UsYUnI+BSaVcbzXdyr40u';
$endpoint   = 'ks3-cn-beijing.ksyun.com';

$live_host = [
    'publish' => [
        'rtmp' => 'publish.live2.miyabaobei.com'
    ],
    'live'    =>[
        'rtmp' => 'rtmp.live2.miyabaobei.com',
        'hls'  => 'hls.live2.miyabaobei.com',
        'hdl'  => 'hdl.live2.miyabaobei.com',
    ]
 ];
$live_paly_back = 'video2.miyabaobei.com';
$live_snap_shot = 'mia-image.ks3-cn-beijing.ksyun.com';

$live_prefix = 'z2live';

$live_bucket = 'mialive';
$image_bucket = 'mia-image';

$live_url = 'video2.miyabaobei.com';