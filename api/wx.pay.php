<?php
/**
 * ====================================
 *             【微信支付】
 * ====================================
 */


include_once("wx.config.php");


/**
 * 工具库
 */
class Util {
    /************************************************************** 
     * 
     *  将数组转换为JSON字符串（兼容中文）解决中文乱码 
     *  @param  array   $array 要转换的数组 
     *  @return string  转换得到的json字符串 
     *  @access public 
     * 
     *************************************************************/
    function getJsonString($array) { 
        /************************************************************** 
        * 
        *  使用特定function对数组中所有元素做处理 
        *  @param  string  &$array     要处理的字符串 
        *  @param  string  $function   要执行的函数 
        *  @return boolean $apply_to_keys_also     是否也应用到key上 
        *  @access public 
        * 
        *************************************************************/
        $arrayRecursive = function(&$array, $function, $apply_to_keys_also = true){ 
            static $recursive_counter = 0; 
                if (++$recursive_counter > 1000) { 
                    die('possible deep recursion attack'); 
                } 
                foreach ($array as $key => $value) { 
                    if (is_array($value)) { 
                            arrayRecursive($array[$key], $function, $apply_to_keys_also); 
                    } else { 
                        $array[$key] = is_string($value) ? $function($value) : $value; 
                    }
                    if ($apply_to_keys_also && is_string($key)) { 
                        $new_key = $function($key); 
                        if ($new_key != $key) { 
                            $array[$new_key] = $array[$key]; 
                            unset($array[$key]); 
                        } 
                    } 
                } 
                $recursive_counter--; 
        }; 
        
        $arrayRecursive($array, 'urlencode', true); 
        $json = json_encode($array); 
        return urldecode($json); 
    } 
      
    //产生随机字符串，最长32位
    function randomString( $length = 32 ) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
        $str ="";
        for ( $i = 0; $i < min($length, 32); $i++ )  {  
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
        }  
        return $str;
    }

    //访问远程，获得数据
    function httpGet($url, $timeout=30) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_URL, $url);

        $res = curl_exec($ch);
        curl_close($ch);     
        return $res;
    }
    
    //访问远程，获得json对象数据
    function httpGetJson($url){ 
        return json_decode( $this->httpGet($url), true);
    }

    //访问远程，获得json字符串数据
    function httpGetJsonString($url){ 
        return json_encode( $this->httpGet($url) );
    }

    //格式化参数，签名过程需要使用
    function formatBizQueryParaMap($paraMap, $urlencode){
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v)
        {
            if($urlencode)
            {
               $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar;
        if (strlen($buff) > 0) 
        {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }

    //获取当前url
    function getCurUrl(){
        $pageURL = 'http';
        
        if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
            $pageURL .= "s";
        }
        
        $pageURL .= "://";
        $pageURL .= $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
        return $pageURL;
    }
    
    //array转xml
    function arrayToXml($arr){
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
             if (is_numeric($val)) {
                 $xml.="<".$key.">".$val."</".$key.">"; 
        
             } else {
                 $xml.="<".$key."><![CDATA[".$val."]]></".$key.">"; 
             }
        }
        $xml.="</xml>";
        return $xml; 
    }
    
    //将xml转为array
    function xmlToArray($xml){
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);        
        return $array_data;
    }

    //以post方式提交xml到对应的接口url 默认不使用证书
    function postXmlCurl($xml, $url, $timeout=30, $isSSL=false){
        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, false);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if($isSSL==true){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            //默认格式为PEM，可以注释
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, WxConfig::SSLCERT_PATH);
            //默认格式为PEM，可以注释
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, WxConfig::SSLKEY_PATH);
        }
        //post提交方式
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else { 
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error"."<br>"; 
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
    }
}


/**
 *     微信支付——H5网页端调起支付接口
 */
class WxPay extends Util{
    private $APPI           = WxConfig::APPID;
    private $APPSECRE       = WxConfig::APPSECRET;
    private $MCHI           = WxConfig::MCHID;//商户号    
    private $PAYKEY         = WxConfig::PAYKEY;//支付秘钥
    private $NOTIFY_URL     = WxConfig::NOTIFY_URL; //通知 
    private $curl_timeout   = WxConfig::CURL_TIMEOUT;
    private $unifiedOrder   = array(); //统一订单 
    
    function __construct(){
        $this->unifiedOrder["appid"]            = $this->APPID;
        $this->unifiedOrder["mch_id"]           = $this->MCHID;
        $this->unifiedOrder["notify_url"]       = $this->NOTIFY_URL;
        $this->unifiedOrder["trade_type"]       = "JSAPI";    
        $this->unifiedOrder["spbill_create_ip"] = $_SERVER['REMOTE_ADDR'];//终端ip        
        $this->unifiedOrder["nonce_str"]        = $this->randomString();//随机字符串    
        $this->unifiedOrder["out_trade_no"]     = 'H5_'.strval(time()); //商户订单号 
    }

    //获取prepay_id
    function getPrepayId($code, $orderInfo ){
        $tempObj = $this->getOpenId( $code );
        if($tempObj['code']!=0){ return $tempObj; }

        //通过code获取openid
        $this->unifiedOrder["openid"] = $tempObj['data']['openid'];        
        //参数合并
        $this->unifiedOrder = array_merge((array) $this->unifiedOrder, (array) $orderInfo);

        //订单参数校验
        $orderParamCheck = function( $unifiedOrder ){
            try {
                if($unifiedOrder["out_trade_no"] == null) {
                    throw new Exception("缺少统一支付接口必填参数out_trade_no！"."<br>");
                }elseif($unifiedOrder["body"] == null){
                    throw new Exception("缺少统一支付接口必填参数body！"."<br>");
                }elseif ($unifiedOrder["total_fee"] == null ) {
                    throw new Exception("缺少统一支付接口必填参数total_fee！"."<br>");
                }elseif ($unifiedOrder["notify_url"] == null) {
                    throw new Exception("缺少统一支付接口必填参数notify_url！"."<br>");
                }elseif ($unifiedOrder["trade_type"] == null) {
                    throw new Exception("缺少统一支付接口必填参数trade_type！"."<br>");
                }elseif ($unifiedOrder["trade_type"] == "JSAPI" && $unifiedOrder["openid"] == NULL){
                    throw new Exception("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！"."<br>");
                } 
            } catch (Exception $e) {    
                die( $e->getMessage() ); 
            }
        };
        $orderParamCheck( $this->unifiedOrder );
        $this->unifiedOrder["sign"] = $this->getSign($this->unifiedOrder);
        $xml = $this->arrayToXml($this->unifiedOrder);
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $data_xml = $this->postXmlCurl($xml, $url, $this->curl_timeout);
        $data_arr = $this->xmlToArray($data_xml);

        if(array_key_exists('result_code', $data_arr) && $data_arr['result_code']=='SUCCESS'){
            $json['code'] = 0;
            $json['msg']  = 'success';
            $json['data'] = $data_arr;
            return $json;
        }

        $errObj['code'] = -1; 
        $errObj['msg']  = 'prepay_id获取失败';
        $errObj['data'] = $data_arr;
        return $errObj;
    }

    //生成可以获得code的url
    function getCodeUrl($redirectUrl){ 
        $obj["appid"]           = $this->APPID;
        $obj["redirect_uri"]    = urlencode( isset($redirectUrl) ? $redirectUrl : $_SERVER['HTTP_REFERER'] ); 
        $obj["response_type"]   = "code";
        $obj["scope"]           = "snsapi_base";
        $obj["state"]           = "STATE"."#wechat_redirect";
        $bizString = $this->formatBizQueryParaMap($obj, false);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
    }
    
    //获取openid 返回对象
    function getOpenid( $code ){
        $getOpenidUrl = function($APPID, $APPSECRET, $code, $formatBizQueryParaMap){
            $obj["appid"]       = $APPID;
            $obj["secret"]      = $APPSECRET;
            $obj["code"]        = $code;
            $obj["grant_type"]  = "authorization_code";
            $bizString = $formatBizQueryParaMap(obj, false);
            return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
        };

        $url = $getOpenidUrl($this->APPID, $this->APPSECRET, $code, $this->formatBizQueryParaMap);

        $data = $this->httpGetJson( $url );
        if(array_key_exists('errcode', $data) && $data['errcode']!=0){
            $errObj['code'] = -1; 
            $errObj['msg']  = 'openid获取失败';
            $errObj['data'] = $data;
            return $errObj;
        }

        $json['code'] = 0;
        $json['msg']  = 'success';
        $json['data'] = $data;
        return $json;
    }

    //生成签名
    function getSign($Obj){
        foreach ($Obj as $k => $v){
            $Param[$k] = $v;
        }
        ksort($Param);
        $String = $this->formatBizQueryParaMap($Param, false);
        $String = $String."&key=".$this->PAYKEY;
        $String = md5($String);
        $result = strtoupper($String);
        return $result;
    }

    //获取支付需要的全部参数 
    //code 网页授权code字符串，orderInfo 订单信息对象
    function getParameters($code='', $orderInfo){
        if($code==''){
            $errObj['code'] = -1; 
            $errObj['msg']  = '网页授权code不存在';
            $errObj['data'] = null;
            return $errObj;
        }

        //步骤1：通过code和订单信息，获得prepay_id
        $tempObj = $this->getPrepayId( $code, $orderInfo );        
        if($tempObj['code']!=0){ return $tempObj; }
        $prepay_id = $tempObj['data']['prepay_id'];

        //步骤2：获取调用微信支付的全部参数值
        $obj["appId"]       = $this->APPID;
        $obj["timeStamp"]   = strval(time());
        $obj["nonceStr"]    = $this->randomString();
        $obj["package"]     = "prepay_id=$prepay_id";
        $obj["signType"]    = "MD5";
        $obj["paySign"]     = $this->getSign($obj);
        
        $json['code'] = 0;
        $json['msg']  = 'success';
        $json['data'] = $obj;
        return $json;
    }
}

?>
