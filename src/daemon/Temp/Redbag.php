<?php
namespace mia\miagroup\Daemon\Temp;
use mia\miagroup\Data\Redbag\Baseinfo;
use mia\miagroup\Data\Redbag\Redbagme;
use mia\miagroup\Data\Redbag\Redbagtadetail;
use mia\miagroup\Service\Redbag as DaemonRedbag;

/**
 * 直播红包压测数据处理-临时脚本
 */
/**
 * 新增测试红包
 *     php cli.php --class=temp --action=redbag --function=addRedBag --param={\"redBagId\":1000001}
 * 调拆分红包服务  
 *     curl -d '{"class":"Redbag","action":"splitRedBag","params":{"redBagId":"1000001"}}' groupservice.miyabaobei.com
 * 领取红包 
 *     curl -d '{"class":"Live","action":"getLiveRedBag","params":{"userId":"123","redBagId":"1000001","roomId":"13"}}' groupservice.miyabaobei.com
 * 删除tadetail
 *     php cli.php --class=temp --action=redbag --function=delRedBagTaDetail --param={\"applyId\":63\,\"redBagId\":1000001}
 * 删除me
 *     php cli.php --class=temp --action=redbag --function=delRedBagMe --param={\"applyId\":63}
 * 删除baseinfo
 *     php cli.php --class=temp --action=redbag --function=delBaseInfo --param={\"redBagId\":1000001}
 */
class Redbag extends \FD_Daemon {
    
    private $baseInfoData;
    private $meData;
    private $taData;
    private $bagService;
    
    public function __construct() {
        $this->baseInfoData = new Baseinfo();
        $this->meData = new Redbagme();
        $this->taData = new Redbagtadetail();
        $this->bagService = new DaemonRedbag();
    }
    
    public function execute() {
        $function = $this->request->options['function'];
        $param = $this->request->options['param'];
        $param = json_decode($param, true);
        switch ($function) {
            case 'delRedBagMe':
                $this->delRedBagMe($param['applyId']);
                break;
            case 'delRedBagTaDetail':
                $this->delRedBagTaDetail($param['applyId']);
                break;
            default:
                $this->$function($param['redBagId']);
        }
    }
    
    private function addRedBag($redBagId) {
        $insertData = array();
        $insertData['redbag_id'] = $redBagId;
        $insertData['department_id'] = 8;
        $insertData['apply_admin_uid'] = 10922;
        $insertData['all_money'] = 1000000;
        $insertData['min_money'] = 1;
        $insertData['max_money'] = 2;
        $insertData['receive_time'] = time() + 86400 * 2;
        $insertData['use_time'] = time();
        $insertData['use_endtime'] = time() + 86400 * 2;
        $insertData['source'] = '直播红包测试';
        $insertData['status'] = 2;
        $insertData['adminuid'] = 10146;
        $insertData['passtime'] = time();
        $insertData['cretaetime'] = time();
        $insertData['description'] = '直播红包测试';
        $result = $this->baseInfoData->insert($insertData);
        var_dump($result);
    }
    
    private function delBaseInfo($redBagId) {
        $where[] = ['redbag_id', $redBagId];
        $result = $this->baseInfoData->delete($where);
        var_dump($result);
    }
    
    private function delRedBagMe($applyId) {
        $where[] = ['apply_id', $applyId];
        $where[] = ['is_test', 1];
        $result = $this->meData->delete($where);
        var_dump($result);
    }
    
    private function delRedBagTaDetail($applyId) {
        $where[] = ['apply_id', $applyId];
        $result = $this->taData->delete($where);
        var_dump($result);
    }
    
    private function splitRedBag($redBagId) {
        $result = $this->bagService->splitRedBag($redBagId);
        var_dump($result);
    }
    
    private function resetRedBag($redBagId) {
        $result = $this->bagService->resetRedBag($redBagId);
        var_dump($result);
    }
}