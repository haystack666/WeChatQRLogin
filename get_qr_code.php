<?php
require 'WeChat.class.php';
$wechat = new WeChat();

$scene_str = uniqid();
$qrcodeData = $wechat->getQrcodeByStr($scene_str);
$ticket = $qrcodeData['ticket'];
$qr_url = $wechat->generateQrcode($ticket);

echo json_encode(['qr_url' => $qr_url, 'scene_str' => $scene_str]);
?>
