## NOTE

**基于NodeJS的D2Server已经发布，请关注[node-d2server](https://github.com/Bacra/node-d2server)这个项目**

由于[环境配置](./common.inc.php)较为复杂，需要分布配置Apache、Chrome、Fiddler2，运行的时候，需要同时启动Apache和Node App， **项目已经停止更新**

爱折腾的朋友可以继续[Fork](https://github.com/Bacra/php-d2server/fork)，至今依然喜欢这个模版引擎的（完全面向前端）



PHP-D2Server
============

这是一个使用PHP结合Apache编写的前端开发环境。

PS：这是用PHP编写的D2Server版本，你可能在寻找[node版本](https://github.com/Bacra/node-d2server)




## Get Started

1. 将[Server配置](./Apache/web.conf)导入到Apache
2. 安装NodeJS，并在[NodeJS文件夹](./NodeJS)下，运行`npm i`
3. 配置Java环境
4. 配置Server变量，见[common.inc.php](./common.inc.php)
5. 下载`gcc`和`yuicompressor`，并将路径按照[配置文件](./common.inc.php)中的`JSC` `CSSC`变量写入到系统环境中
6. 在Chrome中导入[AutoF5插件](./Chrome Extension/AutoF5)
7. 按照Server的配置，在项目中创建`.source\.buildconfg`项目配置文件 [example](./.buildconfig.example)
8. 在系统host中添加`127.0.0.1	www.test.com`
9. 进入[build文件夹](./build)文件夹，运行`安装.bat`
10. 安装全局的基于NodeJS的LESSC
11. 运行Apache和NodeJS文件夹下的`server.js`




## Features

* 基于配置文件的项目管理模式
* 开发文件使用独立文件夹保存，实现模块化的文件保存，由程序进行合并、导出（带压缩）、同步
* 使用[类PHPTAL的模版引擎](./template.class.php)，使用PHP原生DOM类实现了`loop` `default` `define` `block` `text` `html` `label` `init-omit` `use-omit`等方法
* 实现基于websocket的浏览器动态刷新（仅限Chrome）




## Template Engine

* `loop` 循环tag包含的内容
* `default` 设置变量的默认值
* `define` 定义变量（作用域限于标签内）
* `text` 替换tag中的内容（HTML标签不转义）
* `html` 替换tag中的内容（HTML标签转义）
* `label` 无意义的标签，在模版解析完成后，会删除
* `block` 基于项目配置文件中HTML的block参数，简化的`if`命令
* `init-omit` 定义`omit`，便于HTML代码的复用
* `use-omit` 使用已经定义的`omit`

同时，可以使用几个基于`Smarty`语法的命令 `foreach` `if` `else` `elseif` `$var`





## Warn

* 原生PHP的DOM类不支持gbk编码，同时文件必须声明HTML版本，否则会出现解析错误
* 尽量少使用中文路径，虽然已经对一些路径进行了转码，但依旧难保window下出现路径错误
* 启动后会占用80、3316、8080端口，分别是Apache Server、Apache Proxy Server、NodeJS
* 程序没有提供导出HTML的方法，工作交接需要先和技术协商好




## License

PHP-D2Server is available under the terms of the [MIT License](./LICENSE.md).