<?php
require 'WeChat.class.php';
$wechat = new WeChat();

$IP = $wechat->getClientIp();
$scene_str = $_GET['scene_str'];

$openid = $wechat->getOpenidByEventKey($scene_str);
$nickname = $wechat->getNicknameByOpenid($openid);

if ($openid && $nickname) {
    echo "<h1>登录成功</h1>";
    echo "<p>欢迎，{$nickname}</p>";

    // 获取登录地信息
    echo("IP地址为：".$IP."</br>");
    $login_location = $wechat->getIpLocation($IP);
    echo("登录位置为：".$login_location."</br>");

    // 获取 User-Agent 信息
    $ua = $_SERVER['HTTP_USER_AGENT'];

    // 记录登录信息到 login_records 表
    $wechat->recordLogin($openid, $IP, $ua, $login_location, $scene_str);

    // 发送模板消息
    $template_id = "YOUR-TEMPLATE-ID"; // 模板ID
    $message_data = array(
        "thing17" => array("value" => $nickname),
        "time3" => array("value" => date('Y-m-d H:i:s')),
        "thing9" => array("value" => $login_location),
        "thing4" => array("value" => "浏览器"),
    );
    $response = $wechat->sendTemplateMessage($openid, $template_id, $message_data);
    if ($response['errcode'] == 0) {
        echo "<br>微信公众号模板消息发送成功";
    } else {
        echo "模板消息发送失败: " . $response['errmsg'];
    }
} else {
    echo "<h1>未找到登录信息</h1>";
}
?>
