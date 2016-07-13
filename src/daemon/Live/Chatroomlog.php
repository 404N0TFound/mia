<?php
namespace mia\miagroup\Daemon\Live;

use mia\miagroup\Util\RongCloudUtil;
use mia\miagroup\Model\ChatHistory;
class Chatroomlog extends \FD_Daemon{
    
    public function execute(){
        $time = time()-3600;
        $date = date('YmdH',$time);
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
                $filePath = $chatroom_log_path.join('-',explode(' ',date('Y-m-d H',$time)));
                $result = $this->addData($filePath);
                if(!$result)
                    echo '导入数据失败';
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

    /**
     * 下载的文件写入数据库
     *
     * @return void
     * @author 
     **/
    protected function addData($filePath)
    {
        $handle = fopen($filePath,'r');
        if ($handle) {
            $contents = [];
            while(!feof($handle)){
                $line       = stream_get_line($handle, 1024 , "\n");
                $contents[] = json_decode(substr($line,19),true);

            }
            $chatHistory = new ChatHistory();
            $data        = [];
            foreach ($contents as $key => $value) {
                $res = $chatHistory->getChatHistoryByMsgUID($value['msgUID']);
                if(count($value)<9 || $res){
                    continue;
                }
                $data[$key]             = $value;
                $data[$key]['content']  = json_encode($value['content']);
                $data[$key]['dateTime'] = date('Y-m-d H:i:s',strtotime($value['dateTime']));
                $data[$key]['source']   = isset($value['source']) ? $value['source'] : '';

            }
            $result = $chatHistory->addChatHistories($data);
            return $result ? true : false;
        }
    }
}