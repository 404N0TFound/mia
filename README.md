环境搭建步骤：
1. 安装composer，Linux安装命令如下：
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
	Windows安装请参考http://docs.phpcomposer.com/00-intro.html#Installation-Windows
        
2. 更新composer依赖，命令如下
	composer update

3. 增加nginx配置，注意根目录为 src/webroot 