<?php
/**
 * 远程curl配置
 */

/**
 * 示例配置，配置将作用于 \Lib\RemoteCurl类
 * $for_example = array(
 *   'url'          => 'http://xxxxx',   //请求地址
 *   'method'       => 'post',           //请求方法，post或get
 *   'charset'      => 'utf8',           //可选配置，返回数据编码，默认为"utf8"
 *   'data_key'     => 'data',           //可选配置，返回json数据key，默认为"data"
 *   'code_key'     => 'code',           //可选配置，返回json错误码key，默认为"code"
 *   'msg_key'      => 'msg',            //可选配置，返回json错误信息key，默认为"msg"
 *   'success_code' => 0,                //可选配置，返回json请求成功状态码，默认为0
 *   'result_format'=> true,             //可选配置，返回结果是否格式化解析，默认为true。
 * );
 */

//商品推荐服务
$item_recommend = array(
    'url' => 'http://rec.mia.com',
    'method' => 'get',
    'result_format' => false
);

//首页分类标签个性化推荐
$index_cate_recommend = array(
    'url' => 'http://10.1.106.28:1872',
    //'url' => 'http://content.rec.mia.com',
    'method' => 'get',
    'result_format' => false
);

//商品搜索
$item_search = array(
    'url' => 'http://172.16.96.75:18008/miase_wangpan/api.php',
    //'url' => 'http://search.mia.com/miase/api.php',
    'method' => 'get',
    'result_format' => false
);
//用户搜索
$user_search = array(
    'url' => 'http://10.1.106.10:8998/solr/user_search/select',
    //'url' => '​http://user.search.miaidc.com:81/solr/user_search/select',
    'method' => 'get',
    'result_format' => false
);
//笔记搜索
$subject_search = array(
    'url' => 'http://172.16.96.75:18008/notes/api.php',
    //'url' => 'http://notes.search.miaidc.com/notes/api.php',
    'method' => 'get',
    'result_format' => false
);