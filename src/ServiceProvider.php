<?php
/**
 *  * Created by PhpStorm.
 * User: Darkgel
 * Date: 2018/10/9
 * Time: 14:58
 */

namespace Darkgel\Thrift;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Config\Repository;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * 服务提供者加是否延迟加载.
     *
     * @var bool
     */
    protected $defer = true; // 延迟加载服务

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishConfig();
    }

    private function publishConfig()
    {
        $path = $this->getConfigPath();
        $this->publishes([$path => config_path('thrift.php')], 'config');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfig();

        // 单例绑定服务
        $this->app->singleton('thriftManager', function ($app) {
            /** @var $config Repository  */
            $config = $app['config'];
            $thriftConfig = $config->get('thrift');
            return new ThriftManager($thriftConfig);
        });
    }

    private function mergeConfig()
    {
        $path = $this->getConfigPath();
        $this->mergeConfigFrom($path, 'thrift');
    }

    private function getConfigPath()
    {
        return __DIR__ . '/../config/thrift.php';
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        // 因为延迟加载 所以要定义 provides 函数 具体参考laravel 文档
        return ['thriftManager'];
    }
}