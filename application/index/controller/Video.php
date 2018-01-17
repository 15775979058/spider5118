<?php
namespace app\index\controller;

use think\Db;
use think\Request;
class Video extends \think\Controller
{
    /*
     * 小程序首页视频
     */
    public function video_ms_index(){
        return $this -> fetch('video1');
    }

}