<?php
/**
 * 七牛密钥
 */
$access_key = '8pCo5Aj1Y0n9fx6Q3sYrkmtO2Bb-VmyDrTN0uN--';
$secret_key = 'YBPALFcIODJ32FSMxJDXFJ2KmtiA-zu4BxGJ6oPl';

/**
 * 七牛域名
 */
$video_host = 'http://video1.miyabaobei.com/';
$image_host = 'http://image1.miyabaobei.com/';

/**
 * 资源空间
 */
$video_bucket = 'video';
$image_bucket = 'image';
$file_bucket = 'file';

/**
 * 直播hub名称
 */
 $live_hub = 'mia_live';
 
 /**
  * 直播域名
  */
 $live_host = array(
     'rtmp' => 'pili-live-rtmp.live1.miyabaobei.com',
     'hls'  => 'pili-live-hls.live1.miyabaobei.com',
     'hdl'  => 'pili-live-hdl.live1.miyabaobei.com',
     'playback' => 'pili-playback.live1.miyabaobei.com',
 );

/**
 * 视频转码队列名称
 */
$video_transcoding_pipe = 'video';
 
/**
 * 视频转码完成回调地址(暂未启用)
 */
$avthumb_callback = 'http://api.miyabaobei.com/qiniucallback/groupvideo?video_id=%s';

