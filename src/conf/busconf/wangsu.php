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