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
        $data = file('/home/hanxiang/title_text');
        $type = 'title';
        foreach ($data as $v) {
            $v = trim($v);
            if (empty($v)) {
                continue;
            }
            $material_data['type'] = $type;
            $material_data['text'] = $v;
            $this->robotData->addTextMaterail($material_data);
        }
    }
}