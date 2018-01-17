<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:79:"C:\phpstudy\WWW\spider5118\public/../application/index\view\index\business.html";i:1509518156;s:76:"C:\phpstudy\WWW\spider5118\public/../application/index\view\public\base.html";i:1510211368;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="description" content="">
    <meta name="HandheldFriendly" content="True">
    <meta name="MobileOptimized" content="320">
    <meta name="viewport" content="initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>玉米社大数据中心</title>
    <link href="/header.ico" type="image/x-icon" rel="shortcut icon">

    <link rel="alternate" type="application/rss+xml" title="egrappler.com" href="feed/index.html">
    <link href="http://fonts.googleapis.com/css?family=Raleway:700,300" rel="stylesheet"
          type="text/css">
    <link rel="stylesheet" href="/static/index/css/style.css">
    <link rel="stylesheet" href="/static/index/css/prettify.css">
    <!-- 引入样式 -->
    <link rel="stylesheet" href="https://unpkg.com/element-ui/lib/theme-chalk/index.css">

    <script src="/static/index/js/jquery.min.js"></script>
    <script src="/static/index/js/layer/layer.js"></script>
</head>
<body >
<nav>
    <div class="container">
        <h1><a href="/index.php">YMS</a></h1>
        <div id="menu">
            <!--<ul class="toplinks">-->
            <!--<li><a href="#">Opineo Website </a></li>-->
            <!--<li><a href="http://www.egrappler.com/">eGrappler</a></li>-->
            <!--<li><a href="../doc-template/docs.html">Blue Theme</a></li>-->
            <!--<li><a href="../doc-template-green/docs.html">Green Theme</a></li>-->
            <!--</ul>-->
        </div>
        <a id="menu-toggle" href="#" class=" ">&#9776;</a> </div>
</nav>
<header>
    <div class="container">
        <h2 class="docs-header"> 玉米社只为大数据而生</h2>
    </div>
</header>
<section>
    <div class="container">
        <ul class="docs-nav" id="menu-left">
            <li><strong>百度-PC-前50网站信息</strong></li>
            <li><a href="/index.php/index/index/business" class=" ">行业管理</a></li>
            <li><a href="/index.php/index/index/keywords" class=" ">关键词管理</a></li>
            <li><a href="/index.php/index/index/website" class=" ">网站管理</a></li>
            <li class="separator"></li>
            <!--<li><strong>Customizing Opineo</strong></li>-->
            <!--<li><a href="#view_type" class=" ">View Type</a></li>-->
            <!--<li><a href="#animation_style" class=" ">Animation Styles</a></li>-->
            <!--<li><a href="#bars_text" class=" ">Bars Text</a></li>-->
            <!--<li><a href="#vote_counter" class=" ">Vote Counter</a></li>-->
            <!--<li><a href="#rating_icons" class=" ">Rating Icons</a></li>-->
            <!--<li><a href="#rating_titles" class=" ">Rating Titles</a></li>-->
            <!--<li><a href="#bar_colors" class=" ">Bar Colors</a></li>-->
        </ul>
        <div class="docs-content">
            
<style>
    .formDiv{
        height: 100%;
    }
    .blank{
        margin-bottom: 10%;
    }
    .el-pager li{
        font-size: 19px;
    }

</style>


<div class="formDiv">
    <form action="" method="post">
        <div>行业名称：</div>
        <input type="text" class="el-input__inner blank" name="businessName" placeholder="请输入行业名称" />
        <p/>
        <input id="submitBusindess" style="width: 30%;margin-left: 20%" class="el-button el-button--primary" value="提交" />
    </form>

    <div style="margin-top: 5%;">行业列表：</div>

    <table class="el-table__body" style="width: 820px;border: 5px;">
        <thead>
            <tr class="el-table_row">
                <th>ID</th>
                <th>行业名称</th>
                <th>添加时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if(sizeof($busData) == 0){ ?>
                <tr>
                    <td colspan="4" style="text-align: center;font-weight: bold">
                        暂时没有数据
                    </td>
                </tr>
            <?php }else{ foreach($busData as $k => $v){ if($k%2 == 0){ ?>
                        <tr class="el-table__row" style="background-color: #FDF5E6">
                            <td class="el-table_1_column_10">
                                <?php echo $v['id']; ?>
                            </td>
                            <td class="el-table_1_column_10">
                                <?php echo $v['business_name']; ?>
                            </td>
                            <td class="el-table_1_column_10">
                                <?php echo date('Y-m-d',$v['add_time']); ?>
                            </td>
                            <td class="el-table_1_column_10">
                                <button onclick="delBusiness(<?php echo $v['id']; ?>)" type="button" class="el-button el-button--primary" value="删除">
                                    <i class="el-icon-delete">
                                    </i>
                                </button>
                            </td>
                        </tr>
                    <?php }else{ ?>
                        <tr class="el-table__row el-table__row">
                            <td class="el-table_1_column_10">
                                <?php echo $v['id']; ?>
                            </td>
                            <td class="el-table_1_column_10">
                                <?php echo $v['business_name']; ?>
                            </td>
                            <td class="el-table_1_column_10">
                                <?php echo date('Y-m-d',$v['add_time']); ?>
                            </td>
                            <td class="el-table_1_column_10">
                                <button onclick="delBusiness(<?php echo $v['id']; ?>)" type="button" class="el-button el-button--primary" value="删除">
                                    <i class="el-icon-delete">
                                    </i>
                                </button>
                            </td>
                        </tr>
                    <?php } } } ?>
        </tbody>
    </table>

</div>
<div class="el-pager" style="margin-top: 10%;">
    <?php echo $page; ?>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $('#submitBusindess').click(function(){
            var businessName = $('input[name="businessName"]').val();

            $.ajax({
                type: "POST",
                url: "<?php echo url('Index/business'); ?>",
                data: {businessName: businessName},
                async: false,
                dataType: "json",
                success: function(res){

                    if(res.status == 1){
                        alert(res.msg);
                        location.reload();
                        return;
                    }else{
                        alert(res.msg);
                        return;
                    }
                }
            });

        });
    });

    //js中用正则表达式 过滤特殊字符, 校验所有输入域是否含有特殊符号
    function stripscript(s) {
        var pattern = new RegExp("[`~!@#$^&*()=|{}':;',\\[\\].<>/?~！@#￥……&*（）&mdash;—|{}【】‘；：”“'。，、？]");
        var rs = "";
        for (var i = 0; i < s.length; i++) {
            rs = rs + s.substr(i, 1).replace(pattern, '');
        }
        return rs;
    }

    //删除行业
    function delBusiness(id){
        $.ajax({
            type: "POST",
            url: "<?php echo url('Index/_delBusiness'); ?>",
            data: {busId: id},
            async: false,
            dataType: "json",
            success: function(res){

                if(res.status == 1){
                    alert(res.msg);
                    location.reload();
                    return;
                }else{
                    alert(res.msg);
                    return;
                }
            }
        });
    }
</script>


        </div>
    </div>
</section>
<section class="vibrant centered">
    <div class="container">
        <h4> This documentation template is provided free by eGrappler.com. Opineo is a feedback
            collection widget and is available for free download <a href="#"> here</a></h4>
    </div>
</section>
<footer>
    <div class="container">
        <p> &copy; 2017 YMS | More Data <a href="http://www.yumishe88.com/" target="_blank" title="玉米社官网">来自</a> - Collect from <a href="http://www.yumishe88.com.com/" title="玉米社" target="_blank">玉米社</a> </p>
    </div>
</footer>


<script type="text/javascript" src="/static/index/js/prettify/prettify.js"></script>
<script src="/static/index/js/layout.js"></script>
<script src="/static/index/js/jquery.localscroll-1.2.7.js" type="text/javascript"></script>
<script src="/static/index/js/jquery.scrollTo-1.4.3.1.js" type="text/javascript"></script>
</body>
</html>
