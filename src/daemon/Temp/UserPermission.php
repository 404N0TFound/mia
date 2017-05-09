<?php
namespace mia\miagroup\Daemon\Temp;

use mia\miagroup\Data\User\GroupDoozer as DoozerData;
use mia\miagroup\Data\User\GroupSubjectUserExperts as ExpertsData;
use mia\miagroup\Data\User\GroupSubjectVideoPermission as VideoData;
use mia\miagroup\Data\Album\AlbumPermission as AlbumData;
use mia\miagroup\Data\Live\LiveRoom as LiveRoomData;

use mia\miagroup\Data\User\GroupUserCategory as CategoryData;
use mia\miagroup\Data\User\GroupUserPermission as PermissionData;

class UserPermission extends \FD_Daemon {

    public function execute() {
        //$this->setUserPermission('video');
        $this->setUserCategory('expert');
    }

    //将有专栏，视频及直播权限的用户导入蜜芽圈用户权限表
    public function setUserPermission($category){
        //获取不同分类用户信息
        $userArrs = $this->getCategoryUser($category);
        $permissionData = new PermissionData();
        
        if(!empty($userArrs) && in_array($category, array("video","album"))){
            foreach($userArrs as $userArr){
                //组织将有专栏，视频及直播权限的用户数据
                $setData = array();
                $setData['user_id'] = $userArr['user_id'];
                $setData['status'] = $userArr['status'];
                $setData['type'] = $category;
                $setData['source'] = isset($userArr['source']) ? $userArr['source'] : '';
                $setData['create_time'] = $userArr['create_time'];
                $setData['operator'] = $userArr['operator'];
                $extInfo = array();
                $extInfo['reason'] = $userArr['reason'];
                $setData['ext_info'] = json_encode($extInfo);
                //将用户信息导入到用户权限表
                $permissionData->addPermission($setData);
            }
        }
        return true;
    }
    
    //将有具有专家，达人属性的用户导入蜜芽圈用户分类表
    public function setUserCategory($category){
        //获取不同分类用户信息
        $userArrs = $this->getCategoryUser($category);
        $categoryData = new CategoryData();
        
        if(!empty($userArrs) && in_array($category, array("doozer","expert"))){
            foreach($userArrs as $userArr){
                //组织将达人、专家的用户分类数据
                $setData = array();
                $setData['user_id'] = $userArr['user_id'];
                $setData['status'] = $userArr['status'];
                
                $extInfo = array();
                if($category == 'doozer'){
                    $extInfo['desc'] = $userArr['intro'];
                    $setData['operator'] = $userArr['operator'];
                    $setData['create_time'] = $userArr['create_time'];
                    $setData['type'] = $category;
                    $setData['category'] = '';
                }else{
                    $extInfo['desc'] = $userArr['desc'];
                    $extInfo['label'] = $userArr['label'];
                    $extInfo['last_modify'] = $userArr['last_modify'] ;
                    $extInfo['modify_author'] = $userArr['modify_author'];
                    $extInfo['answer_nums'] = $userArr['answer_nums'];
                    $setData['type'] = "doozer";
                    $setData['category'] = $category;
                    
                    $setData['operator'] = $userArr['modify_author'];
                    $setData['create_time'] = $userArr['last_modify'];
                }
                $setData['ext_info'] = json_encode($extInfo);
                //将用户信息导入到用户分类表
                $categoryData->addCategory($setData);
            }
        }
        return true;
    }
    
    //获取专家、有专栏、视频权限的用户信息
    public  function getCategoryUser($category){
        switch ($category) {
            case  'doozer' :
                $categoryUserData = new DoozerData();
                break;
            case  'expert' :
                $categoryUserData = new ExpertsData();
                break;
            case  'video' :
                $categoryUserData = new VideoData();
                break;
            case  'album' :
                $categoryUserData = new AlbumData();
                break;
            default :
                $categoryUserData = new LiveRoomData();
                break;
        }
        
        $userArr = $categoryUserData->getRows();
        return $userArr;
    }
}