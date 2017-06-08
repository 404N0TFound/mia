<?php
namespace mia\miagroup\Daemon\Temp;

use mia\miagroup\Data\User\User as UserData;


class User extends \FD_Daemon
{
    private $userData;
    private $avatarMaterialData;
    
    public function __construct()
    {
        $this->userData = new UserData();
        $this->avatarMaterialData = new \mia\miagroup\Data\Robot\AvatarMaterial();
    }

    public function execute() {
        $function_name = $this->request->argv[0];
        $this->$function_name();
    }
    
    public function updateMajiaUser() {
        $data = file('/home/hanxiang/baobaonick');
        $baby_nick = [];
        foreach ($data as $v) {
            list($nick, $gender) = explode("\t", trim($v));
            $gender = $gender == '女' ? 1 : 2;
            $baby_nick[] = ['nick' => $nick, 'gender' => $gender];
        }
        $baby_nick1 = $baby_nick;
        
        $data = file('/home/hanxiang/majia_uids');
        foreach ($data as $v) {
            $user_id = intval(trim($v));
            if (empty($user_id)) {
                continue;
            }
            $user_info = array();
            $user_info['user_status'] = 1;
            $min_time = strtotime('2014-06-01');
            $max_time = strtotime('2016-06-01');
            $user_info['child_birth_day'] = date('Y-m-d', rand($min_time, $max_time));
            if (!empty($baby_nick)) {
                $baby = array_pop($baby_nick);
            } else {
                $key = array_rand($baby_nick1);
                $baby = $baby_nick1[$key];
            }
            $user_info['child_sex'] = $baby['gender'];
            $user_info['child_nickname'] = $baby['nick'];
            $user_info['level'] = rand(1, 4);
            $sql = "update users set user_status = {$user_info['user_status']}, child_birth_day = '{$user_info['child_birth_day']}', child_sex = {$user_info['child_sex']}, child_nickname = '{$user_info['child_nickname']}', level = {$user_info['level']} where id = $user_id limit 1";
            $ret = $this->userData->query($sql);
            echo $sql . "\n";var_dump($ret);
        }
        
        
    }
    
    public function updateBabyBirthday() {
        $data = file('/home/hanxiang/04-08-baby');
        foreach ($data as $v) {
            $user_id = intval(trim($v));
            $min_time = strtotime('2016-10-01');
            $max_time = strtotime('2017-02-01');
            $user_info['child_birth_day'] = date('Y-m-d', rand($min_time, $max_time));
            $sql = "update users set child_birth_day = '{$user_info['child_birth_day']}' where id = $user_id";
            //$this->userData->query($sql);
            echo $sql . "\n";
        }
    }
    
    public function updateAvatarMaterial() {
        $data = file('/home/hanxiang/majia_uids');
        $user_ids = [];
        foreach ($data as $v) {
            $user_ids[] = trim($v);
        }
        $where[] = ['id', $user_ids];
        $field = 'id, user_status, child_birth_day, child_sex';
        $data = $this->userData->getRows($where, $field);
        foreach ($data as $v) {
            if (!empty($v['child_birth_day'])) {
                $set_data = [];
                $set_data[] = ['user_status', $v['user_status']];
                $set_data[] = ['child_birthday', $v['child_birth_day']];
                $set_data[] = ['child_sex', $v['child_sex']];
                $where = [];
                $where[] = ['user_id', $v['id']];
                $this->avatarMaterialData->update($set_data, $where);
            }
        }
    }
}