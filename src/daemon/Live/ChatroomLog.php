<?php
namespace mia\miagroup\Daemon\Live;

use mia\miagroup\Util\RongCloudUtil;

class ChatroomLog extends \FD_Daemon{
    
    public function execute(){
        
        $date = date('YmdH');
        $rong_api = new RongCloudUtil();
        $url = $rong_api->messageHistory($date);
        if(!empty($url)){
            
        }
    }
}