#!/bin/bash
while [ true ];
do
  sleep 1
  /usr/local/php/bin/php /var/www/html/xigua_sns_dev_1.0/service/classes/daemon/cron/monitor.php
done
