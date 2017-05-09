<?php
namespace mia\miagroup\Daemon\Temp;

use mia\miagroup\Data\Robot\TextMaterial as TextMaterialData;

class Robot extends \FD_Daemon
{
    public function __construct()
    {
        $this->robotData = new TextMaterialData();
    }

    public function execute()
    {
        $robotService = new \mia\miagroup\Service\Robot();
        $data = file('/home/hanxiang/editor_id');
        foreach ($data as $v) {
            $v = trim($v);
            if (empty($v)) {
                continue;
            }
            echo $v, "\n";
            $robotService->publishEditorSubject($v);
        }
        
//         $data = file('/home/hanxiang/title_text');
//         $type = 'title';
//         foreach ($data as $v) {
//             $v = trim($v);
//             if (empty($v)) {
//                 continue;
//             }
//             $material_data['type'] = $type;
//             $material_data['text'] = $v;
//             $this->robotData->addTextMaterail($material_data);
//         }
        
    }
}