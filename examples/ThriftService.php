<?php
/**
 * Created by PhpStorm.
 * User: Darkgel
 * Date: 2019/5/31
 * Time: 16:41
 */

use Darkgel\Thrift\ThriftServiceTrait;

class ThriftService
{
    use ThriftServiceTrait;

    /**
     * 以静态调用的方式调用thrift service的方法时，会使用到该方法
     * 此处重写Trait中方法，加入自己的日志处理等功能
     * @param string $name 方法名
     * @param array $arguments 参数
     *
     * @return array 调用的返回值
     */
    public static function __callStatic($name, $arguments)
    {
        try{
            static::getInstance()->invoke($name, $arguments);
            $result = static::getInstance()->getData();

            // 记录日志

            return $result;

        } catch (\Exception $e) {
            // 记录日志

            // 抛出自定义异常
        }
    }
}