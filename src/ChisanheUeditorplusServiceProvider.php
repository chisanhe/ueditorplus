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
     * 扩展包标识 (用于 Dcat Admin 扩展系统)
     * 格式：vendor.package (用点分隔)
     *
     * @var string
     */
    protected $name = 'chisanhe.ueditorplus';

    /**
     * {@inheritdoc}
     *
     * 覆盖父类方法，让本地开发模式下扩展始终视为启用。
     * 父类会检查 Admin::extension()->enabled()，需要扩展在 Dcat Admin 扩展管理中注册才返回 true。
     * 这里直接返回 true，确保 init() 流程能执行（包括 publishable 注册资源）。
     *
     * @return bool
     */
    public function enabled()
    {
        return true;
    }

    /**
     * JS 文件
     * 
     * @var array
     */
    protected $js = [
        'ueditor.config.js',
        'ueditor.all.js',
        'ueditor.parse.js',
    ];

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
        $this->loadViewsFrom($this->resourcePath('views'), $this->getName());

        // 加载语言包
        $this->loadTranslationsFrom($this->resourcePath('lang'), $this->getName());

        // 注册路由
        $this->app->booted(function () {
            Admin::app()->routes(function ($router) {
                $attributes = array_merge([
                    'prefix'     => config('admin.route.prefix'),
                    'middleware' => config('admin.route.middleware'),
                ], $this->config('route', []));

                $router->group($attributes, function ($router) {
                    require __DIR__ . '/Http/routes.php';
                });
            });
        });

        // 发布静态资源和配置文件
        $this->publishes([
            $this->resourcePath('assets') => public_path('vendor/chisanhe/ueditorplus'),
            __DIR__ . '/../config/ueditorplus.php' => config_path('ueditorplus.php'),
        ], $this->getName());

        // 调用父类初始化方法
        parent::init();

        // 调用 publishable 注册资源发布（父类的 init 不会调用它）
        $this->publishable();
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
