<?php
namespace mia\miagroup\Daemon\Live;

use mia\miagroup\Util\RongCloudUtil;
use mia\miagroup\Model\Live as LiveModel;
class Chatroomlog extends \FD_Daemon{
    
    public function execute(){
        $time = time()-3600;
        $date = date('YmdH',$time);
        $rong_api = new RongCloudUtil();
        $url = $rong_api->messageHistory($date);
        if(!empty($url)){
            $url_info = pathinfo($url);
            $chatroom_log_path = \F_Ice::$ins->workApp->config->get('app.chatroom_log_path') . '/chatroomlog' . '/' . date('Ymd',$time) . '/';
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
                $filePath = $chatroom_log_path.date('Y-m-d-H',$time);
                $this->readFile($filePath);
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

    public function readFile($filePath)
    {
        $handle = fopen($filePath,'r');
        if ($handle) {
            $contents = [];
            while(!feof($handle)){
                $line       = stream_get_line($handle, 102400,"\n");
                if (empty($line)) {
                    continue;
                }
                $data = json_decode(substr($line,19),true);
                if(!isset($data['msgUID']) || empty($data['msgUID'])) {
                    continue;
                }

                $data['GroupId'] = $data['content']['service_extra']['chat_room_id'];
                $contents[]      = $data;
                if (count($contents)==100) {
                    $this->addData($contents);
                    $contents = [];
                    sleep(1);
                }
            }
            if(!empty($contents) && feof($handle)) {
                $this->addData($contents);
            }
            unset($contents);
            fclose($handle);
        }
    }

    /**
     * 添加聊天日志
     *
     * @return void
     * @author 
     **/
    public function addData($contents)
    {
        $data = [];
        foreach ($contents as $key => $value) {
            $content = [];
            if (isset($value['content'])) {
                $content    = $value['content'];
            }
            
            if (!isset($content['user'])) {
                $data[$key]['userId']   = 0;
                $data[$key]['username'] = '';
                $data[$key]['portrait'] = '';
            } else {
                $data[$key]['userId']   = isset($content['user']['id']) ? $content['user']['id'] : 0;
                $data[$key]['username'] = isset($content['user']['name']) ? $content['user']['name'] : '';
                if (isset($content['user']['portrait'])) {
                    $data[$key]['portrait'] = $content['user']['portrait'];
                } elseif ($content['user']['icon']) {
                    $data[$key]['portrait'] = $content['user']['icon'];
                } else {
                    $data[$key]['portrait'] = '';
                }
                
            }

            $data[$key]['appId']       = isset($value['appId']) ? $value['appId'] : '';
            $data[$key]['fromUserId']  = isset($value['fromUserId']) ? $value['fromUserId'] : 0;
            $data[$key]['targetId']    = isset($value['targetId']) ? $value['targetId'] : 0;
            $data[$key]['targetType']  = isset($value['targetType']) ? $value['targetType'] : 0;
            $data[$key]['GroupId']     = isset($value['GroupId']) ? $value['GroupId'] : 0;
            $data[$key]['classname']   = isset($value['classname']) ? $value['classname'] : '';
            $data[$key]['content']     = isset($content['content']) ? $content['content'] : '';
            $data[$key]['contentType'] = isset($content['type']) ? $content['type'] : '';
            $data[$key]['extra']       = isset($content['extra']) ? $content['extra'] : '';
            $data[$key]['dateTime']    = isset($value['dateTime']) ? date('Y-m-d H:i:s',strtotime($value['dateTime'])) : date('Y-m-d H:i:s',time());
            $data[$key]['msgUID']      = isset($value['msgUID']) ? $value['msgUID'] : '';
            $data[$key]['source']      = isset($value['source']) ? $value['source'] : '';
                
        }

        if($data) {
            $liveModel = new LiveModel();
            $liveModel->addChatHistories($data);
        }
        
    }
}