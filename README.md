部署流程

1. 安装 PHP7

2. 打开`src/`, 将`config-sample.php`的内容复制，新建文件`config.php`，将复制的内容粘贴进去并保存。
   1. 所有的配置都在`config.php`
   2. `config.php`已经被添加进`.gitignore`
3. 设置`config.php`
   1. `BASE_URL`为存放index.php的目录
   2. `LANGUAGE`为默认语言，其他无需设置