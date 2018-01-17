<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:77:"C:\phpstudy\WWW\spider5118\public/../application/index\view\upload\index.html";i:1509175320;}*/ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>上传文件</title>
</head>
<body>
    <form action="<?php echo url('Upload/uploadMethod'); ?>" enctype="multipart/form-data" method="post">
        <input type="file" name="txt" /> <br>
        <input type="submit" value="上传" />
    </form>
</body>
</html>