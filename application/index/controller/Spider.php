<?php
namespace app\index\controller;

use think\Db;
use think\Request;
class Spider extends \think\Controller
{

    public function __construct()
    {

        $GLOBALS['pcTop100Info'] = 'http://apis.5118.com/keywordrank/baidupc';
//        $GLOBALS['apiKey'] = 'BDD7A640B563421BA55A3B3BC355EC30';
        $GLOBALS['apiKey'] = '937A3CA669AE4CA3A2CD7D640CA59EF7';
        //key2: 937A3CA669AE4CA3A2CD7D640CA59EF7
    }

    /*
     * 外部访问接口
     *
     *      爬取公众号文章
     */
    public function _apiGetWxArticle(){

        header("Content-type: text/html; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

        // 1.构造包头，添加APIKEY
        $header = array(
            "Content-Type:application/x-www-form-urlencoded", //post请求
            'Authorization: APIKEY '.$GLOBALS['apiKey']
        );
        $header = array(

            "Content-Type:application/x-www-form-urlencoded", //post请求
            "Connection: keep-alive",
            'Referer:http://www.baidu.com',
            'User-Agent: Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; BIDUBrowser 2.6)'
        );

        //2.封装参数body
        /*
         * 关键词:keywords
         * 排名:checkrow
         */

        $url = 'https://mp.weixin.qq.com/s?timestamp=1509969725&src=3&ver=1&signature=Jo5tsSF9FNTbrM5rYgffZqwo7AfBGCCcwu7iIEKdHVjDtCAVTYhFeAf8Wohx3Tl9AezmZtSChCGE-6YBlHLjAd3g7JtVZWPJSKyLTPzZ-dJ9IaI*ADCYUKmrI-t*yyA3oZ1WBE9PvpiXShhP3*NGdP*aCt-ABHpTuXdEm4m9uVo=';
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


        // 4. 执行并获取HTML文档内容，可用echo输出内容
        $output = curl_exec($ch);

        var_dump($output);
        exit();

        //5.结束
        curl_close($ch);
    }

    /*
     * 外部访问接口
     *
     *
     */
    public function _apiGetTopInfo(){

        // 是否为 POST 请求
        if (Request::instance()->isPost()){
            $postArr = $_POST;
            //确定keyword值
            $kid = strFilter($postArr['kid']);
            $keywordInfo = Db::name('keywords') -> where('id',$kid) -> field('keyword') -> find();
            //更新时间
            $this -> updateKeywordsTime($kid);
            $taskIdArr = $this -> getTopTaskid($keywordInfo['keyword']);
            if($taskIdArr['status'] == 1){
                sleep(29);
                $taskDataArr = $this -> getTopTaskData($taskIdArr['msg']);

                if($taskDataArr['status'] == 1){
                    $webRes = $this -> createWebsite($taskDataArr,$kid);
                    if($webRes == 1){
                        $ajax['status'] = 1;
                        $ajax['msg'] = '抓取成功';
                        return $ajax;
                    }else{
                        $ajax['status'] = 0;
                        $ajax['msg'] = '抓取为空';
                        return $ajax;
                    }
                }else{
                    $ajax['status'] = $taskDataArr['status'];
                    $ajax['msg'] = $taskDataArr['msg'];
                    return $ajax;
                }
                exit();
            }else{
                $ajax['status'] = $taskIdArr['status'];
                $ajax['msg'] = $taskIdArr['msg'];
                return $ajax;
            }

        }else{
            $ajax['status'] = 2;
            $ajax['msg'] = '请求错误，请重新请求';
            return $ajax;
        }

    }

    public function updateKeywordsTime($kid){
        $updateData = array(
            'upload_time' => time()
        );
        $res = Db::name('keywords') -> where('id',$kid) -> update($updateData);
        return $res;
    }

    /*
     * 采集插入数据库
     */
    public function createWebsite($taskData,$kid){
        //数据抓取中

       if($taskData['status'] != 1){
           return 3;
       }
        $keyword_monitor = $taskData['msg']->data->keyword_monitor;
            //查询关键词id 行业id
            $idsArr = Db::name('keywords') -> where('id',$kid) -> field('id,bid') ->find();
            //插入数据
            $keyword = $keyword_monitor[0]->keyword;
            $insertTask['search_engine'] = $keyword_monitor[0]->search_engine;
            $insertTask['ip'] = $keyword_monitor[0]->ip;

            $ranks = $keyword_monitor[0]->ranks;

            if(sizeof($ranks) > 0){
                foreach($ranks as $k => $v){

                    //验证链接是否存在
                    $res = Db::name('website') -> where('page_url',$v->page_url) -> find();
                    if(!$res){
                        $insertTask['title'] = $v->page_title;
                        $insertTask['page_url'] = $v->page_url;
                        $insertTask['rank'] = $v->rank;
                        //获取主域名部分数组
                        $urlArr = parse_url($insertTask['page_url']);
                        $insertTask['js_code'] = 'javascript:void(0);document.write("<a href=\''.$urlArr['scheme'].'://'.$urlArr['host'].'\' target=\'_blank\'>click</a>");';
                        $insertTask['top_rank'] = $v->top100;
                        $insertTask['site_weight'] = $v->site_weight;
                        $insertTask['kid'] = $idsArr['id'];
                        $insertTask['bid'] = $idsArr['bid'];
                        $insertTask['add_time'] = time();

                        $res = Db::name('website') -> insert($insertTask);
                    }
                }
                return 1;
            }else{
                return 2;
            }

    }

    /**
     *  5118
     *  百度-PC-前50网站信息
     *  步骤一：批量提交查询请求，并获得提取结果ID
     *
     *  keywords=电脑|CPU 多个用竖线隔开
     *  checkrow=50
     */
    public function getTopTaskid($keywords){
        header("Content-type: text/html; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

        // 1.构造包头，添加APIKEY
        $header = array(
            "Content-Type:application/x-www-form-urlencoded", //post请求
            'Authorization: APIKEY '.$GLOBALS['apiKey']
        );

        //2.封装参数body
        /*
         * 关键词:keywords
         * 排名:checkrow
         */
        $parameter = 'keywords='.$keywords.'&checkrow=100';


        $url = $GLOBALS['pcTop100Info'];
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


        if($output->errcode == 0){
            $ajaxReturn['status'] = 1;
            $taskid = $output->data->taskid;
            $ajaxReturn['msg'] = $taskid;
            curl_close($ch);

            return $ajaxReturn;
        }else{

            $ajaxReturn['status'] = $output->errcode;
            $ajaxReturn['msg'] = $output->errmsg;
            return $ajaxReturn;

        }



    }

    /**
     *  5118
     *  百度-PC-前50网站信息
     *  步骤二：根据提取结果ID查询数据是否采集完成，如果完成则得到Json格式结果
     */
    public function getTopTaskData($taskid){
        header("Content-type: text/html; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

        // 1.构造包头，添加APIKEY
        $header = array(
            "Content-Type:application/x-www-form-urlencoded", //post请求
            'Authorization: APIKEY '.$GLOBALS['apiKey']
        );

        //2.封装参数body
        /*
         * 关键词:keywords
         * 排名:checkrow
         */
        $parameter = 'taskid='.$taskid;


        $url = $GLOBALS['pcTop100Info'];
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

        if($output->errcode == 0){
            $ajaxReturn['status'] = 1;
            $taskData= $output;
            $ajaxReturn['msg'] = $taskData;
            curl_close($ch);

            return $ajaxReturn;

        }else{

            $ajaxReturn['status'] = $output->errcode;
            $ajaxReturn['msg'] = $output->errmsg;
            return $ajaxReturn;

        }


    }

}