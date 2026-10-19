<?php

/**
 * 存储接口定义
 * 
 * 定义 UEditor Plus 文件存储的统一接口
 * 预留 OSS 等云存储扩展点
 * 
 * @package Chisanhe\Ueditorplus\Storage
 * @author  chisanhe
 * @since   1.0.0
 */
namespace Chisanhe\Ueditorplus\Storage;

use Illuminate\Http\Request;

/**
 * 存储接口
 * 
 * 所有存储驱动必须实现此接口
 * 可扩展实现 OSS、AWS S3、七牛云等云存储
 */
interface StorageInterface
{
    /**
     * 上传文件
     * 
     * 处理文件上传的核心方法
     * 
     * @param Request $request Laravel 请求对象
     * @return \Illuminate\Http\JsonResponse 上传结果响应
     */
    public function upload(Request $request);

    /**
     * 抓取远程图片
     * 
     * 从远程 URL 下载并保存图片
     * 
     * @param Request $request Laravel 请求对象
     * @return \Illuminate\Http\JsonResponse 抓取结果响应
     */
    public function fetch(Request $request);

    /**
     * 列出文件
     * 
     * 获取指定目录下的文件列表
     * 
     * @param string $path 目录路径
     * @param int $start 起始位置
     * @param int $size 每页数量
     * @param array $allowFiles 允许的文件扩展名
     * @return array 文件列表数据
     */
    public function listFiles($path, $start = 0, $size = 20, array $allowFiles = []);

    /**
     * 获取文件 URL
     * 
     * 根据文件名返回完整的访问 URL
     * 
     * @param string $filename 文件名/路径
     * @return string 完整的访问 URL
     */
    public function getUrl($filename);

    /**
     * 格式化存储路径
     * 
     * 根据配置的时间格式和随机数生成规则格式化路径
     * 
     * @param string $path 路径格式模板
     * @param string $filename 文件名
     * @return string 格式化后的完整路径
     */
    public function formatPath($path, $filename);
}
