<?php
/**
*   配置账号信息
*/

class WxConfig
{
    const APPID     = 'xxx';
    const APPSECRET = 'xxx';
    const MCHID     = 'xxx';
    const PAYKEY    = 'xxx';

    //证书路径,注意应该填写绝对路径
    const SSLCERT_PATH  = '/xxx/xxx/xxxx/WxPayPubHelper/cacert/apiclient_cert.pem';
    const SSLKEY_PATH   = '/xxx/xxx/xxxx/WxPayPubHelper/cacert/apiclient_key.pem';

    const NOTIFY_URL    = 'http://www.xxxxxx.com/demo/notify_url.php';
    const CURL_TIMEOUT  = 30;
}

?>