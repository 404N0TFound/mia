#!/bin/bash
while [ true ];
do
  sleep 1
  /usr/local/php/bin/php /data/www/groupservice/src/daemon/cli.php --class=common --action=cronmonitor
done
