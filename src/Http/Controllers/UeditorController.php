<?php

/**
 * UEditor Plus 控制器
 * 
 * 处理 UEditor Plus 富文本编辑器的所有后端请求
 * 包括：配置获取、文件上传、远程图片抓取、文件列表等
 * 
 * @package Chisanhe\Ueditorplus\Http\Controllers
 * @author  chisanhe
 * @since   1.0.0
 */
namespace Chisanhe\Ueditorplus\Http\Controllers;

use Chisanhe\Ueditorplus\Storage\LocalStorage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * UEditor Plus 控制器
 * 
 * 统一处理 UEditor 的所有 action 请求
 * 支持的图片/视频/文件上传，远程图片抓取，文件列表等功能
 */
class UeditorController extends Controller
{
    /**
     * 存储实例
     * 
     * @var LocalStorage
     */
    protected $storage;

    /**
     * 处理 UEditor 请求的主入口
     * 
     * 根据 action 参数分发到不同的处理方法
     * 支持的动作：
     * - config: 获取编辑器配置
     * - uploadimage: 上传图片
     * - uploadvideo: 上传视频
     * - uploadfile: 上传文件
     * - catchimage: 抓取远程图片
     * - listimage: 获取图片列表
     * - listfile: 获取文件列表
     * 
     * @param Request $request Laravel 请求对象
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function serve(Request $request)
    {
        // 获取当前请求的 action
        $action = $request->get('action');

        // 根据 action 分发处理
        switch ($action) {
            // 返回编辑器配置
            case 'config':
                return $this->config();

            // 上传图片
            case 'uploadimage':
            // 上传视频
            case 'uploadvideo':
            // 上传文件
            case 'uploadfile':
                return $this->storage($request)->upload($request);

            // 抓取远程图片
            case 'catchimage':
                return $this->storage($request)->fetch($request);

            // 获取图片列表
            case 'listimage':
                return $this->listImage($request);

            // 获取文件列表
            case 'listfile':
                return $this->listFile($request);

            // 未知 action，返回配置
            default:
                return $this->config();
        }
    }

    /**
     * 返回编辑器配置
     * 
     * 返回 UEditor 前端初始化所需的配置信息
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    protected function config()
    {
        // 使用 UeditorPlus::getConfig() 获取完整配置
        $config = \Chisanhe\Ueditorplus\UeditorPlus::getConfig();
        
        // 返回 JSON 配置
        return response()->json($config);
    }

    /**
     * 获取图片列表
     * 
     * 用于图片管理器功能
     * 
     * @param Request $request Laravel 请求对象
     * @return \Illuminate\Http\JsonResponse
     */
    protected function listImage(Request $request)
    {
        // 获取配置
        $config = \Chisanhe\Ueditorplus\UeditorPlus::getConfig();
        
        // 获取分页参数
        $start = $request->get('start', 0);
        $size = $request->get('size', $config['imageManagerListSize']);
        
        // 获取文件列表
        return response()->json(
            $this->storage($request)->listFiles(
                $config['imageManagerListPath'],
                $start,
                $size,
                $config['imageManagerAllowFiles']
            )
        );
    }

    /**
     * 获取文件列表
     * 
     * 用于文件管理器功能
     * 
     * @param Request $request Laravel 请求对象
     * @return \Illuminate\Http\JsonResponse
     */
    protected function listFile(Request $request)
    {
        // 获取配置
        $config = \Chisanhe\Ueditorplus\UeditorPlus::getConfig();
        
        // 获取分页参数
        $start = $request->get('start', 0);
        $size = $request->get('size', $config['fileManagerListSize']);
        
        // 获取文件列表
        return response()->json(
            $this->storage($request)->listFiles(
                $config['fileManagerListPath'],
                $start,
                $size,
                $config['fileManagerAllowFiles']
            )
        );
    }

    /**
     * 获取存储实例
     * 
     * 延迟加载存储实例
     * 
     * @return LocalStorage
     */
    protected function storage(Request $request = null)
    {
        if ($this->storage === null) {
            // 获取磁盘配置，优先使用扩展配置，否则使用 Dcat Admin 配置
            $disk = \Chisanhe\Ueditorplus\UeditorPlus::getUploadConfig('disk');
            
            // 如果未配置磁盘，尝试从 Dcat Admin 获取
            if (empty($disk)) {
                $disk = config('admin.upload.disk', 'public');
            }

            // 允许请求通过 disk 参数覆盖（前端 serverparam 会带上当前磁盘），
            // 仅接受真实存在的磁盘，避免被随意切换
            if ($request) {
                $requested = $request->get('disk');
                if (is_string($requested) && $requested !== '' && config('filesystems.disks.' . $requested)) {
                    $disk = $requested;
                }
            }

            if (empty($disk) || !config('filesystems.disks.' . $disk)) {
                $disk = 'public';
            }
            
            // 创建存储实例
            $this->storage = LocalStorage::make($disk);
        }
        
        return $this->storage;
    }
}
