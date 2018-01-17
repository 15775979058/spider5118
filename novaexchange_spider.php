<?php 



	$GLOBALS['url'] = 'https://novaexchange.com/remote/v2/';
	$API_KEY = "4fossnbv8ntx6v4f20pc43til15r1ut6m4ao0tm4";
	$API_SECRET = "jgpu46egq7i96i2oj0er5rojhnh9ym52wb1y42wt3c8ytv7qth";

    header("content-type': 'application/x-www-form-urlencoded");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

        // 1.构造包头，添加APIKEY
        $header = array(
            "Content-Type:application/x-www-form-urlencoded", //post请求
      
        );
        $url = $GLOBALS['url'].'/getbalances?nonce='.time();
        $signature = hash_hmac('sha1',$url,$API_SECRET, true);

        //2.封装参数body
        /*
         * 关键词:keywords
         * 排名:checkrow
         */
        $parameter = 'apikey='.$API_KEY.'&signature='.$signature;


        $url = $GLOBALS['url'];
        $ch = curl_init();


        // 3. 设置选项
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_URL, $url);  // 设置要抓取的页面地址
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);              // 抓取结果直接返回（如果为0，则直接输出内容到页面）
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, 0);                      // 不需要页面的HTTP头
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameter); //$data是每个接口的json字符串


        // 4. 执行并获取HTML文档内容，可用echo输出内容
        $output = json_decode(curl_exec($ch));

        print_r($output);
        exit();

        if($output->errcode == 0){
            $ajaxReturn['status'] = 1;
            $taskData= $output;
            $ajaxReturn['msg'] = $taskData;
            curl_close($ch);

            echo json_encode($ajaxReturn);

        }else{

            $ajaxReturn['status'] = $output->errcode;
            $ajaxReturn['msg'] = $output->errmsg;
            echo json_encode($ajaxReturn);

        }
?>