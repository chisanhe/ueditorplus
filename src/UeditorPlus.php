<?php

/**
 * UeditorPlus 核心类
 * 
 * 该类是扩展包的主入口类，提供配置获取和扩展包基本信息
 * 
 * @package Chisanhe\Ueditorplus
 * @author  chisanhe
 * @since   1.0.0
 */
namespace Chisanhe\Ueditorplus;

use Illuminate\Support\Arr;

/**
 * UeditorPlus 富文本编辑器扩展核心类
 * 
 * 提供 UEditor Plus 所需的配置信息和基础功能
 */
class UeditorPlus
{
    /**
     * 扩展包名称标识
     * 
     * @const string
     */
    const NAME = 'ueditorplus';

    /**
     * 扩展包版本号
     * 
     * @const string
     */
    const VERSION = '1.1.0';

    /**
     * 服务提供者类名
     * 
     * @var string
     */
    protected $serviceProvider = ChisanheUeditorplusServiceProvider::class;

    /**
     * 视图资源路径
     * 
     * @var string
     */
    public $views = null;

    /**
     * 静态资源路径
     * 
     * @var string
     */
    public $assets = null;

    /**
     * 语言文件路径
     * 
     * @var string
     */
    public $lang = null;

    /**
     * 构造函数
     * 
     * 初始化资源路径等属性
     */
    public function __construct()
    {
        // 设置视图资源路径
        $this->views = __DIR__ . '/../resources/views';
        
        // 设置静态资源路径
        $this->assets = __DIR__ . '/../resources/assets';
        
        // 设置语言文件路径
        $this->lang = __DIR__ . '/../resources/lang';
    }

    /**
     * 获取上传配置
     * 
     * 从配置文件中获取 UEditor 上传相关的配置项
     * 支持获取完整配置或指定键的配置值
     * 
     * @param string|null $key 配置键名，传入 null 则返回全部配置
     * @param mixed $default 默认值，当键不存在时返回此值
     * @return mixed 返回配置值或全部配置数组
     */
    public static function getUploadConfig($key = null, $default = null)
    {
        // 优先从 Laravel 配置缓存获取，若无则直接引入配置文件
        $config = config('ueditorplus') ?: (include __DIR__ . '/../config/ueditorplus.php');

        // 如果未指定键名，返回完整配置数组
        if ($key === null) {
            return $config;
        }

        // 使用 Arr::get 安全获取嵌套配置值
        return Arr::get($config, $key, $default);
    }

    /**
     * 获取上传配置项的完整映射
     * 
     * 返回一个包含所有上传相关配置的统一数组结构
     * 用于前端配置和后端验证
     * 
     * @return array 上传配置数组
     */
    public static function getConfig()
    {
        $config = self::getUploadConfig();
        
        // 返回可直接用于 JSON 响应的配置
        return [
            // 图片上传配置
            'imageActionName' => Arr::get($config, 'imageActionName', 'uploadimage'),
            'imageFieldName' => Arr::get($config, 'imageFieldName', 'upfile'),
            'imageMaxSize' => Arr::get($config, 'imageMaxSize', 2048000),
            'imageAllowFiles' => Arr::get($config, 'imageAllowFiles', ['.png', '.jpg', '.jpeg', '.gif', '.bmp']),
            'imageUrlPrefix' => Arr::get($config, 'imageUrlPrefix', ''),
            'imagePathFormat' => Arr::get($config, 'imagePathFormat', '/ueditorplus/image/{yyyy}{mm}{dd}/{time}{rand:6}'),
            
            // 视频上传配置
            'videoActionName' => Arr::get($config, 'videoActionName', 'uploadvideo'),
            'videoFieldName' => Arr::get($config, 'videoFieldName', 'upfile'),
            'videoMaxSize' => Arr::get($config, 'videoMaxSize', 102400000),
            'videoAllowFiles' => Arr::get($config, 'videoAllowFiles', [
                '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb', '.mpeg', '.mpg',
                '.ogg', '.ogv', '.mov', '.wmv', '.mp4', '.webm', '.mp3', '.wav', '.mid'
            ]),
            'videoUrlPrefix' => Arr::get($config, 'videoUrlPrefix', ''),
            'videoPathFormat' => Arr::get($config, 'videoPathFormat', '/ueditorplus/video/{yyyy}{mm}{dd}/{time}{rand:6}'),
            
            // 文件上传配置
            'fileActionName' => Arr::get($config, 'fileActionName', 'uploadfile'),
            'fileFieldName' => Arr::get($config, 'fileFieldName', 'upfile'),
            'fileMaxSize' => Arr::get($config, 'fileMaxSize', 51200000),
            'fileAllowFiles' => Arr::get($config, 'fileAllowFiles', [
                '.png', '.jpg', '.jpeg', '.gif', '.bmp',
                '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb', '.mpeg', '.mpg',
                '.ogg', '.ogv', '.mov', '.wmv', '.mp4', '.webm', '.mp3', '.wav', '.mid',
                '.rar', '.zip', '.tar', '.gz', '.7z', '.bz2', '.cab', '.iso',
                '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.pdf', '.txt', '.md', '.xml'
            ]),
            'fileUrlPrefix' => Arr::get($config, 'fileUrlPrefix', ''),
            'filePathFormat' => Arr::get($config, 'filePathFormat', '/ueditorplus/file/{yyyy}{mm}{dd}/{time}{rand:6}'),
            
            // 远程图片抓取配置
            'catcherActionName' => Arr::get($config, 'catcherActionName', 'catchimage'),
            'catcherFieldName' => Arr::get($config, 'catcherFieldName', 'source'),
            'catcherMaxSize' => Arr::get($config, 'catcherMaxSize', 2048000),
            'catcherAllowFiles' => Arr::get($config, 'catcherAllowFiles', ['.png', '.jpg', '.jpeg', '.gif', '.bmp']),
            'catcherUrlPrefix' => Arr::get($config, 'catcherUrlPrefix', ''),
            'catcherPathFormat' => Arr::get($config, 'catcherPathFormat', '/ueditorplus/catcher/{yyyy}{mm}{dd}/{time}{rand:6}'),
            'catcherLocalDomain' => Arr::get($config, 'catcherLocalDomain', ['127.0.0.1', 'localhost']),
            'catcherTimeout' => Arr::get($config, 'catcherTimeout', 30),
            'catcherMaxCount' => Arr::get($config, 'catcherMaxCount', 50),
            'catcherUserAgent' => Arr::get($config, 'catcherUserAgent', ''),
            'catcherReferer' => Arr::get($config, 'catcherReferer', 'auto'),
            'catcherVerifySsl' => Arr::get($config, 'catcherVerifySsl', true),

            // 涂鸦上传配置
            'scrawlActionName' => Arr::get($config, 'scrawlActionName', 'uploadscrawl'),
            'scrawlFieldName' => Arr::get($config, 'scrawlFieldName', 'upfile'),
            'scrawlMaxSize' => Arr::get($config, 'scrawlMaxSize', 2048000),
            'scrawlAllowFiles' => Arr::get($config, 'scrawlAllowFiles', ['.png']),
            'scrawlUrlPrefix' => Arr::get($config, 'scrawlUrlPrefix', ''),
            'scrawlPathFormat' => Arr::get($config, 'scrawlPathFormat', '/ueditorplus/scrawl/{yyyy}{mm}{dd}/{time}{rand:6}'),

            // 截图上传配置
            'snapscreenActionName' => Arr::get($config, 'snapscreenActionName', 'uploadimage'),
            'snapscreenFieldName' => Arr::get($config, 'snapscreenFieldName', 'upfile'),
            'snapscreenMaxSize' => Arr::get($config, 'snapscreenMaxSize', 2048000),
            'snapscreenAllowFiles' => Arr::get($config, 'snapscreenAllowFiles', ['.png', '.jpg', '.jpeg', '.gif', '.bmp']),
            'snapscreenUrlPrefix' => Arr::get($config, 'snapscreenUrlPrefix', ''),
            'snapscreenPathFormat' => Arr::get($config, 'snapscreenPathFormat', '/ueditorplus/image/{yyyy}{mm}{dd}/{time}{rand:6}'),
            
            // 图片管理列表配置
            'imageManagerActionName' => Arr::get($config, 'imageManagerActionName', 'listimage'),
            'imageManagerListPath' => Arr::get($config, 'imageManagerListPath', '/ueditorplus/image/'),
            'imageManagerListSize' => Arr::get($config, 'imageManagerListSize', 20),
            'imageManagerUrlPrefix' => Arr::get($config, 'imageManagerUrlPrefix', ''),
            'imageManagerAllowFiles' => Arr::get($config, 'imageManagerAllowFiles', ['.png', '.jpg', '.jpeg', '.gif', '.bmp']),
            
            // 文件管理列表配置
            'fileManagerActionName' => Arr::get($config, 'fileManagerActionName', 'listfile'),
            'fileManagerListPath' => Arr::get($config, 'fileManagerListPath', '/ueditorplus/file/'),
            'fileManagerListSize' => Arr::get($config, 'fileManagerListSize', 20),
            'fileManagerUrlPrefix' => Arr::get($config, 'fileManagerUrlPrefix', ''),
            'fileManagerAllowFiles' => Arr::get($config, 'fileManagerAllowFiles', [
                '.png', '.jpg', '.jpeg', '.gif', '.bmp',
                '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb', '.mpeg', '.mpg',
                '.ogg', '.ogv', '.mov', '.wmv', '.mp4', '.webm', '.mp3', '.wav', '.mid',
                '.rar', '.zip', '.tar', '.gz', '.7z', '.bz2', '.cab', '.iso',
                '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.pdf', '.txt', '.md', '.xml'
            ]),
            
            // 其他配置
            'hashFilename' => Arr::get($config, 'hashFilename', true),
            'disk' => Arr::get($config, 'disk', 'public'),
            // 远程图片抓取开关（前端 UEditor 会读取此字段）
            'catchRemoteImageEnable' => Arr::get($config, 'catchRemoteImageEnable', true),
            'catchRemoteImage' => Arr::get($config, 'catchRemoteImageEnable', true),
        ];
    }
}
