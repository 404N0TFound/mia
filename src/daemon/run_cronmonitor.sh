#!/bin/bash
while [ true ];
do
  sleep 1
  /opt/php/bin/php /opt/webroot/groupservice/current/src/daemon/cli.php --class=common --action=cronmonitor
done
