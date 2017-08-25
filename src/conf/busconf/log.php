<?php

//需要拦截的日志，方法=>记录概率(不要小于0.0001)
$interceptLog = [
    "News/addPlusNews" => 0.01,
    "News/addTradingNews" => 0.01,
    "News/addCouponNews" => 0.01
];

