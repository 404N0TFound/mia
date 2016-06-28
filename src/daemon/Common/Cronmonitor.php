<?php
namespace mia\miagroup\Daemon\Common;
class Cronmonitor extends \FD_Daemon {
    
    private $temp_file_path;
    private $php_bin;
    private $php_cli;
    
    public function execute() {
        //加载定时脚本临时文件存放地址
        $runFilePath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $this->temp_file_path = $runFilePath . '/cronmonitor';
        //加载定时脚本列表
        $daemon_cron = \F_Ice::$ins->workApp->config->get('busconf.daemoncron.cron_list');
        //加载cli入口文件
        $this->php_cli = \F_Ice::$ins->workApp->config->get('app.root_path') . \F_Ice::$ins->workApp->config->get('busconf.daemoncron.php_cli');
        //加载php bin
        $this->php_bin = \F_Ice::$ins->workApp->config->get('app.damon_php_bin');
        if (empty($daemon_cron)) {
            return;
        }
        foreach ($daemon_cron as $cron_name => $cron) {
            //脚本是否启用
            if ($cron['enable'] == false) {
                continue;
            }
            //脚本是否正在运行
            if ($this->daemon_running($cron['cli_args']) == true) {
                continue;
            }
            //脚本是否到执行时间点
            if ($this->time_to_run($cron_name) == false) {
                continue;
            }
            switch ($cron['engine']) {
                case "php" :
                    $cmd = "{$this->php_bin} {$this->php_cli} .  -f {$cron['cli_args']} > /dev/null &";
                    $handle = popen($cmd, "r");
                    break;
                case "bash" :
                    $cmd = "/bin/bash {$cron['cli_args']}";
                    $handle = popen($cmd, "r");
                    break;
            }
            file_put_contents($this->temp_file_path . $cron_name . '_last_time', time());
        }
    }
    
    /**
     * 检测自身进程，同时只允许运行一个实例
     */
    private function daemon_running($daemon_name) {
        $daemon_name = addcslashes($daemon_name, '-');
        $chk_cmd = "ps ax | grep '{$daemon_name}' | grep -v grep | wc -l";
        $fp = @popen($chk_cmd, 'r');
        $rst = trim(@fread($fp, 2096));
        @pclose($fp);
        $process_num = intval($rst);
        if ($process_num >= 1) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 查看是否到执行时间点
     */
    private function time_to_run($cron_name) {
        $cron_list = \F_Ice::$ins->workApp->config->get('daemoncron.cron_list');
        $cron = $cron_list[$cron_name];
        $this->mk_dir($this->temp_file_path);
        $daemon_time_file = $this->temp_file_path . $cron_name . '_last_time';
        //文件不存在，首次执行
        if (!file_exists($daemon_time_file)) {
            if (isset($cron['start_time'])) {
                if (time() - strtotime($cron['start_time']) > 0) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return true;
            }
        }
        $last_time = file_get_contents($daemon_time_file);
        //当前时间-上次执行时间>间隔
        if (time() - $last_time >= $cron['interval']) {
            return true;
        }
        return false;
    }
    
    /**
     * 检测路径是否存在并自动生成不存在的文件夹
     */
    private function mk_dir($path) {
        if(is_dir($path)) return true;
        if(empty($path)) return false;
        $path = rtrim($path, '/');
        $bpath = dirname($path);
        if(!is_dir($bpath)) {
            if(!$this->mk_dir($bpath)) return false;
        }
        if(!@chdir($bpath)) return false;
        if(!@mkdir(basename($path))) return false;
        return true;
    }
}
