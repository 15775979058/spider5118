<?php
namespace app\index\controller;

/**
 *  对接小程序用户接口数据
 */
use think\Db;
use think\Request;
use think\Config;
class Wxuser extends \think\Controller
{


    /*
    * 外部访问接口
    *     开发服务器
    *     存储关注小程序用户信息
    *
    *     参数:
    *         avatarUrl:头像
    *        province:省份
    *         city:城市
    *        country:国家
    *        gender:性别
    *        nickName：用户名
    *
    */
    public function _apiGetWxUser(){
        // 是否为 GET 请求
        if (Request::instance()->isGet()) {
            $postArr = $_GET;
            //获取oppenId所需要的
            $js_code = strFilter($postArr['js_code']);
            if($js_code){
                $oppenArr = $this -> getWxOppenid($js_code);
                $oppenArr = json_decode($oppenArr,true);
                $oppenId = $oppenArr['oppenId'];
                $errcode = $oppenArr['errcode'];
            }else{
                $ajax['status'] = 4;
                $ajax['msg'] = 'js_code为空';
                $ajax['oppenId'] = 0;
                $ajax['errcode'] = 0;
                return json_encode($ajax);
            }

            //确定keyword值
            $province = strFilter($postArr['province']);
            $city = strFilter($postArr['city']);
            $country = strFilter($postArr['country']);
            $gender = strFilter($postArr['gender']);
            $nickName = strFilter($postArr['nickName']);
            $avatarUrl = $postArr['avatarUrl'];

            if($province == '' || $city == '' || $country == '' || $gender == '' || $nickName == '' || $avatarUrl == ''){
                $ajax['status'] = 0;
                $ajax['msg'] = '参数不能为空';
                $ajax['oppenId'] = $oppenId;
                $ajax['errcode'] = $errcode;
                return json_encode($ajax);
            }else{
                //判断是否老用户

                $isSaveDb = Db::name('wxuser') -> where('nickName',$nickName) -> find();
                if(!$isSaveDb){

                    $insertWxuser = array(
                        'nickname' => $nickName,
                        'gender' => $gender,
                        'avatarUrl' => $avatarUrl,
                        'province' => $province,
                        'city' => $city,
                        'country' => $country,
                        'add_time' => time(),
                        'openId' => $oppenId,
                        'errcode' => $errcode
                    );

                    $dbRes = Db::name('wxuser') -> insert($insertWxuser);
                    if($dbRes){
                        $ajax['status'] = 1;
                        $ajax['msg'] = '注册成功';
                        $ajax['oppenId'] = $oppenId;
                        $ajax['errcode'] = $errcode;
                        return json_encode($ajax);
                    }else{
                        $ajax['status'] = 2;
                        $ajax['msg'] = '注册失败';
                        $ajax['oppenId'] = $oppenId;
                        $ajax['errcode'] = $errcode;
                        return json_encode($ajax);
                    }

                }else{
                    $ajax['status'] = 3;
                    $ajax['msg'] = '已注册用户';
                    $ajax['oppenId'] = $oppenId;
                    $ajax['errcode'] = $errcode;
                    return json_encode($ajax);
                }
            }

        }
    }

    /**
    * 调用微信服务器获取oppenid
    *
    *     参数:
    *         js_code:code值
    *
    */
    public function getWxOppenid($js_code){
        header("Content-type: text/html; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

        $loginInfoArr = Config::get('WxLoginInfo');
        //AppId
        $AppID = $loginInfoArr['AppID'];
        //AppSecret
        $AppSecret = $loginInfoArr['AppSecret'];
        //URL
        $oppenIdUrlInit = $loginInfoArr['oppenIdUrl'];

        //进行替换
        $oppenIdUrlInitAppId = preg_replace("/\[APPID\]/", $AppID, $oppenIdUrlInit);
        $oppenIdUrlInitSecret = preg_replace("/\[SECRET\]/", $AppSecret, $oppenIdUrlInitAppId);
        $oppenIdUrl = preg_replace("/\[JSCODE\]/", $js_code, $oppenIdUrlInitSecret);


        //开始请求微信服务器获取openId
        $url = $oppenIdUrl;
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
        $output = json_decode(curl_exec($ch));

        curl_close($ch);


        //获取成功
        if(isset($output -> openid)){
            $openId = $output -> openid;
            $errcode = 0;
            $ajaxReturn = array(
                'oppenId' => $openId,
                'errcode' => $errcode
            );
        }else{
            $errcode = $output -> errcode;
            $openId = 0;
            $ajaxReturn = array(
                'oppenId' => $openId,
                'errcode' => $errcode
            );
        }


        return json_encode($ajaxReturn);

    }
}