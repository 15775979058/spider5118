<?php
namespace app\index\controller;

/**
 *  对接小程序业务5118功能数据接口
 */
use think\Db;
use think\Request;
class Wxspider extends \think\Controller
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
        //定义公有变量

        //百度-PC-排名查询
        $GLOBALS['pcRankSelect'] = 'http://apis.5118.com/morerank/baidupc';
        //百度-PC-排名查询KEY
        $GLOBALS['pcRankSelectKey'] = '73B488A4C23E470E839C09560EE411C7';
        //查询返回条数
        $GLOBALS['pcRankNum'] = 30;


        // 百度-PC-网站排名词导出
        $GLOBALS['pcKeywordUrl'] = 'http://apis.5118.com/keyword/baidupc';
        // 百度-PC-网站排名词导出KEY
        $GLOBALS['pcKeywordKey'] = '88FDC9AD60244FE9ADE6E8DA7A6E2546';


        //公众号文章地址
        $GLOBALS['publicArticleUrl'] =  'http://www.gzhshoulu.wang/account_ymszx688.html';
        //公众号根目录
        $GLOBALS['publicArticleRoot'] = 'http://www.gzhshoulu.wang/';

    }

     /*
      * 外部访问接口
      *     百度-PC-网站排名词导出A
      *     对接微信输入域名
      *
      *     参数:
      *         domainUrl:主域名
      *         oppenId:微信用户唯一标识
      */
    public function _apiWxGetDomainurl(){

        // 是否为 POST 请求
        if (Request::instance()->isPost()) {
            $postArr = $_POST;
            //接收查询域名
            $domainArr = strFilter($postArr['domainUrl']);
            //接收用户唯一标识
            $oppenId = $postArr['oppenId'];

            if($oppenId == ''){
                $ajax['status'] = 0;
                $ajax['msg'] = 'lack oppenId';
                return json_encode($ajax);
            }
           

            //判断经过parse_url是否存在host如存在直接取；如不存在取path
            //$domain为主要进行查询的连接
            $S = parse_url($domainArr);
            if(isset($S['host'])){
                $domainUrl = strtolower($S['host']); //取域名部分
            }else{
                $S = strtolower($S['path']);     //链接部分

                $strlen=strlen($S);              //全部字符长度
                $tp=strpos("$S","/");           //'/'之前的字符长度
                $domainUrl=substr($S,-$strlen,$tp);  //从头开始截取到指字符位置。

                if($domainUrl == ""){
                    $domainUrl = $domainArr;
                }
            }

            $rankExportInfo = $this -> isDbRankexport(strtolower($domainUrl),$oppenId);
            return $rankExportInfo;

        }
    }

    /**
     * 5118
     *     百度-PC-网站排名词导出B
     *
     *     查询input_bdpc_rankexport表是否存在链接，如存在则判断时间是否超过1天，
     *     如超过进行进行接口请求，没有超过进行数据插入
     */
    protected function isDbRankexport($domainUrl,$oppenId){

        $rankWheres = array(
            'domainurl' => $domainUrl,
            'is_delete' => 0
        );
        $rankExportInfo = Db::name('input_bdpc_rankexport') -> where($rankWheres) ->field('id,add_time,use_number')->order('add_time desc') -> find();

        if(sizeof($rankExportInfo) <= 0){
            //进行数据插入
            $rankInsertArr = array(
                'domainurl' => $domainUrl,
                'add_time' => time(),
                'oppenid' => $oppenId,
                'use_number' => 1,
            );
            $dbRes = Db::name('input_bdpc_rankexport') -> insert($rankInsertArr);
            $lastId = Db::name('input_bdpc_rankexport') -> getLastInsID();
            /*
             * 进行请求接口操作
             */
            $keywordsInfoArr = $this -> getKeywordInfoInterface($domainUrl,$lastId);
            return $keywordsInfoArr;
        }else{
            //进行时间判断是否超过1天
            $time = time();
            //请求次数加1
            $upNum = $rankExportInfo['use_number']+1;
            if($time - $rankExportInfo['add_time'] >= 86400){
                /*
                 * 进行请求接口操作
                 */
                $inputUpdate = array(
                    'use_number' => $upNum,
                    'add_time' => time()
                );
                Db::name('input_bdpc_rankexport') -> where('id',$rankExportInfo['id']) -> update($inputUpdate);
                $keywordsInfoArr = $this -> getKeywordInfoInterface($domainUrl,$rankExportInfo['id']);
                return $keywordsInfoArr;
            }else{
                /*
                 * 根据$rankExportInfo['id']去output查询一遍
                 */
                $inputUpdate = array(
                    'use_number' => $upNum,
                    'add_time' => time()
                );
                Db::name('input_bdpc_rankexport') -> where('id',$rankExportInfo['id']) -> update($inputUpdate);
                $keywordsInfoArr = Db::name('output_bdpc_rankexport') -> where('rid',$rankExportInfo['id']) -> select();
                if(sizeof($keywordsInfoArr) == 0){
                    $keywordsInfoArr = $this -> getKeywordInfoInterface($domainUrl,$rankExportInfo['id']);
                }

                //返回数组
                $returnRankArr = array();
                if(sizeof($keywordsInfoArr) <= 20){
                    foreach($keywordsInfoArr as $key => $val){
                        $returnTask['keyword'] = '关键词';
                        $returnTask['keyword_value'] = $keywordsInfoArr[$i]['keyword'];
                        $returnTask['rank'] = '排名';
                        $returnTask['rank_value'] = $keywordsInfoArr[$i]['rank'];
                        $returnTask['bidding'] = '竞价公司数量';
                        $returnTask['bidding_value'] = $keywordsInfoArr[$i]['bidword_company_count'];
                        $returnTask['index'] = '指数';
                        $returnTask['index_value'] = $keywordsInfoArr[$i]['baidu_index'];
                        $returnTask['searchnum'] = 'PC检索量';
                        $returnTask['searchnum_value'] = $keywordsInfoArr[$i]['bidword_pcpv'];
                        $returnTask['mobilesearch'] = '移动检索量';
                        $returnTask['mobilesearch_value'] = $keywordsInfoArr[$i]['bidword_wisepv'];
                        $returnTask['title'] = '标题';
                        $returnTask['title_value'] = $keywordsInfoArr[$i]['title'];
                        array_push($returnRankArr,$returnTask);
                    }
                }else{
                    for($i=1; $i<20; $i++){
                        //返回小程序数组
                        $returnTask['keyword'] = '关键词';
                        $returnTask['keyword_value'] = $keywordsInfoArr[$i]['keyword'];
                        $returnTask['rank'] = '排名';
                        $returnTask['rank_value'] = $keywordsInfoArr[$i]['rank'];
                        $returnTask['bidding'] = '竞价公司数量';
                        $returnTask['bidding_value'] = $keywordsInfoArr[$i]['bidword_company_count'];
                        $returnTask['index'] = '指数';
                        $returnTask['index_value'] = $keywordsInfoArr[$i]['baidu_index'];
                        $returnTask['searchnum'] = 'PC检索量';
                        $returnTask['searchnum_value'] = $keywordsInfoArr[$i]['bidword_pcpv'];
                        $returnTask['mobilesearch'] = '移动检索量';
                        $returnTask['mobilesearch_value'] = $keywordsInfoArr[$i]['bidword_wisepv'];
                        $returnTask['title'] = '标题';
                        $returnTask['title_value'] = $keywordsInfoArr[$i]['title'];
                        array_push($returnRankArr,$returnTask);
                    }
                }
                //返回查询值
                $ajax['status'] = 1;
                $ajax['msg'] = $returnRankArr;
                return json_encode($ajax);
            }
        }

    }


    /*
     * 外部访问接口
     *     百度-PC-网站排名词导出C
     *     获取PC排名信息
     */
    protected function getKeywordInfoInterface($domainUrl,$rid){

        //查询taskId
        $getKeywordArr = $this -> getKeywordExport($domainUrl);
        $getKeywordInfo = json_decode($getKeywordArr,true);

        if($getKeywordInfo['status'] == 1) {

                $webRes = $this -> createKeywordInfo($getKeywordInfo,$rid);
                $webResDecode = json_decode($webRes,true);

                if($webResDecode['code'] == 1){
                    $ajax['status'] = 1;
                    $ajax['msg'] = $webResDecode['data'];
                    return json_encode($ajax);
                }else{
                    $ajax['status'] = 0;
                    $ajax['msg'] = $webResDecode['data'];
                    return json_encode($ajax);
                }


        }
    }

    /**
     *  5118
     *  百度-PC-网站排名词导出E
     *  采集插入数据库
     *  不用链接判断，直接插入
     */
    protected function createKeywordInfo($taskData,$rid){
        //数据抓取中

        if($taskData['status'] != 1){
            $ajax = array(
                'code' => 3,
                'data' => '数据源抓取不成功'
            );
            return json_encode($ajax);
        }

        $keywordExportArr = $taskData['msg']['data']['baidupc'];

        //插入数据
        if(sizeof($keywordExportArr) > 0){
            //返回插入数组
            $returnKeywordsArr = array();
            //只装入50个，计数变量
            $rCount = 0;
            foreach($keywordExportArr as $k => $v){
                if($v['baidu_url']){
                    //插入数据库数组
                    $insertTask['keyword'] = $v['keyword'];
                    $insertTask['rank'] = $v['rank'];
                    $insertTask['baidu_index'] = $v['baidu_index'];
                    $insertTask['title'] = $v['page_title'];
                    $insertTask['page_url'] = $v['baidu_url'];
                    $insertTask['bidword_company_count'] = $v['bidword_companycount'];
                    $insertTask['add_time'] = time();
                    $insertTask['bidword_kwc'] = $v['bidword_kwc'];
                    $insertTask['bidword_pcpv'] = $v['bidword_pcpv'];
                    $insertTask['bidword_wisepv'] = $v['bidword_wisepv'];
                    $insertTask['rid'] = $rid;

                    $res = Db::name('output_bdpc_rankexport') -> insert($insertTask);

                    //返回小程序数组
                    $returnTask['keyword'] = '关键词';
                    $returnTask['keyword_value'] = $insertTask['keyword'];
                    $returnTask['rank'] = '排名';
                    $returnTask['rank_value'] = $insertTask['rank'];
                    $returnTask['bidding'] = '竞价公司数量';
                    $returnTask['bidding_value'] = $insertTask['bidword_company_count'];
                    $returnTask['index'] = '指数';
                    $returnTask['index_value'] = $insertTask['baidu_index'];
                    $returnTask['searchnum'] = 'PC检索量';
                    $returnTask['searchnum_value'] = $insertTask['bidword_pcpv'];
                    $returnTask['mobilesearch'] = '移动检索量';
                    $returnTask['mobilesearch_value'] = $insertTask['bidword_wisepv'];
                    $returnTask['title'] = '标题';
                    $returnTask['title_value'] = $insertTask['title'];

                    $rCount++;
                    if($rCount <= 20){
                        array_push($returnKeywordsArr,$returnTask);
                    }

                }
            }
            $ajax = array(
                'code' => 1,
                'data' => $returnKeywordsArr
            );
            return json_encode($ajax);

        }else{
            $ajax = array(
                'code' => 2,
                'data' => '数据库插入失败'
            );
            return json_encode($ajax);
        }

    }

    /**
     *  5118
     *
     *  百度-PC-网站排名词导出D
     *  采集插入数据库
     */
    protected function getKeywordExport($domainUrl){
        header("Content-type: text/html; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

        // 1.构造包头，添加APIKEY
        $header = array(
            "Content-Type:application/x-www-form-urlencoded", //post请求
            'Authorization: APIKEY '.$GLOBALS['pcKeywordKey']
        );

        //2.封装参数body
        /*
         * 关键词:keywords
         * 排名:checkrow
         */
        $parameter = 'url='.$domainUrl;


        $url = $GLOBALS['pcKeywordUrl'];
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

            return json_encode($ajaxReturn);

        }else{

            $ajaxReturn['status'] = $output->errcode;
            $ajaxReturn['msg'] = $output->errmsg;
            return json_encode($ajaxReturn);

        }
    }




    /**
    * 外部访问接口
    *     百度-PC-排名查询A
    *     获取PC排名信息
    */
    public function _apiWxGetKeyword(){
        // 是否为 POST 请求
        if (Request::instance()->isPost()) {
            $postArr = $_POST;
            //接收查询域名
            $domainArr = strFilter($postArr['domainUrl']);
            //接收关键词
            $keyword = strFilter($postArr['keyword']);
            //接收用户唯一标识
            $oppenId = $postArr['oppenId'];





            if($oppenId == ''){
                $ajax['status'] = 0;
                $ajax['msg'] = 'lack oppenId';
                return json_encode($ajax);
            }

            //判断经过parse_url是否存在host如存在直接取；如不存在取path
            //$domain为主要进行查询的连接
            $S = parse_url($domainArr);
            if(isset($S['host'])){
                $domainUrl = strtolower($S['host']); //取域名部分
            }else{
                $S = strtolower($S['path']);     //链接部分

                $strlen=strlen($S);              //全部字符长度
                $tp=strpos("$S","/");           //'/'之前的字符长度
                $domainUrl=substr($S,-$strlen,$tp);  //从头开始截取到指字符位置。

                if($domainUrl == ""){
                    $domainUrl = $domainArr;
                }
            }

            $rankExportInfo = $this -> isDbRankeyword($keyword,strtolower($domainUrl),$oppenId);
            return $rankExportInfo;

        }
    }

      /*
       * 外部访问接口
       *     百度-PC-排名查询B
       *     获取PC排名信息
       */
    protected function isDbRankeyword($keyword,$domainUrl,$oppenId){
        $rankWheres = array(
            'domainurl' => $domainUrl,
            'keyword' => $keyword,
            'is_delete' => 0
        );
        $rankExportInfo = Db::name('input_bdpc_morerank') -> where($rankWheres) ->field('id,add_time,use_number')->order('add_time desc') -> find();

        if(sizeof($rankExportInfo) <= 0){
            //进行数据插入
            $rankInsertArr = array(
                'domainurl' => $domainUrl,
                'add_time' => time(),
                'oppenid' => $oppenId,
                'keyword' => $keyword,
                'use_number' => 1,
            );
            $dbRes = Db::name('input_bdpc_morerank') -> insert($rankInsertArr);
            $lastId = Db::name('input_bdpc_morerank') -> getLastInsID();
            /*
             * 进行请求接口操作
             */
            $keywordsInfoArr = $this -> getRankInfoInterface($keyword,$domainUrl,$lastId);
            return $keywordsInfoArr;
        }else{
            //进行时间判断是否超过1天
            $time = time();
            //请求次数加1
            $upNum = $rankExportInfo['use_number']+1;
            if($time - $rankExportInfo['add_time'] >= 86400){
                /*
                 * 进行请求接口操作
                 */
                $inputUpdate = array(
                    'use_number' => $upNum,
                    'add_time' => time()
                );
                Db::name('input_bdpc_morerank') -> where('id',$rankExportInfo['id']) -> update($inputUpdate);
                $keywordsInfoArr = $this -> getRankInfoInterface($keyword,$domainUrl,$rankExportInfo['id']);
                return $keywordsInfoArr;
            }else{
                /*
                 * 根据$rankExportInfo['id']去output查询一遍
                 */
                $inputUpdate = array(
                    'use_number' => $upNum,
                    'add_time' => time()
                );
                Db::name('input_bdpc_morerank') -> where('id',$rankExportInfo['id']) -> update($inputUpdate);
                $keywordsInfoArr = Db::name('output_bdpc_morerank') -> where('rid',$rankExportInfo['id']) -> select();
                if(sizeof($keywordsInfoArr) == 0){
                    $keywordsInfoArr = $this -> getRankInfoInterface($keyword,$domainUrl,$rankExportInfo['id']);
                }

                //返回数组 -- 判断是否大于20组，如果大于则只取前20组
                $returnRankArr = array();
                if(sizeof($keywordsInfoArr) <= 20){
                        foreach($keywordsInfoArr as $key => $val){
                            $returnTask['rank'] = '排名';
                            $returnTask['rank_value'] = $keywordsInfoArr[$key]['rank'];
                            $returnTask['search'] = '搜索引擎';
                            $returnTask['search_value'] = '百度';
                            $returnTask['site_weight'] = '权重';
                            $returnTask['site_weight_value'] = $keywordsInfoArr[$key]['site_weight'];
                            $returnTask['top_rank'] = '全网排名';
                            $returnTask['top_rank_value'] = $keywordsInfoArr[$key]['top_rank'];
                            $returnTask['title'] = '标题';
                            $returnTask['title_value'] = $keywordsInfoArr[$key]['title'];
                            array_push($returnRankArr,$returnTask);
                        }
                }else{
                    for($i=1; $i<20; $i++){
                        $returnTask['rank'] = '排名';
                        $returnTask['rank_value'] = $keywordsInfoArr[$i]['rank'];
                        $returnTask['search'] = '搜索引擎';
                        $returnTask['search_value'] = '百度';
                        $returnTask['site_weight'] = '权重';
                        $returnTask['site_weight_value'] = $keywordsInfoArr[$i]['site_weight'];
                        $returnTask['top_rank'] = '全网排名';
                        $returnTask['top_rank_value'] = $keywordsInfoArr[$i]['top_rank'];
                        $returnTask['title'] = '标题';
                        $returnTask['title_value'] = $keywordsInfoArr[$i]['title'];
                        array_push($returnRankArr,$returnTask);
                    }
                }

                //返回查询值
                $ajax['status'] = 1;
                $ajax['msg'] = $returnRankArr;
                return json_encode($ajax);
            }
        }
    }


     /*
      * 外部访问接口
      *     百度-PC-排名查询C
      *     获取PC排名信息
      */
    public function getRankInfoInterface($keyword,$domainUrl,$lastId){



        //查询taskId
        $getTaskIdArr = $this -> getTopTaskid($keyword,$domainUrl);
        $getTaskIdArr = json_decode($getTaskIdArr,true);


        if($getTaskIdArr['status'] == 1) {
            sleep(20);
            //查询taskInfo

            $getTaskInfo = $this->getTopTaskData($getTaskIdArr['msg']);
            $getTaskInfoDecode = json_decode($getTaskInfo,true);

            if($getTaskInfoDecode['status'] == 1){

                $webRes = $this -> createMorerank($getTaskInfoDecode,$lastId);

                $webResDecode = json_decode($webRes,true);


                if($webResDecode['code'] == 1){
                    $ajax['status'] = 1;
                    $ajax['msg'] = $webResDecode['data'];
                    echo json_encode($ajax);
                }else{
                    $ajax['status'] = 0;
                    $ajax['msg'] = $webResDecode['data'];
                    echo json_encode($ajax);
                }

            }else{
                $ajax['status'] = $getTaskInfo['status'];
                $ajax['msg'] = $getTaskInfo['msg'];
                echo json_encode($ajax);
            }

        }


    }

    /**
     *  5118
     *  百度-PC-排名查询D
     *  采集插入数据库
     *  需要判断，如果url已存在则不插入
     */
    protected function createMorerank($taskData,$rid){
        //数据抓取中

        if($taskData['status'] != 1){
            $createAjax = array('code' => 3,  'data' => '数据源抓取不成功');
            return json_encode($createAjax);
        }

        $keyword_monitor = $taskData['msg']['data']['keywordmonitor'];

        //插入数据
        $keyword = $keyword_monitor[0]['keyword'];
        $insertTask['search_engine'] = $keyword_monitor[0]['search_engine'];
        $insertTask['ip'] = $keyword_monitor[0]['ip'];

        $ranks = $keyword_monitor[0]['ranks'];

        if(sizeof($ranks) > 0){
            //返回插入数组
            $returnKeywordsArr = array();
            //只装入50个，计数变量
            $rCount = 0;
            foreach($ranks as $k => $v){
                //验证链接是否存在
                $res = Db::name('output_bdpc_morerank') -> where('page_url',$v['page_url']) -> find();
                if(!$res){
                    //插入数据库数组
                    $insertTask['title'] = $v['page_title'];
                    $insertTask['page_url'] = $v['page_url'];
                    $insertTask['rank'] = $v['rank'];
                    $insertTask['top_rank'] = $v['top100'];
                    $insertTask['site_weight'] = $v['site_weight'];
                    $insertTask['rid'] = $rid;
                    $insertTask['add_time'] = time();
                    $res = Db::name('output_bdpc_morerank') -> insert($insertTask);

                    //返回小程序数组
                    $returnTask['rank'] = '排名';
                    $returnTask['rank_value'] = $insertTask['rank'];
                    $returnTask['search'] = '搜索引擎';
                    $returnTask['search_value'] = '百度';
                    $returnTask['site_weight'] = '权重';
                    $returnTask['site_weight_value'] = $insertTask['site_weight'];
                    $returnTask['top_rank'] = '全网排名';
                    $returnTask['top_rank_value'] = $insertTask['top_rank'];
                    $returnTask['title'] = '标题';
                    $returnTask['title_value'] = $insertTask['title'];

                    $rCount++;
                    if($rCount <= 20){
                        array_push($returnKeywordsArr,$returnTask);
                    }
                }
            }
            $createAjax = array(
                'code' => 1,
                'data' => $returnKeywordsArr
            );
            return json_encode($createAjax);

        }else{
            $createAjax = array('code' => 2, 'data' => '数据库插入失败');
            return json_encode($createAjax);
        }

    }



    /**
     *  5118
     *  百度-PC-排名查询E
     *  步骤一：批量提交查询请求，并获得提取结果ID
     *
     *  keywords=电脑|CPU 多个用竖线隔开
     *  checkrow=50
     */
    protected function getTopTaskid($keywords,$domainUrl){
        header("Content-type: text/html; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

        // 1.构造包头，添加APIKEY
        $header = array(
            "Content-Type:application/x-www-form-urlencoded", //post请求
            'Authorization: APIKEY '.$GLOBALS['pcRankSelectKey']
        );

        //2.封装参数body
        /*
         * 关键词:keywords
         * 排名:checkrow
         */
        $parameter = 'keywords='.$keywords.'&checkrow='.$GLOBALS['pcRankNum'].'&url='.$domainUrl;


        $url = $GLOBALS['pcRankSelect'];
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

            return json_encode($ajaxReturn);
        }else{

            $ajaxReturn['status'] = $output->errcode;
            $ajaxReturn['msg'] = $output->errmsg;
            return json_encode($ajaxReturn);

        }

    }


    /**
     *  5118
     *  百度-PC-排名查询F
     *  步骤二：根据提取结果ID查询数据是否采集完成，如果完成则得到Json格式结果
     */
    protected function getTopTaskData($taskid){
        header("Content-type: text/html; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

        // 1.构造包头，添加APIKEY
        $header = array(
            "Content-Type:application/x-www-form-urlencoded", //post请求
            'Authorization: APIKEY '.$GLOBALS['pcRankSelectKey']
        );

        //2.封装参数body
        /*
         * 关键词:keywords
         * 排名:checkrow
         */
        $parameter = 'taskid='.$taskid;


        $url = $GLOBALS['pcRankSelect'];
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

            return json_encode($ajaxReturn);

        }else{

            $ajaxReturn['status'] = $output->errcode;
            $ajaxReturn['msg'] = $output->errmsg;
            return json_encode($ajaxReturn);

        }


    }






    /*
     *  公众号文章
     *     自动爬取公众号文章A
     */
    public function _autoSpiderWxArticle(){
        header("Content-type: text/html; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

        //开始请求微信服务器获取openId
        $url = $GLOBALS['publicArticleUrl'];
        $ch = curl_init();


        // 1. 设置选项
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_URL, $url);  // 设置要抓取的页面地址
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);              // 抓取结果直接返回（如果为0，则直接输出内容到页面）
        curl_setopt($ch, CURLOPT_HEADER, 0);                      // 不需要页面的HTTP头
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);


        // 2. 执行并获取HTML文档内容，可用echo输出内容
        $output = curl_exec($ch);

        curl_close($ch);

        //3.获取文章列表内容
        preg_match_all('/<div class="list_middle_sider">(.*?)<span style="display:none;">/is',$output,$articleList);

        //4.分别获取item
        preg_match_all('/<.*?>(.*?)<\/.*?>/is',$articleList[1][0],$articleItem);

        //5.得到需要过滤的数组
        $explodeArr = array_chunk($articleItem[1],3);
        unset($explodeArr[0]);
        $explodeArticle = array_reverse($explodeArr);
        unset($explodeArticle[0]);
        unset($explodeArticle[1]);
        $explodeArticle = array_reverse($explodeArticle);

        //6.开始获取值
        foreach($explodeArticle as $k => $v){
            //获取$v[0] 封面，标题，文章详情页
            //封面
            preg_match_all("/<img[^<>]+src *\= *[\"']?(http\:\/\/[^ '\"]+)/i", $v[0], $src_links, PREG_SET_ORDER);
            $cover_img = $src_links[0][1];
            //标题
            preg_match_all("/<img[^<>]+alt *\= *[\"']?([^ '\"]+)/i", $v[0], $alt, PREG_SET_ORDER);
            $articleTitle = $alt[0][1];
            //详情页面
            preg_match_all("/<a[^<>]+href *\= *[\"']?([^ '\"]+)/i", $v[0], $href, PREG_SET_ORDER);
            $articleDetails = $GLOBALS['publicArticleRoot'].$href[0][1];
            //文章发布时间
            $articleTime = strtotime($v[1]);

            $insertArticleArr = array(
                'cover_img' => $cover_img,
                'title' => $articleTitle,
                'details_page' => $articleDetails,
                'add_time' => $articleTime
            );

            $whereArticle = array(
                'details_page' => $insertArticleArr['details_page'],
                'is_delete' => 0
            );
            $isDbArticle = Db::name('public_article') -> where($whereArticle) -> count();
            //没有插入过这篇文章
            if($isDbArticle <= 0){
                $res = Db::name('public_article') -> insert($insertArticleArr);
                echo $res;
            }

        }

    }


    /*
     *  公众号文章
     *     返回小程序文章接口B
     */
    public function _apiGetPublicArticle(){
        header("Content-type: text/html; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

        // 是否为 POST 请求
        if (Request::instance()->isPOST()) {


            //头条查询条件
            $headerLineWheres = array(
                'is_delete' => 0,
                'level' => 1
            );
            //头条查询
            $articleData = Db::name('public_article') -> where($headerLineWheres) -> order('id asc') -> select();
            //装入返回数组
            $returnHeadlinesArticle = array();
            foreach($articleData as $k => $v){
                $returnHeadlinesArticle[$k]['id'] = $v['id'];
                $returnHeadlinesArticle[$k]['name'] = $v['title'];
                $returnHeadlinesArticle[$k]['image'] = $v['cover_img'];
                $returnHeadlinesArticle[$k]['style'] = 2;
            }

            //文章列表条件
            $articleWheres = array(
                'is_delete' => 0,
                'level' => 2
            );
            //文章列表
            $articleData = Db::name('public_article') -> where($articleWheres) -> order('id asc')  -> select();
            //装入返回数组
            $returnCommonArticle = array();
            foreach($articleData as $k => $v){

                    $returnCommonArticle[$k]['id'] = $v['id'];
                    $returnCommonArticle[$k]['title'] = $v['title'];
                    $returnCommonArticle[$k]['icons'][0] = $v['cover_img'];
                    $returnCommonArticle[$k]['date'] = date("Y-m-d H:i:s",$v['add_time']);
                    $returnCommonArticle[$k]['details_page'] = $v['details_page'];
                    $returnCommonArticle[$k]['like_number'] = $v['like_number'];
                    $returnCommonArticle[$k]['style'] = 2;
                    $returnCommonArticle[$k]['tag'] = '玉米社新闻客户端';
                }



            if($returnCommonArticle){
                $ajax['status'] = 1;
                $ajax['msg'] = '获取成功';
                $ajax['commonArticle'] = $returnCommonArticle;
                $ajax['headlinesArticle'] = $returnHeadlinesArticle;
                return json_encode($ajax);
            }else{
                $ajax['status'] = 0;
                $ajax['msg'] = '数据异常，请联系管理员';
                return json_encode($ajax);
            }

        }

    }


    /*
     *  公众号文章
     *     返回小程序详情页面C
     */
    public function _apiGetArticleDetails(){
        header("Content-type: text/html; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        // 是否为 GET 请求
        if (Request::instance()->isGET()) {
            $idPost = $_GET['id'];
//            $idPost = 29;
            $id = strFilter($idPost);

            $details_page_array = Db::name('public_article') -> where('id',$id) -> field('details_page') -> find();
            $details_page = $details_page_array['details_page'];



            //开始请求微信服务器获取openId
            $url = $details_page;
            $ch = curl_init();



            // 1. 设置选项
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_URL, $url);  // 设置要抓取的页面地址
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);              // 抓取结果直接返回（如果为0，则直接输出内容到页面）
            curl_setopt($ch, CURLOPT_HEADER, 0);                      // 不需要页面的HTTP头
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_POST, 1);


            // 2. 执行并获取HTML文档内容，可用echo输出内容
            $output = curl_exec($ch);

            curl_close($ch);


            //3.获取文章详情内容
        preg_match_all("/<div id='list_container'>(.*?)<div class=\"ct_mpda_wrp\" id=\"js_sponsor_ad_area\" style=\"display:none;\">/is",$output,$articleDetails);


            //4.1过滤特殊标签
        $pageDetail=preg_replace("/<(\/?embed .*?)>/si","",$articleDetails[1][0]); //过滤html标签
            //4.2替换所有14px字体，为16px
        $pageDetail=preg_replace("/font-size: 14px/si","font-size: 26px",$pageDetail); //过滤html标签
        $pageDetail=preg_replace("/font-size: 16px/si","font-size: 38px",$pageDetail); //过滤html标签

        $pages = "<link rel='stylesheet' href='/static/spiderWx/css/main.css'>".$pageDetail;

            //5.
            print_r($pages);


        }
    }





}