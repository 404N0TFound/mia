<?php

namespace  Wcs;


final class Config
{
    //version
    const WCS_SDK_VER = "1.3.1";


    //url设置
    const WCS_PUT_URL	= '';   //WCS put 上传路径
    const WCS_GET_URL	= '';    //WCS get 上传路径
    const WCS_MGR_URL	= 'miyabaobei.mgr11.v1.wcsapi.com';    //WCS MGR 路径

    //access key and secret key
    const WCS_ACCESS_KEY	= '09ee5f51bb17641b11216dd6e52a7179b3bc6a55';
    const WCS_SECRET_KEY	= '544a05dd515731700624117b6507893d9ed90b03';

    //token的deadline,默认是1小时,也就是3600s
    const  WCS_TOKEN_DEADLINE = 3600;

    //上传文件设置
    const WCS_OVERWRITE = 1; //默认文件不覆盖

    //超时时间
    const WCS_TIMEOUT = 0;

    //分片上传参数设置
    const WCS_BLOCK_SIZE = 4194304; //默认块大小4M
   // const WCS_CHUNK_SIZE =  4 * 256 * 1024; //默认片大小256K
    const WCS_CHUNK_SIZE =  4194304; // 片大小==块大小，提高传输速度
    const WCS_RECORD_URL = './'; //默认当前文件目录
    const WCS_COUNT_FOR_RETRY = 3;  //超时重试次数
}

