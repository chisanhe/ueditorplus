<?php

/**
 * UEditor Plus 服务提供者
 * 
 * Dcat Admin 扩展服务提供者
 * 负责注册路由、视图、语言包、表单字段等
 * 
 * @package Chisanhe\Ueditorplus
 * @author  chisanhe
 * @since   1.0.0
 */
namespace Chisanhe\Ueditorplus;

use Chisanhe\Ueditorplus\Form\UeditorPlusField;
use Dcat\Admin\Admin;
use Dcat\Admin\Extend\ServiceProvider as BaseServiceProvider;

/**
 * UEditor Plus 服务提供者类
 * 
 * 继承 Dcat Admin 的 ServiceProvider
 * 实现扩展包的初始化和注册
 */
class ChisanheUeditorplusServiceProvider extends BaseServiceProvider
{
    /**
     * 扩展包名称
     * 
     * @const string
     */
    const NAME = 'ueditorplus';

    /**
     * 扩展包版本
     * 
     * @const string
     */
    const VERSION = '1.0.0';

    /**
     * 注册服务
     * 
     * 在 Laravel 应用启动时调用
     * 用于绑定接口到实现等
     */
    public function register()
    {
        // 注册存储接口到本地实现
        // 可扩展：注册 OSS 存储等
        $this->app->singleton(
            \Chisanhe\Ueditorplus\Storage\StorageInterface::class,
            \Chisanhe\Ueditorplus\Storage\LocalStorage::class
        );
    }

    /**
     * 初始化扩展包
     * 
     * 在 Dcat Admin 启动完成后调用
     * 用于注册表单字段、路由、视图、语言包等
     */
    public function init()
    {
        // 注册表单字段扩展
        // 使用 $form->ueditorplus('field_name') 调用
        \Dcat\Admin\Form::extend('ueditorplus', UeditorPlusField::class);

        // 加载视图路径
        $this->loadViewsFrom($this->resourcePath('views'), self::NAME);

        // 加载语言包
        $this->loadTranslationsFrom($this->resourcePath('lang'), self::NAME);

        // 注册路由
        $this->registerRoutes();

        // 发布资源文件
        $this->publishAssets();

        // 发布配置文件
        $this->publishConfig();
    }

    /**
     * 注册路由
     * 
     * 在 Dcat Admin 应用中注册 UEditor Plus 的路由
     */
    protected function registerRoutes()
    {
        // 在 Dcat Admin 应用启动完成后注册路由
        $this->app->booted(function () {
            // 获取路由配置
            $routeConfig = UeditorPlus::getUploadConfig('route', []);
            
            // 合并默认配置和用户配置
            $attributes = [
                'prefix' => isset($routeConfig['prefix']) ? $routeConfig['prefix'] : 'ueditorplus',
                'middleware' => isset($routeConfig['middleware']) ? $routeConfig['middleware'] : ['web', 'admin'],
            ];

            // 注册路由
            Admin::app()->routes(function ($router) use ($attributes) {
                $router->group($attributes, function ($router) {
                    // 加载路由文件
                    require __DIR__ . '/Http/routes.php';
                });
            });
        });
    }

    /**
     * 发布静态资源文件
     * 
     * 将扩展包的静态资源发布到应用的 public 目录
     */
    protected function publishAssets()
    {
        // 发布资源到 public 目录
        $this->publishes([
            $this->resourcePath('assets') => public_path('vendor/' . self::NAME),
        ], 'chisanhe-ueditorplus-assets');
    }

    /**
     * 发布配置文件
     * 
     * 将扩展包的配置文件发布到应用的 config 目录
     */
    protected function publishConfig()
    {
        // 发布配置文件到 config 目录
        $this->publishes([
            __DIR__ . '/../config/ueditorplus.php' => config_path('ueditorplus.php'),
        ], 'chisanhe-ueditorplus-config');
    }

    /**
     * 获取资源路径
     * 
     * @param string $path 相对于资源目录的路径
     * @return string 完整的资源路径
     */
    protected function resourcePath($path = '')
    {
        $basePath = __DIR__ . '/../resources';
        
        if ($path) {
            return $basePath . '/' . $path;
        }
        
        return $basePath;
    }

    /**
     * 设置表单字段
     * 
     * @return \Chisanhe\Ueditorplus\Form\UeditorPlusField
     */
    public function settingForm()
    {
        // 返回设置表单实例
        // 如需扩展设置页面，可在此方法中实现
        return null;
    }
}
