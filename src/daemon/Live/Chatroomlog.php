<?php
namespace mia\miagroup\Daemon\Live;

use mia\miagroup\Util\RongCloudUtil;

class Chatroomlog extends \FD_Daemon{
    
    public function execute(){
        
        $date = date('YmdH',time()-3600);
        $rong_api = new RongCloudUtil();
        $url = $rong_api->messageHistory($date);
        if(!empty($url)){
            $url_info = pathinfo($url);
            $chatroom_log_path = \F_Ice::$ins->workApp->config->get('app.chatroom_log_path') . '/chatroomlog' . '/' . date('Ymd') . '/';
            $filename = $chatroom_log_path.$url_info['basename'];
            if(!is_dir($chatroom_log_path)){
                @mkdir($chatroom_log_path,0777,true);
            }
            //下载文件
            $this->curlDownload($url, $filename);
            //处理压缩文件
            $zip=new \ZipArchive();
            if($zip->open($filename)===TRUE){
                $zip->extractTo($chatroom_log_path);
                $zip->close();
                unlink($filename);
                echo 'success';
            }else{
                echo 'fail';
            }
        }
    }
    
    /**
     * crul下载文件
     * @param unknown $url
     * @param unknown $dir
     */
    public function curlDownload($url, $filename) {
        $ch = curl_init($url);
        $fp = fopen($filename, "wb");
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