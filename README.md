# thrift-manager
该thrift manager组件用于在laravel上管理thrift调用，使得thrift调用更加方便
## 安装
 - composer require darkgel/thrift-manager
## 配置
 - 将Darkgel\Thrift\ServiceProvider::class添加到laravel配置文件app.php中的providers数组
 - 执行命令“php artisan vendor:publish --provider="Darkgel\Thrift\ServiceProvider"”，将thrift.php配置文件发布到config目录下
 - 在thrift.php文件中配置相应的service
## 使用
a. 将相应的thrift生成代码放置到目录app\Services\Thrift\Gen\下（可以根据自己的需要使用不同的目录），修改composer.json中的配置：
```
"autoload": {
    "classmap": [
        "database/seeds",
        "database/factories",
        "app/Services/Thrift/Gen"//添加这一行
    ],
    "psr-4": {
        "App\\": "app/"
    }
},
```
b. 运行命令：composer dump-autoload -o,生成相应的classmap映射，这样就可以在代码中通过composer自动加载thrift的生成代码了 

c. 配置：在config\thrift.php中添加配置，可参考现有的配置。以下是一个例子
```
<?php
return [
    //default配置
    'namespacePrefix' => '',命名空间前缀
    'sendTimeout' => 3000,//毫秒
    'recvTimeout' => 3000,//毫秒
    'retry' => 3,

    'singleServiceConnectionConfig' => [
        //没有使用多路协议
        'TestService'=>[
            'clientClassName'   => 'test\TestServiceClient',
            'serverHost'        => '127.0.0.1',
            'serverPort'        => '9292',
            'sendTimeout'       => 30000,
            'recvTimeout'       => 30000,
            'retry'             => 3,
        ],
    ],

    'multipleServiceConnectionConfig' => [
        //使用多路协议的配置例子
        'localhost:7911' => [
            'sendTimeout' => 20,
            'recvTimeout' => 20,
            'serverHost' => '10.3.20.168',
            'serverPort' => 7911,
            'retry' => 2,
            'services' => [//这里可以配置多个服务
                'MultiplicationService' => [
                    'clientClassName'   => 'thriftgen\service\MultiplicationServiceClient',
                ],
                'AdditionService' => [
                    'clientClassName'   => 'thriftgen\service\AdditionServiceClient',
                ],
            ],
        ],
    ],
];
```

d. 在目录app\Services\Thrift（该目录按自己的实际情况选择）下添加一个ThriftService类(该类可以在vendor/darkgel/thrift-manager/examples下找到)
，使用该ThriftService作为所有thrift服务类的基类。
```
<?php
namespace App\Services\Thrift;

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
```
e. 在目录app\Services\Thrift\Services(该目录按自己的实际情况选择)下添加一个具体的Service类（可在vendor/darkgel/thrift-manager/examples找到该例子），该类必须继承自上面的ThriftService类。配置该service类的属性：$instance（必须有，用于实现单例模式，赋值为null即可）；$service（必须有，与thrift配置相关，见上面的thrift配置）；$multiplexed(可选，若为true表示使用多路协议)。例子如下：
```
<?php
namespace App\Services\Thrift\Services;

use App\Services\Thrift\ThriftService;

class AdditionService extends ThriftService
{
    //子类必须声明该属性,以指向当前类的单实例
    protected static $instance = null;
    
    public $service = 'AdditionService';//这里对应thrift.php中的配置
    protected $multiplexed = true;
}
```
f. 通过静态方法调用上面添加的service类，eg:AdditionService::add(1, 2)。add方法名即thrift远程提供的方法名。Services目录下的目录结构如图：
   - Services(app/Services目录，放置应用中的service类)
     - Thrift
       - Gen（该目录下放置所有thrift生成的代码）
       - Services（该目录下放置相应的thrift服务类）
         - AdditionService.php（具体的thrift服务类）
       - ThriftService.php(所有thrift服务类的基类)
     
## 注意
 - 每次添加/修改thrift的生成代码到Gen目录下后，执行composer dump-autoload -o,生成相应的classmap映射