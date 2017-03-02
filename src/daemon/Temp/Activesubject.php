<?php 
namespace mia\miagroup\Daemon\Temp;

class ActiveSubject extends \FD_Daemon {
    
    public function execute() {
        $this->fixImgData();
    }
    
    public function fixImgData() {
        $activeData = new \mia\miagroup\Data\Active\Active();
        $data = $activeData->getRows([], 'id, top_img');
        foreach ($data as $v) {
            $id = $v['id'];
            $url = 'https://img.miyabaobei.com/' . $v['top_img'];
            @$img = getimagesize($url);
            if ($img) {
                $imgWidth = $img[0];
                $imgHeight = $img[1];
                $ext_info['image'] = [
                    'url' => $url,
                    'width' => $imgWidth,
                    'height' => $imgHeight
                ];
                $set_data['ext_info'] = json_encode($ext_info);
            }
            var_dump($set_data, $id);
            $activeData->updateActive($set_data, $id);
            exit;
        }
    }
}