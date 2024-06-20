<?php
class WeChat {
    protected $appid = "YOUR-APPID";
    protected $secret = "YOUR-SECRET";
    protected $accessToken;
    protected $mysqli;

    function __construct() {
        $this->accessToken = $this->getAccessToken();
        $this->mysqli = new mysqli('主机地址','用户名','密码','数据库名');

        if ($this->mysqli->connect_error) {
            die("Connection failed: " . $this->mysqli->connect_error);
        }
    }

    private function getAccessToken() {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appid}&secret={$this->secret}";
        $res = json_decode($this->httpRequest($url), true);
        return $res['access_token'];
    }

    private function httpRequest($url, $data = "") {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    public function getQrcodeByStr($scene_str) {
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token={$this->accessToken}";
        $data = json_encode([
            "expire_seconds" => 3600,
            "action_name" => "QR_STR_SCENE",
            "action_info" => ["scene" => ["scene_str" => $scene_str]]
        ]);
        $result = $this->httpRequest($url, $data);
        return json_decode($result, true);
    }

    public function generateQrcode($ticket) {
        return "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket={$ticket}";
    }

    public function sendTemplateMessage($openid, $template_id, $data, $url = '', $miniprogram = null, $client_msg_id = '') {
        $access_token = $this->accessToken;
        $api_url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=$access_token";
        
        $post_data = array(
            "touser" => $openid,
            "template_id" => $template_id,
            "url" => $url,
            "data" => $data
        );

        if ($miniprogram) {
            $post_data['miniprogram'] = $miniprogram;
        }

        if ($client_msg_id) {
            $post_data['client_msg_id'] = $client_msg_id;
        }

        $result = $this->httpRequest($api_url, json_encode($post_data));
        return json_decode($result, true);
    }
    
    public function callback() {
        $callbackXml = file_get_contents('php://input');
        $data = json_decode(json_encode(simplexml_load_string($callbackXml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        file_put_contents('log.txt', "Received callback: " . print_r($data, true) . "\n", FILE_APPEND);

        if (isset($data['FromUserName']) && isset($data['Event'])) {
            $userInfo = $this->getUserInfo($data['FromUserName']);

            if ($data['Event'] == 'subscribe' && isset($data['EventKey'])) {
                // 用户未关注时，关注后的事件处理
                $eventKey = str_replace('qrscene_', '', $data['EventKey']);
                $this->storeUserInfo($userInfo, $eventKey);
            } elseif ($data['Event'] == 'SCAN' && isset($data['EventKey'])) {
                // 用户已关注时的事件处理
                $this->storeUserInfo($userInfo, $data['EventKey']);
            }
        }
    }


    public function getUserInfo($openId) {
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$this->accessToken}&openid={$openId}&lang=zh_CN";
        $data = $this->httpRequest($url);
        return json_decode($data, true);
    }
    
    public function storeUserInfo($userInfo, $eventKey) {
        $openid = $userInfo['openid'];
        $nickname = "用户_" . substr(md5(uniqid(mt_rand(), true)), 0, 8);

        // 检查是否重复注册
        $user_check_stmt = $this->mysqli->prepare("SELECT id FROM wechat_users WHERE openid = ?");
        if ($user_check_stmt === false) {
            die("Prepare failed: " . $this->mysqli->error);
        }
        $user_check_stmt->bind_param("s", $openid);
        $user_check_stmt->execute();
        $user_check_stmt->store_result();

        if ($user_check_stmt->num_rows == 0) {
            // 添加新用户到 wechat_users 表
            $user_insert_stmt = $this->mysqli->prepare("INSERT INTO wechat_users (openid, nickname) VALUES (?, ?)");
            if ($user_insert_stmt === false) {
                die("Prepare failed: " . $this->mysqli->error);
            }
            $user_insert_stmt->bind_param("ss", $openid, $nickname);
            $user_insert_stmt->execute();
            $user_insert_stmt->close();
        }

        $user_check_stmt->close();

        // 记录登录信息到 login_records 表
        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = $_SERVER['HTTP_USER_AGENT'];
        $login_location = $this->getIpLocation($ip);

        $login_insert_stmt = $this->mysqli->prepare("INSERT INTO login_records (openid, ip, user_agent, login_location, event_key) VALUES (?, ?, ?, ?, ?)");
        if ($login_insert_stmt === false) {
            die("Prepare failed: " . $this->mysqli->error);
        }
        $login_insert_stmt->bind_param("sssss", $openid, $ip, $ua, $login_location, $eventKey);
        $login_insert_stmt->execute();
        $login_insert_stmt->close();
    }


    public function getIpLocation($ip) {
        $api_url = "https://webapi-pc.meitu.com/common/ip_location?ip=$ip";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $api_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            echo 'Error:' . curl_error($curl);
        }
        curl_close($curl);
        $location_data = json_decode($response, true);
        if (isset($location_data['data'][$ip]['nation']) && isset($location_data['data'][$ip]['province']) && isset($location_data['data'][$ip]['city'])) {
            return $location_data['data'][$ip]['nation'] . "，" . $location_data['data'][$ip]['province'] . "，" . $location_data['data'][$ip]['city'];
        } else {
            return "未知位置";
        }
    }

    public function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }


    public function getOpenidByEventKey($eventKey) {
        $stmt = $this->mysqli->prepare("SELECT openid FROM login_records WHERE event_key = ?");
        if ($stmt === false) {
            die("Prepare failed: " . $this->mysqli->error);
        }
        $stmt->bind_param("s", $eventKey);
        $stmt->execute();
        $stmt->bind_result($openid);
        $stmt->fetch();
        $stmt->close();
        return $openid;
    }
    
    public function getNicknameByOpenid($openid) {
        $stmt = $this->mysqli->prepare("SELECT nickname FROM wechat_users WHERE openid = ?");
        if ($stmt === false) {
            die("Prepare failed: " . $this->mysqli->error);
        }
        $stmt->bind_param("s", $openid);
        $stmt->execute();
        $stmt->bind_result($nickname);
        $stmt->fetch();
        $stmt->close();
        return $nickname;
    }

    public function recordLogin($openid, $ip, $ua, $login_location, $eventKey) {
        $login_insert_stmt = $this->mysqli->prepare("INSERT INTO login_records (openid, ip, user_agent, login_location, event_key) VALUES (?, ?, ?, ?, ?)");
        if ($login_insert_stmt === false) {
            die("Prepare failed: " . $this->mysqli->error);
        }
        $login_insert_stmt->bind_param("sssss", $openid, $ip, $ua, $login_location, $eventKey);
        $login_insert_stmt->execute();
        $login_insert_stmt->close();
    }
}
?>
