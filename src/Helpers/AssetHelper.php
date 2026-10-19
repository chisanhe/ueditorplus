<?php

/**
 * UEditor Plus 资源路径助手
 * 
 * 用于处理扩展包资源的路径注册和发布
 * 
 * @package Chisanhe\Ueditorplus\Helpers
 * @author  chisanhe
 * @since   1.0.0
 */
namespace Chisanhe\Ueditorplus\Helpers;

use Illuminate\Foundation\Application;

/**
 * 资源路径助手类
 * 
 * 提供资源路径处理和包注册的辅助方法
 */
class AssetHelper
{
    /**
     * 注册包的资源路径
     * 
     * 将扩展包的命名空间映射到资源路径
     * 
     * @param Application $app Laravel 应用实例
     * @param string $name 包名称
     * @param string $path 资源路径
     */
    public static function registerPackage($app, $name, $path)
    {
        // 获取包命名空间
        $namespace = 'chisanhe.' . $name;
        
        // 获取视图工厂
        $viewFactory = $app['view'];
        
        // 注册视图命名空间
        $viewFactory->addNamespace($namespace, $path);
    }

    /**
     * 获取包资源的基础路径
     * 
     * @param Application $app Laravel 应用实例
     * @param string $name 包名称
     * @return string 资源路径
     */
    public static function getPackagePath($app, $name)
    {
        return $app->basePath('vendor/chisanhe/' . $name);
    }

    /**
     * 发布资源文件
     * 
     * @param string $from 源路径
     * @param string $to 目标路径
     * @return array 发布配置
     */
    public static function publishAssets($from, $to)
    {
        return [
            $from => $to,
        ];
    }
}
