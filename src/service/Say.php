<?php
namespace mia\miagroup\Service;
class Say extends \FS_Service {
    public function hello($name) {
        return $this->succ('Hello ' . $name);
    }
}
