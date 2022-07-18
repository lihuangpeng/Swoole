<?php
/*
 * author:lihuangpeng
 * date:2021-02-04
 * 网站访问入口文件
 * */
namespace library;
if (version_compare(PHP_VERSION, '5.6.0', '<')) die('PHP_VERSION > 5.6.0');
define('PROJECT_NAME', 'MVC_PHP');
define('IS_CLI', PHP_SAPI === 'cli');
require_once "../core/base.php";
Container::get('core')->run();
