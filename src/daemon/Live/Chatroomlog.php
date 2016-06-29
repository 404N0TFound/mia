<?php
namespace mia\miagroup\Daemon\Live;

use mia\miagroup\Util\RongCloudUtil;

class Chatroomlog extends \FD_Daemon{
    
    public function execute(){
        
        $date = date('YmdH');
        $rong_api = new RongCloudUtil();
        $url = $rong_api->messageHistory($date);
        if(!empty($url)){
            $url_info = pathinfo($url);
            $chatroom_log_path = \F_Ice::$ins->workApp->config->get('app.chatroom_log_path').date('Ymd').'/';
            $file_name = 'chatroomlog_'.$date.$url_info['extension'];
            $dir = $chatroom_log_path.$file_name;
            
            if(!is_dir($chatroom_log_path)){
                @mkdir($chatroom_log_path,0777,true);
            }
            
            $this->curlDownload($url, $dir);
            
            //处理压缩文件
//             $zip = new ZipArchive;
//             if ($zip->open($file_name) === TRUE) {
//                 $zip->extractTo('foldername');
//                 $zip->close();
//                 //success
//             } else {
//                 //fail
//             }
            
        }
    }
    
    /**
     * crul下载文件
     * @param unknown $url
     * @param unknown $dir
     */
    public function curlDownload($url, $dir) {
        $ch = curl_init($url);
        $fp = fopen($dir, "wb");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $res=curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        
        if(!$res){
            die(curl_error($ch));
        }
        return $res;
    }
}