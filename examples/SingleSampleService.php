<?php
/**
 * Created by PhpStorm.
 * User: Darkgel
 * Date: 2019/5/31
 * Time: 16:39
 */

class SingleSampleService extends ThriftService
{
    //子类必须声明该属性,以指向当前类的单实例
    protected static $instance = null;

    public $service = 'SingleSampleService';//这里对应thrift.php中的配置
}