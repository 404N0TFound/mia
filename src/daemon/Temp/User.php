<?php
namespace mia\miagroup\Daemon\Temp;

use mia\miagroup\Service\Album as AlbumService;
use mia\miagroup\Data\User\User as UserData;


class User extends \FD_Daemon
{
    public function __construct()
    {
        $this->albumService = new AlbumService();
        $this->userData = new UserData();
    }

    public function execute()
    {
        $file_path = '/tmp/album_permission';
        $data = file($file_path);
        foreach ($data as $v) {
            $phone = trim($v);
            $where = [];
            $where[] = array('cell_phone', $phone);
            $data = $this->userData->getRow($where, 'id');
            if ($data) {
                $user_id = $data['id'];
                $this->albumService->addAlbumPermission($user_id);
            }
        }
    }
}