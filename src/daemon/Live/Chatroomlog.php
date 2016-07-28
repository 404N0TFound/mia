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
                $filePath = $chatroom_log_path.join('-',explode(' ',date('Y-m-d H',$time)));
                $this->addData($filePath);
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
            $data = [];
            foreach ($contents as $key => $value) {
                $content = '';
                if(count($value)<9){
                    continue;
                }
                $data[$key] = $value;
                $content    = $value['content'];
                if (!isset($content['user'])) {
                    $data[$key]['userId']   = 0;
                    $data[$key]['username'] = '';
                    $data[$key]['portrait'] = '';
                } else {
                    $data[$key]['userId']   = isset($content['user']['id']) ? $content['user']['id'] : 0;
                    $data[$key]['username'] = isset($content['user']['name']) ? $content['user']['name'] : '';
                    $data[$key]['portrait'] = isset($content['user']['portrait']) ? $content['user']['portrait'] : '';;
                }
                
                $data[$key]['content']     = isset($content['content']) ? $content['content'] : '';
                $data[$key]['contentType'] = isset($content['type']) ? $content['type'] : '';
                $data[$key]['extra']       = isset($content['extra']) ? $content['extra'] : '';
                $data[$key]['dateTime']    = date('Y-m-d H:i:s',strtotime($value['dateTime']));
                $data[$key]['source']      = isset($value['source']) ? $value['source'] : '';
                
            }
            $liveModel = new LiveModel();
            $totalNum  = count($data);
            $totalPage = ceil($totalNum/100);
            for($i=0;$i<$totalPage;$i++){
                $newData = array_slice($data, ($i*100) ,100);
                $result  = $liveModel->addChatHistories($newData);
                sleep(1);
            }
            fclose($handle);
        }
    }
}