<?php
namespace mia\miagroup\Data\Koubei;

use Ice;

class KoubeiPic extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'koubei_pic';

    protected $mapping = array();
    
    /**
     * 根据口碑id批量获取口碑图片信息（该方法用于精品口碑，暂时不做）
     * @param array() $koubeiIds
     * @return array()
     */
    public function getBatchKoubeiPic($koubeiIds) {
    
    }
    
    /**
     * 保存口碑图片信息
     * @param array $koubeiPicInfo
     */
    public function saveKoubeiPic($koubeiPicInfo)
    {
        $koubeiPicData = $this->insert($koubeiPicInfo);
        return $koubeiPicData;
    }

}