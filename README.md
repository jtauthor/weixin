
    微信支付

    官方提供的的api很不友好，这里重新设计了，非常简单清晰明了！

    var paramObj = {
        code        : 'weixinCode',
        product_id  : 'xxx',
        price       : '1',
        title       : '打赏一下'
    };

    var url = 'http://xxxx.com/api/get_wxpay_args.php?' + $.param(paramObj);

    访问这个请求 获得支付需要的参数，是不是特别特别的简单？！