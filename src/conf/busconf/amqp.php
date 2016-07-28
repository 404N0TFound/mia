<?php
$amqp_app_push = [
    "exchange"      => "app_push_name",
    "exchange_type" => "direct",
    "durable"       => true,
    "queue_name"    => "app_push_que",
    "routing_key"   => "app_push_rout",
];