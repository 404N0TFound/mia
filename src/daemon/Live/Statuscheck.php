<?php
namespace mia\miagroup\Daemon\Live;
class Statuscheck extends \FD_Daemon {
    public function execute() {
        $this->output(array('code' => 0));
    }
}
