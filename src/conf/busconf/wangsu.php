<?php
$live_host = [
    'publish' => [
        //rtmp://wangsu-publish-rtmp.live.miyabaobei.com/wslive/流名
        'rtmp' => 'rtmp://wangsu-publish-rtmp.live.miyabaobei.com/wslive',
    ],
    'live' => [
        //rtmp:// wangsu-live-rtmp.live.miyabaobei.com/wslive/流名
        'rtmp' => 'rtmp://wangsu-live-rtmp.live.miyabaobei.com/wslive',
        //'hls'  => 'http://wangsu-live-hls.live.miyabaobei.com/wslive/流名/playlist.m3u8',
        'hls' => 'http://wangsu-live-hls.live.miyabaobei.com/wslive',
        //http://wangsu-live-hdl.live.miyabaobei.com/wslive/流名.flv
        'hdl' => 'http://wangsu-live-hdl.live.miyabaobei.com/wslive',
    ],
];

$live_prefix = 'z3live';
//SK AK HOST之类的配置在 src/lib/Wcs/Config.php 里面设置

$img_bucket = "mia-image";//图片存储空间名
$video_bucket = "mia-video";//视频存储空间名

$live_snap_shot = 'http://mia-image.miyabaobei.com';
$live_video = 'http://mia-video.miyabaobei.com';

$live_stream_api = [
    'protal_username'=>'miyabaobei',//平台帐号名
    'key'=>'',//key值
];

//直播状态URL
$live_stream_status = 'http://qualiter.wscdns.com/api/frameRate.jsp?';