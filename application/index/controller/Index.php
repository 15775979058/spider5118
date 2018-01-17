<?php
namespace app\index\controller;

use think\Request;
use think\Db;
class Index extends \think\Controller
{
    /**
     * 主页
     */
    public function index()
    {

        return $this -> fetch('index');
    }

    /**
     * 行业管理页面
     */
    public function business(){

        // 是否为 POST 请求
        if (Request::instance()->isPost()){
            $postArr = $_POST;
            $businessName = strFilter($postArr['businessName']);

            $insertData = array(
                'business_name' => $businessName,
                'add_time' => time()
            );
            $res = Db::name('business') -> insert($insertData);
            if($res){
                $ajax['status'] = 1;
                $ajax['msg'] = '添加行业成功';
                return ($ajax);
                exit();
            }else{
                $ajax['status'] = 0;
                $ajax['msg'] = '添加行业失败';
                return ($ajax);
                exit();
            }
        }
        //普通请求
        $busData = Db::name('business') -> where('is_delete',0) ->order('id desc')->paginate(5);
        $page = $busData -> render();

        $this -> assign('busData',$busData);
        $this -> assign('page',$page);
        return $this -> fetch('business');
    }

    /*
     *  方法-删除行业
     */
    public function _delBusiness(){
        // 是否为 POST 请求
        if (Request::instance()->isPost()){
            if(isset($_POST['busId'])){
                $postArr = $_POST;
                $busId = strFilter($postArr['busId']);

                $busArr = array(
                    'is_delete' => 1
                );
                $res = Db::name('business') -> where('id',$busId) -> update($busArr);
                if($res){
                    $ajax['status'] = 1;
                    $ajax['msg'] = '删除成功';
                    return $ajax;
                }else{
                    $ajax['status'] = 0;
                    $ajax['msg'] = '删除失败';
                    return $ajax;
                }
            }
        }
    }

    /*
     * 方法-关键词名称
     */


    /**
     * 上传页面
     */
    public function upload(){
        return $this -> fetch('upload');
    }


    /**
     * 关键词页面
     */
    public function keywords(){

        // 是否为 POST 请求
        if (Request::instance()->isPost()){

            if($_POST['businessType'] != 0 && $_POST['businessName']!= '' ){
                $postArr = $_POST;
                $insertData = array(
                    'bid' => strFilter($postArr['businessType']),
                    'keyword' => strFilter($postArr['businessName']),
                    'add_time' => time()
                );

                $res = Db::name('keywords') -> insert($insertData);
                if($res){
                    $this -> success('关键词添加成功');
                }else{
                    $this -> error('关键词添加失败');
                }
            }else{
                $this -> error('参数不能为空');
            }
        }
        //查询行业名称
        $busTitle = Db::name('business') -> where('is_delete',0) ->field('id,business_name') -> select();

        //关键词列表
        $keywordsData = Db::name('keywords') -> where('is_delete',0) -> field('id,keyword,bid,add_time,select_state,upload_time') -> where('is_delete',0) -> paginate(10);
        $page = $keywordsData -> render();

        $this -> assign('keywordsData',$keywordsData);
        $this -> assign('page',$page);
        $this -> assign('busTitle',$busTitle);
        return $this -> fetch('keywords');
    }


    /**
     * 网站管理页面
     */
    public function website(){

        //查询website表的数据
        $websiteData = Db::name('website') -> where('is_delete',0) ->order('id desc') -> paginate(100);
        $page = $websiteData -> render();

        $this -> assign('websiteData',$websiteData);
        $this -> assign('page',$page);
        return $this -> fetch('website');
    }


}
