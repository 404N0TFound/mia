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
    'method' => 'get',
    'result_format' => false
);
