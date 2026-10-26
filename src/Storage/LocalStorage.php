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
            'url' => $this->getUrl($filename, Arr::get($config, 'url_prefix', '')),
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
        $urls = $this->resolveUrls($request, $fieldName);

        // 检查是否有 URL
        if (empty($urls)) {
            return $this->error('UPLOAD_ERR_NO_FILE');
        }

        // 单次最大抓取数量限制，防止一次粘贴过多图片导致请求超时
        $limit = (int) UeditorPlus::getUploadConfig('catcherMaxCount', 50);
        if ($limit > 0 && count($urls) > $limit) {
            $urls = array_slice($urls, 0, $limit);
        }

        // 去重处理
        $urls = array_values(array_unique($urls));

        $list = [];
        foreach ($urls as $url) {
            // 下载单个图片
            $img = $this->download($url, $config);

            if ($img['state'] === 'SUCCESS') {
                // 存储图片内容，写入失败时标记为错误
                if (!$this->storeContent($img['file'], $img['filename'])) {
                    $img['state'] = 'ERROR_WRITE';
                    $img['url'] = '';
                }
            }

            // 失败项返回可读的错误信息
            if ($img['state'] !== 'SUCCESS') {
                $img['state'] = $this->transError($img['state']);
            }

            // 移除内部字段（不需要返回给前端）
            unset($img['file'], $img['filename']);
            $list[] = $img;
        }

        // 构建响应（UEditor 协议要求存在 state 字段）
        $response = [
            'state' => empty($list) ? 'ERROR' : 'SUCCESS',
            'list' => $list
        ];

        return response()->json($response);
    }

    /**
     * 从请求中解析待抓取的图片地址
     *
     * 除标准的 source / source[] 字段外，还兼容 UEditor 内置 catchremoteimage 插件
     * （UEditorPlus 前端自带该插件，字段名由 catcherFieldName 决定，未配置时不可控），
     * 因此这里额外兜底：从请求参数中找出第一个「字符串数组且元素为 http 地址」的字段。
     *
     * @param Request $request Laravel 请求对象
     * @param string $fieldName 配置的表单字段名
     * @return array
     */
    protected function resolveUrls(Request $request, $fieldName)
    {
        $urls = $request->get($fieldName);

        if (is_array($urls) && !empty($urls)) {
            return array_values(array_filter($urls, 'is_string'));
        }

        if (is_string($urls) && $urls !== '') {
            // 支持逗号分隔 / JSON 数组的传参方式
            if (strpos($urls, '[') === 0) {
                $decoded = json_decode($urls, true);
                if (is_array($decoded)) {
                    return array_values(array_filter($decoded, 'is_string'));
                }
            }

            return array($urls);
        }

        // 兜底：遍历所有请求参数，找出图片地址数组
        foreach ($request->request->all() as $key => $value) {
            if (!is_array($value) || empty($value)) {
                continue;
            }

            $first = reset($value);
            if (is_string($first) && preg_match('/^https?:\/\//i', $first)) {
                return array_values(array_filter($value, 'is_string'));
            }
        }

        return array();
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
        // 取远程抓取的专属配置项（超时、UA、Referer 等）
        $options = $this->remoteOptions();

        // 规范化 URL：去掉首尾空白
        $url = trim((string) $url);
        if ($url === '') {
            return $this->errorResult('ERROR_HTTP_LINK', $url);
        }

        // 补全协议相对地址 //host/a.jpg -> https://host/a.jpg
        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        }

        // 仅允许 HTTP(S) 协议
        if (!preg_match('/^https?:\/\//i', $url)) {
            return $this->errorResult('ERROR_HTTP_LINK', $url);
        }

        // 解析 URL
        $pathRes = parse_url($url);
        if (empty($pathRes['host'])) {
            return $this->errorResult('ERROR_HTTP_LINK', $url);
        }

        // 解析原始文件名
        $path = isset($pathRes['path']) ? $pathRes['path'] : '';
        $original = basename($path) !== '' ? basename($path) : md5($url);

        // 构建基础返回信息
        $info = array(
            'state' => 'SUCCESS',
            'url' => '',
            'title' => '',
            'original' => urldecode($original),
            'source' => $url,
            'size' => 0,
            'file' => '',
            'filename' => '',
        );

        // 请求远程资源
        $response = $this->requestUrl($url, $options);
        if (!$response['ok']) {
            // 记录失败原因到日志，方便排查（30 秒内的重复错误不会刷屏）
            $this->logFetchError($url, $response['error']);

            $info['state'] = 'ERROR_FETCH';

            return $info;
        }

        $content = $response['content'];
        $length = strlen($content);

        if ($length <= 0) {
            $info['state'] = 'ERROR_EMPTY_FILE';

            return $info;
        }

        // 大小校验
        $maxSize = (int) Arr::get($config, 'max_size', 0);
        if ($maxSize > 0 && $length > $maxSize) {
            $info['state'] = 'ERROR_SIZE_EXCEED';
            $info['size'] = $length;

            return $info;
        }

        // 内容是否为有效图片
        if (!$this->isImageContent($content)) {
            $info['state'] = 'ERROR_NOT_IMAGE';

            return $info;
        }

        // 根据 URL 与响应头推断扩展名
        $ext = $this->guessExtension($url, $response['type']);

        // 扩展名白名单校验
        $allowFiles = Arr::get($config, 'allow_files', array());
        if (!empty($allowFiles)) {
            $allowFiles = array_map('strtolower', $allowFiles);
            if (!in_array('.' . $ext, $allowFiles, true)) {
                $info['state'] = 'ERROR_TYPE_NOT_ALLOWED';

                return $info;
            }
        }

        // 生成文件名与存储路径
        $title = md5($url) . '.' . $ext;
        $pathFormat = Arr::get($config, 'path_format', 'ueditorplus/catcher/{yyyy}{mm}{dd}/{time}{rand:6}');
        $filename = $this->formatPath($pathFormat, $title);

        $info['title'] = $title;
        $info['file'] = $content;
        $info['filename'] = $filename;
        $info['size'] = $length;
        $info['url'] = $this->getUrl($filename, Arr::get($config, 'url_prefix', ''));

        return $info;
    }

    /**
     * 获取远程请求配置项
     *
     * 读取配置文件中的 catcher 相关配置，并提供安全的默认值
     *
     * @return array
     */
    protected function remoteOptions()
    {
        $defaultUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
            . '(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

        $timeout = (int) UeditorPlus::getUploadConfig('catcherTimeout', 30);
        if ($timeout <= 0) {
            $timeout = 30;
        }

        $userAgent = UeditorPlus::getUploadConfig('catcherUserAgent', $defaultUa);
        if ($userAgent === '' || $userAgent === null) {
            $userAgent = $defaultUa;
        }

        $verifySsl = (bool) UeditorPlus::getUploadConfig('catcherVerifySsl', true);

        return array(
            'timeout' => $timeout,
            'user_agent' => $userAgent,
            'verify_ssl' => $verifySsl,
            // referer 策略由 getReferer() 按具体 URL 动态计算
            'referer_policy' => UeditorPlus::getUploadConfig('catcherReferer', 'auto'),
        );
    }

    /**
     * 计算请求 Referer
     *
     * auto: 使用远程图片所在站点的根地址（多数图床/微信正文图片需要）
     * none: 不发送 Referer
     * 其他: 原样作为 Referer 发送
     *
     * @param string $url 远程图片地址
     * @param string $policy Referer 策略
     * @return string
     */
    protected function getReferer($url, $policy)
    {
        if ($policy === 'none' || $policy === '' || $policy === null) {
            return '';
        }

        if ($policy !== 'auto') {
            return (string) $policy;
        }

        $parts = parse_url($url);

        if (empty($parts['host'])) {
            return '';
        }

        return $parts['scheme'] . '://' . $parts['host'] . '/';
    }

    /**
     * 请求远程地址并返回内容
     *
     * 优先使用 curl 扩展（可携带 UA / Referer，兼容性最好），
     * 不可用时降级到 stream 方式
     *
     * @param string $url 远程地址
     * @param array $options 请求配置
     * @return array ok / content / type / error
     */
    protected function requestUrl($url, array $options)
    {
        if (function_exists('curl_init')) {
            return $this->curlGet($url, $options);
        }

        return $this->streamGet($url, $options);
    }

    /**
     * 使用 curl 请求远程资源
     *
     * @param string $url 远程地址
     * @param array $options 请求配置
     * @return array
     */
    protected function curlGet($url, array $options)
    {
        $result = array('ok' => false, 'content' => '', 'type' => '', 'error' => '');

        $timeout = Arr::get($options, 'timeout', 30);
        $verifySsl = Arr::get($options, 'verify_ssl', true) ? 1 : 0;
        $referer = $this->getReferer($url, Arr::get($options, 'referer_policy', 'auto'));

        $ch = curl_init($url);

        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERAGENT => Arr::get($options, 'user_agent', ''),
            CURLOPT_SSL_VERIFYPEER => $verifySsl,
            CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => array(
                'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            ),
        ));

        if ($referer !== '') {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }

        $content = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($content === false || $errno) {
            $result['error'] = 'curl error ' . $errno . ': ' . $error;

            return $result;
        }

        if ($status >= 400 || $status === 0) {
            $result['error'] = 'HTTP status ' . $status;

            return $result;
        }

        $result['ok'] = true;
        $result['content'] = $content;
        $result['type'] = $type;

        return $result;
    }

    /**
     * 使用 stream 方式请求远程资源（curl 不可用时的降级方案）
     *
     * @param string $url 远程地址
     * @param array $options 请求配置
     * @return array
     */
    protected function streamGet($url, array $options)
    {
        $result = array('ok' => false, 'content' => '', 'type' => '', 'error' => '');

        $timeout = Arr::get($options, 'timeout', 30);
        $verifySsl = Arr::get($options, 'verify_ssl', true);
        $referer = $this->getReferer($url, Arr::get($options, 'referer_policy', 'auto'));

        $header = 'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8' . "\r\n";
        if (!empty($options['user_agent'])) {
            $header .= 'User-Agent: ' . $options['user_agent'] . "\r\n";
        }
        if ($referer !== '') {
            $header .= 'Referer: ' . $referer . "\r\n";
        }

        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'timeout' => $timeout,
                'follow_location' => 1,
                'max_redirects' => 5,
                'ignore_errors' => true,
                'header' => $header,
            ),
            'ssl' => array(
                'verify_peer' => $verifySsl,
                'verify_peer_name' => $verifySsl,
            ),
        ));

        $fp = @fopen($url, 'r', false, $context);
        if ($fp === false) {
            $result['error'] = 'fopen failed: ' . json_encode(error_get_last());

            return $result;
        }

        $content = stream_get_contents($fp);
        $meta = stream_get_meta_data($fp);
        fclose($fp);

        if ($content === false || $content === '') {
            $result['error'] = 'empty response';

            return $result;
        }

        $type = '';
        if (isset($meta['wrapper_data']) && is_array($meta['wrapper_data'])) {
            foreach ($meta['wrapper_data'] as $h) {
                if (stripos($h, 'Content-Type:') === 0) {
                    $type = trim(substr($h, strlen('Content-Type:')));
                    break;
                }
            }
        }

        $result['ok'] = true;
        $result['content'] = $content;
        $result['type'] = $type;

        return $result;
    }

    /**
     * 判断二进制内容是否为图片
     *
     * 优先使用 getimagesizefromstring，若不支持或识别失败，
     * 再通过文件头魔数做兜底判断（兼容 webp 等格式）
     *
     * @param string $content 文件内容
     * @return bool
     */
    protected function isImageContent($content)
    {
        if (function_exists('getimagesizefromstring')) {
            $size = @getimagesizefromstring($content);
            if (is_array($size) && !empty($size[0])) {
                return true;
            }
        }

        $magic = array(
            "\xFF\xD8\xFF",          // jpg
            "\x89PNG",               // png
            'GIF8',                  // gif
            'BM',                    // bmp
            'RIFF',                  // webp
            'II',                    // tiff
            'MM',                    // tiff
            "\x00\x00\x01\x00",      // ico
        );

        foreach ($magic as $sign) {
            if (strncmp($content, $sign, strlen($sign)) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * 推断图片扩展名
     *
     * 依次从 URL 路径、query 参数（如微信正文图片 wx_fmt=jpeg）、
     * 响应 Content-Type 推断，最后默认 jpg
     *
     * @param string $url 远程地址
     * @param string $contentType 响应 Content-Type
     * @return string 小写扩展名（不含点）
     */
    protected function guessExtension($url, $contentType = '')
    {
        $parts = parse_url($url);

        // 1. 从路径中取扩展名
        if (!empty($parts['path'])) {
            $ext = strtolower(pathinfo($parts['path'], PATHINFO_EXTENSION));
            $ext = preg_replace('/[^a-z0-9].*$/i', '', $ext);
            if ($ext !== '' && strlen($ext) <= 5) {
                return $ext;
            }
        }

        // 2. 从 query 参数推断（微信正文图片：?wx_fmt=jpeg）
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            foreach (array('wx_fmt', 'format', 'imageView', 't', 'fmt') as $key) {
                if (!empty($query[$key]) && is_string($query[$key])) {
                    $ext = strtolower(preg_replace('/[^a-z0-9].*$/i', '', $query[$key]));
                    $ext = preg_replace('/^\d+$/', '', $ext);
                    if ($ext !== '' && strlen($ext) <= 5) {
                        return $ext;
                    }
                }
            }
        }

        // 3. 从 Content-Type 推断
        $ext = $this->extensionFromMime($contentType);
        if ($ext !== '') {
            return $ext;
        }

        return 'jpg';
    }

    /**
     * 根据 MIME 类型返回扩展名
     *
     * @param string $mime MIME 类型
     * @return string
     */
    protected function extensionFromMime($mime)
    {
        $mime = strtolower(trim((string) $mime));
        if ($mime === '') {
            return '';
        }

        // 去掉 charset 等附加信息
        $pos = strpos($mime, ';');
        if ($pos !== false) {
            $mime = trim(substr($mime, 0, $pos));
        }

        $map = array(
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/pjpeg' => 'jpg',
            'image/png' => 'png',
            'image/x-png' => 'png',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'image/x-ms-bmp' => 'bmp',
            'image/webp' => 'webp',
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'image/tiff' => 'tiff',
            'image/svg+xml' => 'svg',
            'image/avif' => 'avif',
        );

        return isset($map[$mime]) ? $map[$mime] : '';
    }

    /**
     * 记录远程抓取失败原因
     *
     * @param string $url 远程地址
     * @param string $error 错误信息
     * @return void
     */
    protected function logFetchError($url, $error)
    {
        if (!function_exists('app') || !class_exists('Illuminate\Support\Facades\Log')) {
            return;
        }

        \Illuminate\Support\Facades\Log::warning('[ueditorplus] 远程图片抓取失败: ' . $url . ' - ' . $error);
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
            $allFiles = $this->disk->listContents($this->normalizePath($path), true);
            
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
        // getRealPath() 在上传文件校验失败时可能返回 false，
        // 直接传给 fopen() 会抛 TypeError（PHP 8 起），这里先做保护
        $realPath = $file->getRealPath();

        if (!is_string($realPath) || $realPath === '' || !is_readable($realPath)) {
            return false;
        }

        $resource = @fopen($realPath, 'rb');

        if ($resource === false) {
            return false;
        }

        try {
            // 使用文件系统的 put 方法保存文件
            return $this->disk->put($this->normalizePath($filename), $resource);
        } finally {
            if (is_resource($resource)) {
                fclose($resource);
            }
        }
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
        return $this->disk->put($this->normalizePath($filename), $content);
    }

    /**
     * 规范化存储路径
     *
     * 配置中的路径格式常以 / 开头（如 /ueditorplus/image/...），
     * 与磁盘 root 拼接时可能产生双斜杠或越过根目录，这里统一处理
     *
     * @param string $path 原始路径
     * @return string
     */
    protected function normalizePath($path)
    {
        $path = str_replace('\\', '/', (string) $path);

        return trim(preg_replace('#/+#', '/', $path), '/');
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
            return 'ERROR_SIZE_EXCEED';
        }

        // 检查文件类型
        $allowFiles = Arr::get($config, 'allow_files', []);
        if (!empty($allowFiles)) {
            $ext = '.' . strtolower($file->getClientOriginalExtension());
            $allowFilesLower = array_map('strtolower', $allowFiles);
            if (!in_array($ext, $allowFilesLower)) {
                return 'ERROR_TYPE_NOT_ALLOWED';
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
            $actionName = Arr::get($upload, $prefix . 'ActionName');

            // 注意：必须使用 Arr::get 取值，直接使用 $upload[$actionName] 时，
            // 当配置中不存在 scrawl / snapscreen 等键（UeditorPlus::getConfig() 未返回）会触发
            // "Undefined index" 错误，在 Laravel 中会被转换成 ErrorException 导致接口 500。
            // 远程抓图（catchimage）在匹配到 scrawl 时正好会命中该问题。
            if (empty($actionName) || $action !== $actionName) {
                continue;
            }

            $config = [
                'action' => $actionName,
                'field_name' => Arr::get($upload, $prefix . 'FieldName', 'upfile'),
                'max_size' => Arr::get($upload, $prefix . 'MaxSize', 0),
                'allow_files' => Arr::get($upload, $prefix . 'AllowFiles', []),
                'path_format' => Arr::get($upload, $prefix . 'PathFormat', ''),
                'url_prefix' => Arr::get($upload, $prefix . 'UrlPrefix', ''),
            ];
            break;
        }

        if (empty($config)) {
            // 未匹配到任何 action 时返回默认配置，避免调用方出现 undefined index
            $config = [
                'action' => $action,
                'field_name' => 'upfile',
                'max_size' => 0,
                'allow_files' => [],
                'path_format' => 'ueditorplus/{yyyy}{mm}{dd}/{time}{rand:6}',
                'url_prefix' => '',
            ];
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
        return response()->json(['state' => $this->transError($message)]);
    }

    /**
     * 翻译错误信息
     * 
     * 语言包不存在对应键时返回原始错误信息，避免出现空的 state
     * 
     * @param string $message 错误信息键名
     * @return string
     */
    protected function transError($message)
    {
        // 语言包在两个命名空间下都有注册（扩展名 chisanhe.ueditorplus 与短名 ueditorplus），
        // 这里依次尝试，避免某些加载时机下拿不到翻译文本
        $keys = array(
            'ueditorplus::upload.' . $message,
            'chisanhe.ueditorplus::upload.' . $message,
        );

        foreach ($keys as $key) {
            $line = trans($key);

            if (is_string($line) && $line !== '' && $line !== $key) {
                return $line;
            }
        }

        return $message;
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
            // 上限取 18 位：pow(10, 19) 已超过 PHP_INT_MAX，会导致 mt_rand 抛 ValueError
            $length = min((int) $matches[1], 18);
            $rand = str_pad(mt_rand(0, (int) pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
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
     * @param string $prefix 可选的 URL 前缀（如 CDN 域名）
     * @return string 完整的访问 URL
     */
    public function getUrl($filename, $prefix = '')
    {
        $path = $this->normalizePath($filename);

        $url = method_exists($this->disk, 'url') ? $this->disk->url($path) : '/' . $path;

        if ($prefix === '' || $prefix === null) {
            return $url;
        }

        // 配置了 UrlPrefix（如 CDN 域名）时，只拼接 URL 中的路径部分，
        // 避免生成 "https://cdn.xxx.com/http://127.0.0.1:8000/storage/..." 这种错误地址
        $urlPath = parse_url($url, PHP_URL_PATH);

        if (!$urlPath) {
            $urlPath = '/' . $path;
        }

        return rtrim($prefix, '/') . $urlPath;
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

        // 配置中 disk 为空时使用 Dcat Admin 的默认磁盘
        if (!is_string($disk) || $disk === '') {
            $disk = 'public';
        }

        // 获取磁盘实例
        if (is_string($disk)) {
            $disk = \Illuminate\Support\Facades\Storage::disk($disk);
        }

        return new static($disk);
    }
}
