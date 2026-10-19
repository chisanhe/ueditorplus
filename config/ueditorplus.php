<?php

/**
 * UEditor Plus 配置文件
 * 
 * 配置前后端通信相关的上传参数
 * 
 * @package Chisanhe\Ueditorplus\Config
 * @author  chisanhe
 * @since   1.0.0
 */

return [
    /**
     * 存储磁盘配置
     * 
     * 使用 Laravel Filesystem 的磁盘配置
     * 默认使用 Dcat Admin 的上传磁盘设置
     * 
     * @var string
     */
    'disk' => '',

    /**
     * 是否使用哈希文件名
     * 
     * true: 使用 MD5 哈希作为文件名（原文件名作为扩展名保留）
     * false: 使用原始文件名
     * 
     * @var bool
     */
    'hashFilename' => true,

    // ==================== 图片上传配置 ====================

    /**
     * 图片上传 action 名称
     * 
     * @var string
     */
    'imageActionName' => 'uploadimage',

    /**
     * 图片上传表单字段名
     * 
     * @var string
     */
    'imageFieldName' => 'upfile',

    /**
     * 图片最大上传大小（单位：字节）
     * 
     * 默认 2MB = 2048000 字节
     * 
     * @var int
     */
    'imageMaxSize' => 2048000,

    /**
     * 允许上传的图片格式
     * 
     * @var array
     */
    'imageAllowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp'],

    /**
     * 图片访问路径前缀
     * 
     * 用于拼接完整的图片 URL
     * 
     * @var string
     */
    'imageUrlPrefix' => '',

    /**
     * 图片保存路径格式
     * 
     * 支持的变量：
     * - {yyyy} 四位年份
     * - {yy} 两位年份
     * - {mm} 月份
     * - {dd} 日期
     * - {hh} 小时
     * - {ii} 分钟
     * - {ss} 秒
     * - {filename} 原始文件名
     * - {time} 时间戳
     * - {rand:n} 随机数，n 为位数
     * 
     * @var string
     */
    'imagePathFormat' => '/ueditorplus/image/{yyyy}{mm}{dd}/{time}{rand:6}',

    // ==================== 视频上传配置 ====================

    /**
     * 视频上传 action 名称
     * 
     * @var string
     */
    'videoActionName' => 'uploadvideo',

    /**
     * 视频上传表单字段名
     * 
     * @var string
     */
    'videoFieldName' => 'upfile',

    /**
     * 视频最大上传大小（单位：字节）
     * 
     * 默认 100MB = 102400000 字节
     * 
     * @var int
     */
    'videoMaxSize' => 102400000,

    /**
     * 允许上传的视频格式
     * 
     * @var array
     */
    'videoAllowFiles' => [
        '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb',
        '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv',
        '.mp4', '.webm', '.mp3', '.wav', '.mid'
    ],

    /**
     * 视频访问路径前缀
     * 
     * @var string
     */
    'videoUrlPrefix' => '',

    /**
     * 视频保存路径格式
     * 
     * @var string
     */
    'videoPathFormat' => '/ueditorplus/video/{yyyy}{mm}{dd}/{time}{rand:6}',

    // ==================== 文件上传配置 ====================

    /**
     * 文件上传 action 名称
     * 
     * @var string
     */
    'fileActionName' => 'uploadfile',

    /**
     * 文件上传表单字段名
     * 
     * @var string
     */
    'fileFieldName' => 'upfile',

    /**
     * 文件最大上传大小（单位：字节）
     * 
     * 默认 50MB = 51200000 字节
     * 
     * @var int
     */
    'fileMaxSize' => 51200000,

    /**
     * 允许上传的文件格式
     * 
     * @var array
     */
    'fileAllowFiles' => [
        '.png', '.jpg', '.jpeg', '.gif', '.bmp',
        '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb',
        '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv',
        '.mp4', '.webm', '.mp3', '.wav', '.mid',
        '.rar', '.zip', '.tar', '.gz', '.7z', '.bz2', '.cab', '.iso',
        '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx',
        '.pdf', '.txt', '.md', '.xml'
    ],

    /**
     * 文件访问路径前缀
     * 
     * @var string
     */
    'fileUrlPrefix' => '',

    /**
     * 文件保存路径格式
     * 
     * @var string
     */
    'filePathFormat' => '/ueditorplus/file/{yyyy}{mm}{dd}/{time}{rand:6}',

    // ==================== 远程图片抓取配置 ====================

    /**
     * 允许抓取的本地域名
     * 
     * 远程图片在这些域名下时不会被抓取到本地
     * 
     * @var array
     */
    'catcherLocalDomain' => ['127.0.0.1', 'localhost', 'img.baidu.com'],

    /**
     * 远程图片抓取 action 名称
     * 
     * @var string
     */
    'catcherActionName' => 'catchimage',

    /**
     * 远程图片字段名
     * 
     * @var string
     */
    'catcherFieldName' => 'source',

    /**
     * 抓取图片最大大小（单位：字节）
     * 
     * @var int
     */
    'catcherMaxSize' => 2048000,

    /**
     * 允许抓取的图片格式
     * 
     * @var array
     */
    'catcherAllowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp'],

    /**
     * 抓取图片访问路径前缀
     * 
     * @var string
     */
    'catcherUrlPrefix' => '',

    /**
     * 抓取图片保存路径格式
     * 
     * @var string
     */
    'catcherPathFormat' => '/ueditorplus/catcher/{yyyy}{mm}{dd}/{time}{rand:6}',

    // ==================== 图片管理器配置 ====================

    /**
     * 图片管理器 action 名称
     * 
     * @var string
     */
    'imageManagerActionName' => 'listimage',

    /**
     * 图片管理器列表路径
     * 
     * @var string
     */
    'imageManagerListPath' => '/ueditorplus/image/',

    /**
     * 图片管理器每页数量
     * 
     * @var int
     */
    'imageManagerListSize' => 20,

    /**
     * 图片管理器路径前缀
     * 
     * @var string
     */
    'imageManagerUrlPrefix' => '',

    /**
     * 图片管理器允许的文件类型
     * 
     * @var array
     */
    'imageManagerAllowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp'],

    // ==================== 文件管理器配置 ====================

    /**
     * 文件管理器 action 名称
     * 
     * @var string
     */
    'fileManagerActionName' => 'listfile',

    /**
     * 文件管理器列表路径
     * 
     * @var string
     */
    'fileManagerListPath' => '/ueditorplus/file/',

    /**
     * 文件管理器每页数量
     * 
     * @var int
     */
    'fileManagerListSize' => 20,

    /**
     * 文件管理器路径前缀
     * 
     * @var string
     */
    'fileManagerUrlPrefix' => '',

    /**
     * 文件管理器允许的文件类型
     * 
     * @var array
     */
    'fileManagerAllowFiles' => [
        '.png', '.jpg', '.jpeg', '.gif', '.bmp',
        '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb',
        '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv',
        '.mp4', '.webm', '.mp3', '.wav', '.mid',
        '.rar', '.zip', '.tar', '.gz', '.7z', '.bz2', '.cab', '.iso',
        '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx',
        '.pdf', '.txt', '.md', '.xml'
    ],

    // ==================== 路由配置 ====================

    /**
     * 路由配置
     * 
     * 可自定义路由前缀和中间件
     * 
     * @var array
     */
    'route' => [
        // 路由前缀，默认 'ueditorplus'
        'prefix' => 'ueditorplus',
        
        // 路由中间件
        'middleware' => ['web', 'admin'],
    ],

    // ==================== 135编辑器配置 ====================

    /**
     * 135编辑器配置
     * 
     * 135编辑器是一款强大的在线排版编辑器
     * 
     * @see https://www.135editor.com
     * 
     * @var array
     */
    'editor135' => [
        // 是否启用135编辑器
        'enable' => true,
        
        // 135编辑器 API 地址
        'apiUrl' => '',
        
        // 135编辑器 Key
        'key' => '',
    ],

    // ==================== 秀米编辑器配置 ====================

    /**
     * 秀米编辑器配置
     * 
     * 秀米编辑器是一款简洁美观的在线图文编辑工具
     * 
     * @see https://xiumi.us
     * 
     * @var array
     */
    'xiumi' => [
        // 是否启用秀米编辑器
        'enable' => true,
        
        // 秀米 business ID
        'biz' => '',
        
        // 秀米 API 地址
        'apiUrl' => '',
    ],

    // ==================== AI 智能配置 ====================

    /**
     * AI 智能配置
     * 
     * 支持 OpenAI、DeepSeek、百度文心、阿里通义等多种大模型
     * 
     * @var array
     */
    'ai' => [
        // 是否启用 AI 功能
        'enable' => false,
        
        // 大模型驱动：OpenAi, DeepSeek, Baidu, Alibaba, Tencent, etc.
        'driver' => 'OpenAi',
        
        // API 地址
        'apiUrl' => '',
        
        // API Key
        'apiKey' => '',
        
        // 模型名称
        'model' => 'gpt-3.5-turbo',
    ],
];
