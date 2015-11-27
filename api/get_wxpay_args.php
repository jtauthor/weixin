<?php
    //微信支付
    header("Content-type: application/json; charset=utf-8"); 
    header("Access-Control-Allow-Origin:*");

    include_once("wx.pay.php");
    $wxPay = new WxPay();

    $getUrlParam = function( $key ){
        if( isset($_GET[$key]) ) {
            return $_GET[$key];
        }elseif(isset($_POST[$key])){
            return $_POST[$key];
        }else{
            $errObj['code'] = -1;
            $errObj['msg']  = $key.'不存在'; 
            $errObj['data'] = null;
            echo Util::getJsonString($errObj); 
            exit; 
        }
    };

    $code       = $getUrlParam('code');
    $product_id = $getUrlParam('product_id');
    $price      = $getUrlParam('price');
    $title      = $getUrlParam('title');

    $unifiedOrder["product_id"]  = $product_id;
    $unifiedOrder["total_fee"]   = $price;
    $unifiedOrder["body"]        = $title;

    $wxPayArgsObj = $wxPay->getParameters($code, $unifiedOrder);

    echo Util::getJsonString($wxPayArgsObj); 

?>
