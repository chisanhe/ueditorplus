<?php

/**
 * 本地存储实现类
 * 
 * 使用 Laravel Filesystem 本地磁盘进行文件存储
 * 实现 StorageInterface 接口
 * 
 * @package Chisanhe\Ueditorplus\Storage
 * @author  chisanhe
 * @since   1.0.0
 */
namespace Chisanhe\Ueditorplus\Storage;

use Chisanhe\Ueditorplus\UeditorPlus;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * 本地存储驱动类
 * 
 * 负责处理文件上传、远程图片抓取、文件列表等本地存储操作
 */
class LocalStorage implements StorageInterface
{
    /**
     * Laravel Filesystem 实例
     * 
     * @var Filesystem
     */
    protected $disk;

    /**
     * 构造函数
     * 
     * @param Filesystem $disk Laravel 文件系统实例
     */
    public function __construct(Filesystem $disk)
    {
        $this->disk = $disk;
    }

    /**
     * 上传文件
     * 
     * 处理图片、视频、文件等上传请求
     * 包括文件验证、路径格式化、事件触发等
     * 
     * @param Request $request Laravel 请求对象
     * @return \Illuminate\Http\JsonResponse 上传结果
     */
    public function upload(Request $request)
    {
        // 获取当前 action 对应的上传配置
        $config = $this->getUploadConfig($request->get('action'));

        // 检查是否有文件上传
        $fieldName = Arr::get($config, 'field_name', 'upfile');
        if (!$request->hasFile($fieldName)) {
            return $this->error('UPLOAD_ERR_NO_FILE');
        }

        // 获取上传的文件
        $file = $request->file($fieldName);

        // 验证文件是否有效
        if ($error = $this->fileHasError($file, $config)) {
            return $this->error($error);
        }

        // 生成文件名
        $filename = $this->getFilename($file, $config);

        // 存储文件到磁盘
        $this->store($file, $filename);

        // 构建成功响应
        $response = [
            'state' => 'SUCCESS',
            'url' => $this->getUrl($filename),
            'title' => $filename,
            'original' => $file->getClientOriginalName(),
            'type' => $file->getExtension(),
            'size' => $file->getSize(),
        ];

        return response()->json($response);
    }

    /**
     * 抓取远程图片
     * 
     * 从远程 URL 下载并保存图片到本地存储
     * 支持批量抓取多个图片
     * 
     * @param Request $request Laravel 请求对象
     * @return \Illuminate\Http\JsonResponse 抓取结果
     */
    public function fetch(Request $request)
    {
        // 获取抓取配置
        $config = $this->getUploadConfig($request->get('action'));
        
        // 获取要抓取的图片 URL 列表
        $fieldName = Arr::get($config, 'field_name', 'source');
        $urls = $request->get($fieldName, []);

        // 检查是否有 URL
        if (empty($urls)) {
            return $this->error('UPLOAD_ERR_NO_FILE');
        }

        // 去重处理
        $urls = array_unique($urls);
        
        $list = [];
        foreach ($urls as $url) {
            // 下载单个图片
            $img = $this->download($url, $config);
            
            if ($img['state'] === 'SUCCESS') {
                // 存储图片内容
                $this->storeContent($img['file'], $img['filename']);
            }
            
            // 移除 file 字段（不需要返回）
            unset($img['file']);
            $list[] = $img;
        }

        // 构建响应
        $response = [
            'state' => empty($list) ? 'ERROR' : 'SUCCESS',
            'list' => $list
        ];

        return response()->json($response);
    }

    /**
     * 下载远程文件
     * 
     * 从 URL 下载文件内容
     * 
     * @param string $url 远程文件 URL
     * @param array $config 上传配置
     * @return array 下载结果信息
     */
    protected function download($url, $config)
    {
        // 检查 URL 是否为有效的 HTTP(S) 链接
        if (strpos($url, 'http') !== 0) {
            return $this->errorResult('ERROR_HTTP_LINK', $url);
        }

        // 解析 URL 获取路径信息
        $pathRes = parse_url($url);
        $img = new \SplFileInfo($pathRes['path']);
        $original = $img->getFilename();
        $ext = $img->getExtension() ?: 'jpg';
        $title = md5($url) . '.' . $ext;
        $filename = $this->formatPath($config['path_format'], $title);

        // 构建返回信息
        $info = [
            'state' => 'SUCCESS',
            'url' => $this->getUrl($filename),
            'title' => $title,
            'original' => $original,
            'source' => $url,
            'size' => 0,
            'file' => '',
            'filename' => $filename,
        ];

        // 创建 HTTP 请求上下文
        $context = stream_context_create([
            'http' => [
                'follow_location' => true, // 自动跟随重定向
                'timeout' => 30, // 超时时间 30 秒
            ]
        ]);

        // 打开远程文件流
        $file = @fopen($url, 'r', false, $context);
        
        if ($file === false) {
            $info['state'] = 'ERROR';
            return $info;
        }

        // 读取文件内容
        $content = stream_get_contents($file);
        fclose($file);
        
        $info['file'] = $content;
        $info['size'] = strlen($content);

        return $info;
    }

    /**
     * 列出文件
     * 
     * 获取指定目录下的文件列表，用于图片/文件管理器
     * 
     * @param string $path 目录路径
     * @param int $start 起始位置
     * @param int $size 每页数量
     * @param array $allowFiles 允许的文件扩展名
     * @return array 文件列表和分页信息
     */
    public function listFiles($path, $start = 0, $size = 20, array $allowFiles = [])
    {
        try {
            // 获取目录下所有文件
            $allFiles = $this->disk->listContents($path, true);
            
            // 分页处理
            $files = $this->paginateFiles($allFiles, $start, $size, $allowFiles);
            
            // 统计符合条件的文件总数
            $total = count($this->filterFiles($allFiles, $allowFiles));
            
            return [
                'state' => empty($files) ? 'EMPTY' : 'SUCCESS',
                'list' => $files,
                'start' => $start,
                'total' => $total,
            ];
        } catch (\Exception $e) {
            // 目录不存在或无法访问时返回空
            return [
                'state' => 'EMPTY',
                'list' => [],
                'start' => $start,
                'total' => 0,
            ];
        }
    }

    /**
     * 过滤文件列表
     * 
     * 根据允许的扩展名过滤文件
     * 
     * @param array $files 文件列表
     * @param array $allowFiles 允许的扩展名
     * @return array 过滤后的文件列表
     */
    protected function filterFiles(array $files, array $allowFiles)
    {
        if (empty($allowFiles)) {
            return array_filter($files, function ($file) {
                return $file['type'] === 'file';
            });
        }

        return array_filter($files, function ($file) use ($allowFiles) {
            if ($file['type'] !== 'file') {
                return false;
            }
            
            // 获取文件扩展名
            $ext = '.' . pathinfo($file['path'], PATHINFO_EXTENSION);
            return in_array(strtolower($ext), array_map('strtolower', $allowFiles));
        });
    }

    /**
     * 分页处理文件列表
     * 
     * @param array $files 文件列表
     * @param int $start 起始位置
     * @param int $size 每页数量
     * @param array $allowFiles 允许的扩展名
     * @return array 分页后的文件列表
     */
    protected function paginateFiles(array $files, $start = 0, $size = 50, array $allowFiles = [])
    {
        // 过滤文件
        $filteredFiles = $this->filterFiles($files, $allowFiles);
        
        // 转换并分页
        $result = [];
        $current = 0;
        
        foreach ($filteredFiles as $file) {
            if ($current >= $start && $current < $start + $size) {
                $result[] = [
                    'url' => $this->getUrl($file['path']),
                    'mtime' => isset($file['timestamp']) ? $file['timestamp'] : (isset($file['lastModified']) ? $file['lastModified'] : 0),
                ];
            }
            $current++;
        }
        
        return $result;
    }

    /**
     * 存储文件
     * 
     * 将上传的文件保存到磁盘
     * 
     * @param UploadedFile $file 上传的文件对象
     * @param string $filename 目标文件名
     * @return bool 是否存储成功
     */
    protected function store(UploadedFile $file, $filename)
    {
        // 使用文件系统的 put 方法保存文件
        return $this->disk->put($filename, fopen($file->getRealPath(), 'r+'));
    }

    /**
     * 存储文件内容
     * 
     * 直接保存文件内容（用于抓取的远程图片）
     * 
     * @param string $content 文件内容
     * @param string $filename 目标文件名
     * @return bool 是否存储成功
     */
    protected function storeContent($content, $filename)
    {
        return $this->disk->put($filename, $content);
    }

    /**
     * 验证文件
     * 
     * 检查文件是否符合上传要求
     * 
     * @param UploadedFile $file 上传的文件对象
     * @param array $config 上传配置
     * @return bool|string 验证失败返回错误信息，成功返回 false
     */
    protected function fileHasError(UploadedFile $file, array $config)
    {
        // 检查文件是否有效
        if (!$file->isValid()) {
            return $file->getError();
        }

        // 检查文件大小
        $maxSize = Arr::get($config, 'max_size', 0);
        if ($maxSize > 0 && $file->getSize() > $maxSize) {
            return 'upload.ERROR_SIZE_EXCEED';
        }

        // 检查文件类型
        $allowFiles = Arr::get($config, 'allow_files', []);
        if (!empty($allowFiles)) {
            $ext = '.' . strtolower($file->getClientOriginalExtension());
            $allowFilesLower = array_map('strtolower', $allowFiles);
            if (!in_array($ext, $allowFilesLower)) {
                return 'upload.ERROR_TYPE_NOT_ALLOWED';
            }
        }

        return false;
    }

    /**
     * 生成文件名
     * 
     * 根据配置决定是否使用哈希文件名
     * 
     * @param UploadedFile $file 上传的文件对象
     * @param array $config 上传配置
     * @return string 格式化后的完整路径
     */
    protected function getFilename(UploadedFile $file, array $config)
    {
        // 获取文件扩展名
        $ext = '.' . $file->getClientOriginalExtension();
        
        // 检查是否启用哈希文件名
        $hashFilename = UeditorPlus::getUploadConfig('hashFilename', true);
        
        if ($hashFilename) {
            // 使用 MD5 哈希作为文件名
            $originalName = md5($file->getFilename() . time()) . $ext;
        } else {
            // 使用原始文件名
            $originalName = $file->getClientOriginalName();
        }
        
        // 格式化存储路径
        return $this->formatPath($config['path_format'], $originalName);
    }

    /**
     * 获取上传配置
     * 
     * 根据 action 类型返回对应的上传配置
     * 
     * @param string $action 上传动作类型
     * @return array 上传配置
     */
    protected function getUploadConfig($action)
    {
        // 获取全部配置
        $upload = UeditorPlus::getConfig();
        
        // 定义所有 action 的前缀
        $prefixes = [
            'image',       // 图片上传
            'scrawl',      // 涂鸦上传
            'snapscreen',  // 截图上传
            'catcher',     // 远程图片抓取
            'video',       // 视频上传
            'file',        // 文件上传
            'imageManager',  // 图片管理
            'fileManager',   // 文件管理
        ];
        
        $config = [];

        // 遍历查找匹配的 action 配置
        foreach ($prefixes as $prefix) {
            $actionName = $prefix . 'ActionName';
            if ($action == $upload[$actionName]) {
                $config = [
                    'action' => Arr::get($upload, $actionName),
                    'field_name' => Arr::get($upload, $prefix . 'FieldName', 'upfile'),
                    'max_size' => Arr::get($upload, $prefix . 'MaxSize', 0),
                    'allow_files' => Arr::get($upload, $prefix . 'AllowFiles', []),
                    'path_format' => Arr::get($upload, $prefix . 'PathFormat', ''),
                ];
                break;
            }
        }

        return $config;
    }

    /**
     * 构建错误响应
     * 
     * @param string $message 错误信息键名
     * @param string|null $url 关联的 URL（可选）
     * @return array 错误结果数组
     */
    protected function errorResult($message, $url = null)
    {
        $result = [
            'state' => $message,
        ];
        
        if ($url !== null) {
            $result['url'] = $url;
            $result['source'] = $url;
        }
        
        return $result;
    }

    /**
     * 获取错误响应
     * 
     * @param string $message 错误信息
     * @return \Illuminate\Http\JsonResponse JSON 格式的错误响应
     */
    protected function error($message)
    {
        return response()->json(['state' => trans("ueditorplus::upload.{$message}")]);
    }

    /**
     * 格式化存储路径
     * 
     * 根据配置的时间格式和随机数规则替换路径变量
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
     * @param string $path 路径格式模板
     * @param string $filename 文件名
     * @return string 格式化后的完整路径
     */
    public function formatPath($path, $filename)
    {
        // 获取当前时间各部分
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $hour = date('H');
        $minute = date('i');
        $second = date('s');
        $timestamp = time();

        // 替换日期时间变量
        $path = str_replace(
            ['{yyyy}', '{yy}', '{mm}', '{dd}', '{hh}', '{ii}', '{ss}', '{filename}', '{time}'],
            [$year, substr($year, 2), $month, $day, $hour, $minute, $second, $filename, $timestamp],
            $path
        );

        // 处理随机数变量 {rand:n}
        if (preg_match('/\{rand:(\d+)\}/i', $path, $matches)) {
            $length = min((int) $matches[1], strlen(PHP_INT_MAX));
            $rand = str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
            $path = preg_replace('/\{rand:\d+\}/i', $rand, $path);
        }

        // 确保文件名包含在路径中
        if (Str::contains($path, $filename)) {
            return $path;
        }

        // 路径末尾添加斜杠并拼接文件名
        return rtrim($path, '/') . '/' . $filename;
    }

    /**
     * 获取文件 URL
     * 
     * @param string $filename 文件名/路径
     * @return string 完整的访问 URL
     */
    public function getUrl($filename)
    {
        return $this->disk->url($filename);
    }

    /**
     * 创建存储实例
     * 
     * 静态工厂方法，用于快速创建存储实例
     * 
     * @param string|null $disk 磁盘名称，为空则使用配置中的默认值
     * @return static 存储实例
     */
    public static function make($disk = null)
    {
        // 如果未指定磁盘，获取配置中的默认值
        if (!$disk) {
            $disk = UeditorPlus::getUploadConfig('disk', 'public');
        }

        // 获取磁盘实例
        if (is_string($disk)) {
            $disk = \Illuminate\Support\Facades\Storage::disk($disk);
        }

        return new static($disk);
    }
}
