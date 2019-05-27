<?php

/**
 * thrift service基类
 *
 * Created by PhpStorm.
 * User: Darkgel
 * Date: 2018/10/9
 * Time: 10:10
 */
namespace Darkgel\Thrift;
use Thrift\Exception\TTransportException;
use Thrift\Exception\TException;


trait ThriftServiceTrait
{
    /**@var string 对应thrift配置文件中相应的service名称*/
    public $service = '';

    /**@var bool 是否使用了多路协议，是则为true，否则为false*/
    protected $multiplexed = false;

    /**@var Object|null 相应的thrift client*/
    protected $thriftClient = null;

    /**@var int|null 错误码*/
    protected $errCode = null;
    /**@var string|null 错误信息*/
    protected $errMsg = '';
    /**@var array|null thrift调用后返回的数据 */
    protected $data = null;

    //使用单例模式，禁止克隆
    protected final function __clone() {}

    protected function __construct() {
        if(empty($this->service)){
            throw new ThriftException('service is invalid');
        }

        try{
            $this->thriftClient = app('thriftManager')->getService($this->service, $this->multiplexed);
        } catch (\Exception $e){
            $this->errCode = $e->getCode();
            $this->errMsg = $e->getMessage();
            $this->thriftClient = null;
            throw new ThriftException('can not get thrift client : '.$this->service);
        }

    }

    /**
     * 返回service 单实例
     * @return ThriftServiceTrait
     */
    public final static function getInstance() {
        if(static::$instance === null){
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function hasError(){
        return is_null($this->errCode);
    }

    public function getData(){
        return $this->data;
    }

    public function getError(){
        return [
            'errCode' => $this->errCode,
            'errMsg' => $this->errMsg,
        ];
    }

    /**
     * 重置errCode,errMsg,data
     */
    protected function reset(){
        $this->errCode = null;
        $this->errMsg = '';
        $this->data = null;
    }

    /**
     * 调用接口
     * @param string|null $method 方法名
     * @param array|null $args 参数
     * @return bool 调用成功返回true,否则抛出异常
     * @throws ThriftException
     */
    protected function invoke($method = null, $args = null){
        if($this->thriftClient === null){
            $this->errCode = -10006;//thrift调用失败
            $this->errMsg = "thriftClient is null";
            throw new ThriftException("thriftClient is null : ".$this->service.'['.$this->errCode.':'.$this->errMsg.']');
        }

        $this->reset();
        if(is_null($method)){
            $args = func_get_args();
            $method = array_shift($args);
        }

        try{
            $data = call_user_func_array([$this->thriftClient,$method],$args);
        }catch(TTransportException $e){
            $this->errCode = $e->getCode();
            $this->errMsg = $e->getMessage();
            throw new ThriftException($this->service.'/'.$method.':'.'['.json_encode($args,JSON_UNESCAPED_UNICODE).']['.$this->errCode.':'.$this->errMsg.']');
        }catch(TException $e){
            $this->errCode = $e->getCode();
            $this->errMsg = $e->getMessage();
            throw new ThriftException($this->service.'/'.$method.':'.'['.json_encode($args,JSON_UNESCAPED_UNICODE).']['.$this->errCode.':'.$this->errMsg.']');
        }catch(\Exception $e){
            $this->errCode = $e->getCode();
            $this->errMsg = $e->getMessage();
            throw new ThriftException($this->service.'/'.$method.':'.'['.json_encode($args,JSON_UNESCAPED_UNICODE).']['.$this->errCode.':'.$this->errMsg.']');
        }
        $this->data = self::objectToArray($data);
        return true;
    }

    /**
     * 将对象转化成数组，会递归调用
     * @param Object $object
     * @return mixed
     */
    public static function objectToArray($object){
        if(!is_object($object) && !is_array($object)){
            return $object;
        }
        $data = array();
        foreach($object as $key=>$value){
            $data[$key] = self::objectToArray($value);
        }
        return $data;
    }

    /**
     * 以静态调用的方式调用thrift service的方法时，会使用到该方法
     * @param string $name 方法名
     * @param array $arguments 参数
     * @return array|false 调用的返回值
     * @throws ThriftException
     */
    public static function __callStatic($name, $arguments)
    {
        if(!static::getInstance()->invoke($name, $arguments)){
            return false;
        }
        return static::getInstance()->getData();
    }
}