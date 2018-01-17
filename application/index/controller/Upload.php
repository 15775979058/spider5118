<?php
namespace app\index\controller;

class Upload extends \think\Controller
{
    public function index(){
        echo '上传';

        return $this->fetch('index');
    }

    /*
     * 上传方法
     */
    public function uploadMethod(){
        header('Content-Type:text/html;Charset=utf-8');
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('txt');
        $filePath = '';

        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                // 成功上传后 获取上传信息
                // 输出 jpg
//                echo $info->getExtension();
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                $filePath = $info -> getSaveName();
//                echo $info->getSaveName();
                // 输出 42a79759f284b767dfcb2a0197904287.jpg
//                echo $info->getFilename();

//                $this -> success('上传成功');
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }

        //进行读行
        $directDir = './uploads/'.$filePath;



        $file = fopen($directDir, "r");
        $user=array();
        $i=0;
//输出文本中所有的行，直到文件结束为止。
        while(! feof($file))
        {
            $user[$i]= fgets($file);//fgets()函数从文件指针中读取一行
            $i++;

        }


        //装url数组
        $urlArr=array_filter($user);


        $newUrlArr = array();

        foreach($urlArr as $k => $v){
            $templets = 'javascript:void(0);document.write("<a href=\'http://'.strFilter($v).'\' target=\'_blank\'>点击一下</a>");';
            array_push($newUrlArr,$templets);

        }


        $newFileName = './creates/'.time().'.txt';
        $newUrlString = implode("\r\n", $newUrlArr);

        file_put_contents($newFileName, $newUrlString);

        $this -> success('./creates/'.time().'生成成功');


        fclose($file);
    }


    /*
     * 读取指定内容
     */
    function getLine($file, $line, $length = 1053){
        $returnTxt = null; // 初始化返回
        $i = 1; // 行数

        $handle = @fopen($file, "r");
        var_dump($handle);
        exit();
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle, $length);
                if($line == $i) $returnTxt = $buffer;
                $i++;
            }
            fclose($handle);
        }
        return $returnTxt;
    }
}