<?php
/**
 * 管理thrift连接与配置
 *
 * Created by PhpStorm.
 * User: Darkgel
 * Date: 2018/10/9
 * Time: 10:44
 */

namespace Darkgel\Thrift;
use Thrift\Transport\TSocket;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TBufferedTransport;
use Thrift\Exception\TException;
use Thrift\Protocol\TMultiplexedProtocol;

class ThriftManager
{
    protected $sendTimeout;
    protected $recvTimeout;
    protected $retry;
    protected $namespacePrefix;

    protected $config = [];
    protected $singleServiceConnectionConfig = [];
    protected $multipleServiceConnectionConfig = [];
    protected $services = [
        'singleServiceConnection' => [],
        'multipleServiceConnection' => [],
    ];

    const CONFIG_ITEM_SINGLE = ['sendTimeout', 'recvTimeout', 'serverHost', 'serverPort', 'retry', 'clientClassName', 'namespacePrefix'];

    const CONFIG_ITEM_MULTIPLE_COMMON = ['sendTimeout', 'recvTimeout', 'serverHost', 'serverPort', 'retry'];
    const CONFIG_ITEM_MULTIPLE_SERVICES = ['clientClassName', 'namespacePrefix'];

    public function __construct(array $thriftConfig){
        //从配置文件获取thrift配置
        $this->config = $thriftConfig;
        $this->sendTimeout = isset($this->config['sendTimeout']) ? $this->config['sendTimeout'] : 5;
        $this->recvTimeout = isset($this->config['recvTimeout']) ? $this->config['recvTimeout'] : 5;
        $this->retry = isset($this->config['retry']) ? $this->config['retry'] : 3;
        $this->namespacePrefix = isset($this->config['namespacePrefix']) ? $this->config['namespacePrefix'] : '';

        $this->singleServiceConnectionConfig = isset($this->config['singleServiceConnectionConfig']) ? $this->config['singleServiceConnectionConfig'] : [];
        $this->multipleServiceConnectionConfig = isset($this->config['multipleServiceConnectionConfig']) ? $this->config['multipleServiceConnectionConfig'] : [];

        foreach($this->singleServiceConnectionConfig as $k => $v) {
            $this->services['singleServiceConnection'][$k] = $k;
        }

        foreach($this->multipleServiceConnectionConfig as $socketStr => $config){
            $this->services['multipleServiceConnection'][$socketStr]['protocol'] = '';
            foreach ($config['services'] as  $serviceName => $configArray){
                $this->services['multipleServiceConnection'][$socketStr]['services'][$serviceName] = $serviceName;
            }
        }
    }

    /**
     * 获取特定的thrift服务对应的client
     * @param string $name 服务名
     * @param bool $multiplexed 使用多路协议为true，否则为false
     * @return mixed
     * @throws ThriftException
     */
    public function getService($name, $multiplexed = false){
        $service = $multiplexed ? $this->getMultipleService($name) : $this->getSingleService($name);
        if(!is_null($service)){
            return $service;
        }

        throw new ThriftException('Thrift Service Not Defined: '.$name);
    }

    /**
     * 获取特定的thrift服务对应的client
     * @param string $name 服务名
     * @return mixed
     * @throws ThriftException
     */
    public function __get($name)
    {
        $service = $this->getSingleService($name);
        if(!is_null($service)){
            return $service;
        }

        $service = $this->getMultipleService($name);
        if(!is_null($service)){
            return $service;
        }

        throw new ThriftException('Thrift Service Not Defined: '.$name);
    }

    /**
     * 获取特定的thrift服务对应的client(非多路协议)
     * @param string $name 服务名
     * @return mixed
     * @throws ThriftException
     */
    private function getSingleService($name){
        if(isset($this->services['singleServiceConnection'][$name])){
            if(is_string($this->services['singleServiceConnection'][$name])){
                $config = $this->singleServiceConnectionConfig[$name];
                try{
                    foreach(self::CONFIG_ITEM_SINGLE as $item) {
                        if(empty($config[$item])) {
                            $config[$item] = $this->config[$item];
                        }
                    }
                } catch (\Exception $e){
                    throw new ThriftException("configuration parameter not found！".$e->getMessage());
                }


                for($i = 0; $i<$config['retry']; $i++){
                    try{
                        $transport = new TSocket($config['serverHost'], $config['serverPort']);
                        $transport->setSendTimeout($config['sendTimeout']);
                        $transport->setRecvTimeout($config['recvTimeout']);
                        $transport->open();
                        $protocol = new TBinaryProtocol(new TBufferedTransport($transport));
                        $client = $this->namespacePrefix.$config['clientClassName'];
                        $this->services['singleServiceConnection'][$name] = new $client($protocol);
                        break;

                    }catch(TException $e){
                        if($i == $config['retry']-1){
                            throw new ThriftException('over max connection times!'.$e->getMessage());
                        }
                    }
                }
            }
            return $this->services['singleServiceConnection'][$name];
        }

        return null;
    }

    /**
     * 获取特定的thrift服务对应的client(多路协议)
     * @param string $name 服务名
     * @return mixed
     * @throws ThriftException
     */
    private function getMultipleService($name){
        try{
            foreach ($this->services['multipleServiceConnection'] as $socketStr => $v){
                if(isset($this->services['multipleServiceConnection'][$socketStr]['services'][$name])){
                    if(is_string($this->services['multipleServiceConnection'][$socketStr]['services'][$name])){
                        $protocol = $this->getCommonProtocol($socketStr);
                        $multiplexedProtocol = new TMultiplexedProtocol($protocol, $name);
                        $config = $this->multipleServiceConnectionConfig[$socketStr]['services'][$name];
                        $client = $this->namespacePrefix.$config['clientClassName'];
                        $this->services['multipleServiceConnection'][$socketStr]['services'][$name] = new $client($multiplexedProtocol);
                    }
                    return $this->services['multipleServiceConnection'][$socketStr]['services'][$name];
                }
            }

            return null;

        }catch (\Exception $e){
            throw new ThriftException('can not get multiple service : '.$name.'error :'.$e->getMessage());
        }
    }

    /**
     * 获取共用的protocol
     * @param string $socketStr
     * @return TBinaryProtocol
     * @throws ThriftException
     */
    private function getCommonProtocol($socketStr){
        if(is_string($this->services['multipleServiceConnection'][$socketStr]['protocol'])){
            $config = $this->multipleServiceConnectionConfig[$socketStr];
            try{
                foreach(self::CONFIG_ITEM_MULTIPLE_COMMON as $item) {
                    if(empty($config[$item])) {
                        $config[$item] = $this->config[$item];
                    }
                }
            } catch (\Exception $e){
                throw new ThriftException("configuration parameter not found！".$e->getMessage());
            }

            for($i=0;$i<$config['retry'];$i++){
                try{
                    $socket = new TSocket($config['serverHost'], $config['serverPort']);
                    $socket->setSendTimeout($config['sendTimeout']);
                    $socket->setRecvTimeout($config['recvTimeout']);
                    $transport = new TBufferedTransport($socket);
                    $this->services['multipleServiceConnection'][$socketStr]['protocol'] = new TBinaryProtocol($transport);
                    $transport->open();
                    register_shutdown_function(array(&$transport, 'close'));
                    break;

                }catch(TException $e){
                    if($i == $config['retry']-1){
                        throw new ThriftException('over max connection times!'.$e->getMessage());
                    }
                }
            }
        }

        return $this->services['multipleServiceConnection'][$socketStr]['protocol'];
    }
}