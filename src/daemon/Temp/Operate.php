<?php
namespace mia\miagroup\Daemon\Temp;

use mia\miagroup\Remote\miBean as miBeanRemote;

/**
 * 运营相关-临时脚本
 *
 * issueMiBean() 发放蜜豆奖励
 */

class Operate extends \FD_Daemon
{
    public function __construct()
    {

    }

    public function execute()
    {
        $function_name = $this->request->argv[0];
        $this->$function_name();
    }

    public function issueMiBean()
    {
        $data = file('/home/xiekun/bean_user');
        $bean = new miBeanRemote();
        foreach ($data as $v) {
            $v = trim($v);
            list($user_id, $count) = explode("\t", $v);
            if(empty($user_id) || empty($count)) {
                continue;
            }
            $param['user_id'] = 3782852;//蜜芽兔
            $param['to_user_id'] = $user_id;
            $param['relation_type'] = "group_operator";
            $param['relation_id'] = 0;
            $param['mibean'] = $count;
            $res = $bean->add($param);
        }
    }

}