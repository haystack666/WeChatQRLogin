<div align="center">

<div style="width: 30;>

[![haystack666/WeChatQRLogin](https://haydata-cd.oss-cn-chengdu.aliyuncs.com/github/WeChatQRLogin/WeChatQRLogin-logo.png?x-oss-process=style/WeChatQRLogin_image_small)](https://github.com/haystack666/WeChatQRLogin)

</div>

</div>

<h1 align="center">WeChatQRLogin</h1>

## 项目介绍

网页中使用微信公众号的微信扫码登录，【下载即可用】的微信公众号的微信扫码登录，使用微信官方接口以及美图IP地址的API，不夹带任何私货，请放心使用😅！！！

## 共同开发

欢迎各位朋友随时提问，让WeChatQRLogin变得更简单好用！如果你有新的想法，也欢迎反馈。

## 功能特性

- [x] 包含注册代码（包含防止重复注册），使用MySQL记录注册用户信息以及登录日志
- [x] 未关注用户扫码并关注公众号后也能正常注册登录

## TODO

- [ ] 根据浏览器UA返回正确的标识

## 注意事项

1. 你需要开通[【微信公众平台-新的功能-模板消息】](https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Template_Message_Interface.html)。
2. 请确保**微信公众平台-设置与开发-接口权限**中所有接口均已开通且应有配置已全部设置。

>**获取access_token**
>
>![haystack666/WeChatQRLogin](https://haydata-cd.oss-cn-chengdu.aliyuncs.com/github/WeChatQRLogin/WeChatQRLogin-access_token.png)

---

>**生成带参数的二维码**
>
>![haystack666/WeChatQRLogin](https://haydata-cd.oss-cn-chengdu.aliyuncs.com/github/WeChatQRLogin/WeChatQRLogin-createQRCode.png)

---

>**获取用户基本信息**
>
>![haystack666/WeChatQRLogin](https://haydata-cd.oss-cn-chengdu.aliyuncs.com/github/WeChatQRLogin/WeChatQRLogin-getUserInfo.png)

---

>**网页授权获取用户基本信息**
>
>![haystack666/WeChatQRLogin](https://haydata-cd.oss-cn-chengdu.aliyuncs.com/github/WeChatQRLogin/WeChatQRLogin-webGetInfo.png)
点击后面的**修改**
⬇️⬇️⬇️⬇️⬇️
>**网页授权域名**
>
>![haystack666/WeChatQRLogin](https://haydata-cd.oss-cn-chengdu.aliyuncs.com/github/WeChatQRLogin/WeChatQRLogin-bindDomain.png)

---

>**微信公众平台-设置与开发-基本配置-服务器配置**
>
>![haystack666/WeChatQRLogin](https://haydata-cd.oss-cn-chengdu.aliyuncs.com/github/WeChatQRLogin/WeChatQRLogin-TokenVerification.png)



## 使用方法

**下载项目到本地，更改其中某些文件的一些代码即可**

WeChat.class.php：

```php
protected $appid = "YOUR-APPID"; //改为你的appid
protected $secret = "YOUR-SECRET"; //改为你的secret
...
...
$this->mysqli = new mysqli('主机地址','用户名','密码','数据库名'); //改为你的数据库相关信息
```

创建表

```sql
CREATE TABLE IF NOT EXISTS wechat_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    openid VARCHAR(50) NOT NULL UNIQUE,
    nickname VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS login_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    openid VARCHAR(50) NOT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip VARCHAR(50),
    user_agent TEXT,
    login_location TEXT,
    event_key VARCHAR(50)
);

```

# 结束
**至此，不出意外你应该可以正常使用了，如果我文档中漏掉了什么地方，请指正！**
