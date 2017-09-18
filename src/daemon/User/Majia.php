<?php
namespace mia\miagroup\Daemon\User;

/**
 * 马甲相关脚本
 */
 
class Majia extends \FD_Daemon {
    
    private $user_service;
    private $majia_data;
    private $majia_ums_data;

    public function __construct() {
        $this->user_service = new \mia\miagroup\Service\User();
        $this->majia_data = new \mia\miagroup\Data\Robot\AvatarMaterial();
        $this->majia_ums_data = new \mia\miagroup\Model\Ums\Robot();
    }

    public function execute() {
        $function_name = $this->request->argv[0];
        $this->$function_name();
    }
    
    /**
     * 马甲宝宝年龄信息更新
     */
    public function baby_birth_incr() {
        $incr_value = $this->request->argv[1];
        if (empty($incr_value)) {
            $incr_value = 86400;
        }
        $majia_users = $this->majia_ums_data->getAvatarMaterialData(['status' => 2], 0, false);
        if (empty($majia_users['list'])) {
            return ;
        }
        foreach ($majia_users['list'] as $v) {
            if (empty($v['child_birthday']) || $v['child_birthday'] == '0000-00-00') {
                continue;
            }
            $update_birth = date('Y-m-d', strtotime($v['child_birthday']) + $incr_value);
            $this->majia_data->updateAvatarMaterialById($v['id'], ['child_birthday' => $update_birth]);
            $this->user_service->updateUserInfo($v['user_id'], ['child_birth_day' => $update_birth]);
        }
    }
}