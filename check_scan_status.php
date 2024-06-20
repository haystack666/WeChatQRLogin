<?php
require 'WeChat.class.php';
$wechat = new WeChat();

$scene_str = $_GET['scene_str'];
$openid = $wechat->getOpenidByEventKey($scene_str);

if ($openid) {
    echo json_encode(['status' => 'scanned', 'openid' => $openid]);
} else {
    echo json_encode(['status' => 'waiting']);
}
?>
