<?php
namespace mia\miagroup\Daemon\Live;
class Streamcheck extends \FD_Daemon {
    public function execute() {
        $this->output(array('code' => 0));
    }
}
