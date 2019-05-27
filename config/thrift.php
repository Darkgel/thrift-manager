<?php
return [
    //default配置
    'namespacePrefix' => '',//命名空间前缀
    'sendTimeout' => 3000,//毫秒
    'recvTimeout' => 3000,//毫秒
    'retry' => 3,

    'singleServiceConnectionConfig' => [
        //没有使用多路协议的配置例子
//        'singleSampleService'=>[
//            'clientClassName'   => 'sample\SampleThriftServiceClient',
//            'serverHost'        => '127.0.0.1',
//            'serverPort'        => '12345',
//            'sendTimeout'       => 30000,
//            'recvTimeout'       => 30000,
//            'retry'             => 3,
//        ],
    ],

    'multipleServiceConnectionConfig' => [
        //使用多路协议的配置例子
//        'localhost:7911' => [
//            'sendTimeout' => 20,
//            'recvTimeout' => 20,
//            'serverHost' => 'localhost',
//            'serverPort' => 7911,
//            'retry' => 2,
//            'services' => [//这里可以配置多个服务
//                'MultiplicationService' => [
//                    'clientClassName'   => 'MultiplicationServiceClient',
//                ],
//                'AdditionService' => [
//                    'clientClassName'   => 'AdditionServiceClient',
//                ],
//            ],
//        ],
    ],
];