ubuntu服务器部署流程

1. 安装 PHP7, Mysql, apache2
   1. `apt install -y php7.2`
   2. `apt install apache2 libapache2-mod-php7.2`
   3. `apt install mysql-server php7.2-mysql`

2. 配置mysql
   1. mysql安装引导
      a. `mysql_secure_installation`
   2. 创建数据库
      a. `mysql`
      b. `mysql> CREATE DATABASE fast;`
   3. 创建用户
      a. `mysql> CREATE USER 'usr1'@'localhost' IDENTIFIED BY '123Abc**';`
      b. `mysql> GRANT all priviledges on *.* to 'usr1'@'localhost';`
   4. 退出
      a. `mysql> exit`

3. 开启端口80的防火墙
    1. ufw用户：`ufw allow 80/tcp`
    2. iptables用户：`iptables -A INPUT -m state --state NEW -p tcp --dport 80 -j ACCEPT`

4. 安装php的curl标准库
    1. `apt-get install -y php-curl`

5. 重启服务
    1. `systemctl restart apache2.service`
    2. `systemctl restart mysql.service`

6. 打开`src/`, 将`config-sample.php`的内容复制，新建文件`config.php`，将复制的内容粘贴进去并保存。
   1. 所有的配置都在`config.php`
   2. `config.php`已经被添加进`.gitignore`
   
7. 设置`config.php`
   1. `BASE_URL`为存放index.php的目录，例如http://localhost/folding-at-SUSTech-server/src
   2. `DB_HOST`：填写`localhost`
   3. `DB_NAME`：数据库名，如果参照了上面配置mysql的方法，填写`fast`
   4. `DB_USERNAME`：用户名，在上面配置musql的方法中为`usr1`
   5. `DB_PASSWORD`：密码，在上面配置musql的方法中为`123Abc**`
   
8. 数据库迁移
   1. 访问<BASE_URL>/index.php/api/update/fastdb
   2. 显示Migration Success则表示数据库成功迁移

