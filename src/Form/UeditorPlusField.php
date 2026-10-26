<?php

/**
 * UEditor Plus 表单字段类
 * 
 * Dcat Admin 表单字段，用于集成 UEditor Plus 富文本编辑器
 * 支持自定义高度、上传接口、135编辑器、秀米等配置
 * 
 * @package Chisanhe\Ueditorplus\Form
 * @author  chisanhe
 * @since   1.0.0
 */
namespace Chisanhe\Ueditorplus\Form;

use Dcat\Admin\Admin;
use Dcat\Admin\Form\Field;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * UEditor Plus 表单字段类
 * 
 * 继承自 Dcat Admin 的 Field 类
 * 用于在 Dcat Admin 表单中集成 UEditor Plus 编辑器
 */
class UeditorPlusField extends Field
{
    /**
     * 编辑器默认配置选项
     * 
     * @var array
     */
    protected $options = [
        // 编辑器最大字数
        'maximumWords' => 100000,
        // 服务器上传接口
        'serverUrl' => '',
        // 是否自动清除初始化内容
        'autoClearinitialContent' => false,
        // 是否自动获取焦点
        'focus' => false,
        // 是否启用自动保存
        'autoSaveEnable' => false,
    ];

    /**
     * 视图模板路径
     *
     * 命名空间必须与 ServiceProvider::loadViewsFrom 注册的命名空间保持一致，
     * 否则会报 "No hint path defined for [...]"。
     *
     * @var string
     */
    protected $view = 'chisanhe.ueditorplus::ueditor';

    /**
     * 当前使用的磁盘存储配置
     * 
     * @var string
     */
    protected $disk;

    /**
     * 是否启用 135 编辑器
     * 
     * @var bool
     */
    protected $enable135Editor = false;

    /**
     * 是否启用秀米编辑器
     * 
     * @var bool
     */
    protected $enableXiumiEditor = false;

    /**
     * 135 编辑器配置
     * 
     * @var array
     */
    protected $editor135Config = [];

    /**
     * 秀米编辑器配置
     * 
     * @var array
     */
    protected $xiumiEditorConfig = [];

    /**
     * 设置编辑器高度
     * 
     * @param int $height 编辑器高度（像素）
     * @return $this
     */
    public function height($height)
    {
        $height = (int) $height;

        // 仅记录期望高度，实际生效高度在 setupScript() 中统一计算：
        // ->height() > config('ueditorplus.initialFrameHeight') > 400
        $this->options['initialFrameHeight'] = $height;

        return $this;
    }

    /**
     * 设置编辑器宽度（像素）
     *
     * 注意：父类 Dcat\Admin\Form\Field::width($field, $label) 用于设置 Bootstrap 网格布局，
     * 不要覆盖它。设置编辑器像素宽度请使用本方法。
     *
     * @param int $width 编辑器宽度（像素）
     * @return $this
     */
    public function editorWidth($width)
    {
        $this->options['initialFrameWidth'] = (int) $width;

        return $this;
    }

    /**
     * 设置上传接口地址
     * 
     * @param string $url 上传接口 URL
     * @return $this
     */
    public function server($url)
    {
        // 判断是否为绝对 URL
        if (Str::startsWith($url, ['http://', 'https://'])) {
            $this->options['serverUrl'] = $url;
        } else {
            // 使用 Dcat Admin 的路径处理
            $this->options['serverUrl'] = admin_url($url);
        }
        // 仅在需要时启用从服务器加载配置
        // 例如：需要后端动态返回 toolbars 配置等场景
        // 默认关闭，避免服务器配置覆盖 PHP 端的 initialFrameHeight 等设置
        // $this->options['loadConfigFromServer'] = true;

        return $this;
    }

    /**
     * 设置使用的存储磁盘
     * 
     * @param string $disk 磁盘名称
     * @return $this
     */
    public function disk($disk)
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * 设置工具栏配置
     * 
     * @param array|string $toolbars 工具栏配置
     * @return $this
     */
    public function toolbars($toolbars)
    {
        $this->options['toolbars'] = $toolbars;

        return $this;
    }

    /**
     * 启用自动保存
     * 
     * @return $this
     */
    public function enableAutoSave()
    {
        $this->options['autoSaveEnable']  = true;
        $this->options['enableAutoSave'] = true;

        return $this;
    }

    /**
     * 禁用自动保存
     *
     * @return $this
     */
    public function disableAutoSave()
    {
        $this->options['autoSaveEnable']  = false;
        $this->options['enableAutoSave'] = false;

        return $this;
    }

    /**
     * 启用远程图片自动抓取
     *
     * 粘贴内容时自动检测并下载外部图片到本地
     *
     * @return $this
     */
    public function enableRemoteImageFetch()
    {
        $this->options['remoteImageEnable'] = true;

        return $this;
    }

    /**
     * 禁用远程图片自动抓取
     *
     * @return $this
     */
    public function disableRemoteImageFetch()
    {
        $this->options['remoteImageEnable'] = false;

        return $this;
    }

    /**
     * 启用 135 编辑器
     *
     * 135 编辑器是一款强大的在线排版编辑器
     * 
     * @param array $config 135编辑器配置
     * @return $this
     */
    public function enable135Editor($config = [])
    {
        $this->enable135Editor = true;
        $this->editor135Config = array_merge([
            'enable' => true,
        ], $config);
        
        // 确保 135 编辑器按钮在工具栏中
        $this->addToolbarButton('135editor');
        
        return $this;
    }

    /**
     * 禁用 135 编辑器
     * 
     * @return $this
     */
    public function disable135Editor()
    {
        $this->enable135Editor = false;
        $this->editor135Config = [];

        return $this;
    }

    /**
     * 启用秀米编辑器
     * 
     * 秀米编辑器是一款简洁美观的在线图文编辑工具
     * 
     * @param array $config 秀米编辑器配置
     * @return $this
     */
    public function enableXiumiEditor($config = [])
    {
        $this->enableXiumiEditor = true;
        $this->xiumiEditorConfig = array_merge([
            'enable' => true,
            'biz' => '',      // 秀米 business ID
            'template' => '', // 默认模板
        ], $config);
        
        // 确保秀米编辑器按钮在工具栏中
        $this->addToolbarButton('xiumi');
        
        return $this;
    }

    /**
     * 禁用秀米编辑器
     * 
     * @return $this
     */
    public function disableXiumiEditor()
    {
        $this->enableXiumiEditor = false;
        $this->xiumiEditorConfig = [];

        return $this;
    }

    /**
     * 添加工具栏按钮
     * 
     * 确保指定按钮在工具栏中显示
     * 
     * @param string $button 按钮名称
     * @return $this
     */
    protected function addToolbarButton($button)
    {
        if (!isset($this->options['toolbars'])) {
            return $this;
        }

        $toolbars = $this->options['toolbars'];
        
        // 如果是二维数组
        if (is_array($toolbars) && isset($toolbars[0]) && is_array($toolbars[0])) {
            foreach ($toolbars as &$row) {
                if (!in_array($button, $row)) {
                    $row[] = $button;
                }
            }
        } elseif (is_array($toolbars) && !in_array($button, $toolbars)) {
            $toolbars[] = $button;
            $this->options['toolbars'] = $toolbars;
        }

        return $this;
    }

    /**
     * 设置 AI 配置
     * 
     * @param array $config AI 配置
     * @return $this
     */
    public function aiConfig($config)
    {
        $this->options['aiConfig'] = $config;

        return $this;
    }

    /**
     * 启用字数统计
     * 
     * @param bool $enable 是否启用
     * @param int $maxWords 最大字数
     * @return $this
     */
    public function wordCount($enable = true, $maxWords = 100000)
    {
        $this->options['wordCount'] = $enable;
        $this->options['maximumWords'] = $maxWords;

        return $this;
    }

    /**
     * 格式化配置选项为 JSON
     * 
     * @return string JSON 编码的配置字符串
     */
    protected function formatOptions()
    {
        // 如果未设置上传接口，使用默认的 serve 接口
        if (empty($this->options['serverUrl'])) {
            $this->server('ueditorplus/serve');
        }

        // 关闭 UEditorPlus 内置的 catchremoteimage 插件：
        // 1) 它会与本扩展的批量抓取逻辑重复请求同一张图片；
        // 2) 内置插件依赖 catcherFieldName / catcherActionName 等配置，
        //    缺失时会把图片 src 替换成 loading 占位图后无法恢复。
        // 这里统一交给本扩展的抓图逻辑处理，避免冲突。
        $this->options['catchRemoteImageEnable'] = false;

        // 注入扩展配置（135编辑器、秀米编辑器等）
        // 注意：loadConfigFromServer 由 server() 方法设置，默认为 false
        // 如果需要从服务器加载配置（如后端返回的 toolbars 动态配置），显式调用 server() 方法
        if ($this->enable135Editor) {
            $this->options['editor135Config'] = $this->editor135Config;
        }
        
        if ($this->enableXiumiEditor) {
            $this->options['xiumiEditorConfig'] = $this->xiumiEditorConfig;
        }

        return json_encode($this->options);
    }

    /**
     * 设置磁盘存储
     * 
     * 从配置中获取磁盘配置
     */
    protected function setDisk()
    {
        // 优先使用通过 disk() 方法设置的磁盘
        if ($this->disk) {
            return;
        }

        // 从扩展配置中获取磁盘配置
        $disk = \Chisanhe\Ueditorplus\UeditorPlus::getUploadConfig('disk');
        
        // 如果扩展未配置，从 Dcat Admin 获取
        if (empty($disk)) {
            $disk = config('admin.upload.disk', 'public');
        }

        $this->disk = $disk;
    }

    /**
     * 设置编辑器脚本
     * 
     * 生成初始化编辑器的 JavaScript 代码
     */
    /**
     * 计算本地域名白名单
     *
     * 这些域名下的图片视为「已是本地图片」，不会被重复抓取。
     * 除配置中的 catcherLocalDomain 外，还会自动加入：
     * - 当前访问域名
     * - config('app.url') 的域名（保存图片时拼接的域名）
     * - 当前磁盘 URL 的域名（磁盘 url 与 APP_URL 不一致时，很容易被误判成外链）
     * - 各类 UrlPrefix（CDN）的域名
     *
     * @param string $disk 磁盘名称
     * @return array
     */
    protected function localDomains($disk)
    {
        $domains = (array) \Chisanhe\Ueditorplus\UeditorPlus::getUploadConfig(
            'catcherLocalDomain',
            array('127.0.0.1', 'localhost')
        );

        // 当前访问域名
        try {
            if (function_exists('request')) {
                $domains[] = request()->getHost();
            }
        } catch (\Exception $e) {
            // 忽略，仅降级为不含当前域名
        }

        // 站点配置域名与 CDN 前缀域名
        $candidates = array((string) config('app.url', ''));

        foreach (array('imageUrlPrefix', 'videoUrlPrefix', 'fileUrlPrefix', 'catcherUrlPrefix') as $key) {
            $candidates[] = (string) \Chisanhe\Ueditorplus\UeditorPlus::getUploadConfig($key, '');
        }

        // 当前磁盘生成的 URL（如 http://localhost/storage）
        try {
            $candidates[] = \Chisanhe\Ueditorplus\Storage\LocalStorage::make($disk)->getUrl('');
        } catch (\Exception $e) {
            // 磁盘不可用时忽略
        }

        foreach ($candidates as $url) {
            if ($url === '') {
                continue;
            }

            $host = parse_url($url, PHP_URL_HOST);

            if (!empty($host)) {
                $domains[] = $host;
            }
        }

        $domains = array_map(function ($domain) {
            return is_string($domain) ? strtolower(trim($domain)) : '';
        }, $domains);

        $domains = array_values(array_unique(array_filter($domains)));

        return $domains;
    }

    protected function setupScript()
    {
        // 生成唯一 ID
        $id = $this->generateId();
        
        // 设置元素属性
        $this->attribute('id', $id);
        $this->addVariables(['id' => $id]);

        // 强制关闭自动高度，确保 initialFrameHeight 生效（在 formatOptions 之前设置）
        $this->options['autoHeightEnabled'] = false;

        // 编辑器高度取值优先级：->height() 显式指定 > 配置文件 > 默认 400
        // 注意：类属性 $options 中不预置 initialFrameHeight，
        // 这样才能区分“用户显式调用了 ->height()”与“使用配置文件的默认值”
        $height = (int) ($this->options['initialFrameHeight'] ?? config('ueditorplus.initialFrameHeight', 400));
        if ($height <= 0) {
            $height = 400;
        }
        $this->options['initialFrameHeight'] = $height;
        $this->options['minFrameHeight'] = $height;

        // 格式化配置
        $opts = $this->formatOptions();

        // 获取 CSRF 令牌
        $csrfToken = csrf_token();

        // 提前设置 serverUrl，避免 UEditor 在 serverUrl 为空时用当前页面 URL
        $serverUrl = $this->options['serverUrl'] ?? '';
        $disk = $this->disk;

        // 是否启用远程图片自动抓取（从配置文件读取）
        $remoteImageEnable = Arr::get(config('ueditorplus', []), 'catchRemoteImageEnable', true)
            && ($this->options['remoteImageEnable'] ?? true);

        // 是否从服务器加载配置
        $loadConfigFromServer = $this->options['loadConfigFromServer'] ?? false;

        // 关键修复：通过 json_encode 直接将原始 HTML 传给 JS 变量，
        // 绕过 hidden input HTML 属性的 < 字符编码问题（浏览器会自动将属性中的 < 编码为 &lt;）
        $contentJson = json_encode((string) $this->value(), JSON_UNESCAPED_UNICODE);

        // 将 PHP 值转为 JS 字面量（供 heredoc 直接插入，避免 {表达式} 语法冲突）
        $remoteImageEnableJS = $remoteImageEnable ? 'true' : 'false';
        $serverUrlJS = json_encode($serverUrl);
        $loadConfigFromServerJS = $loadConfigFromServer ? 'true' : 'false';
        $diskJS = json_encode((string) $disk);
        $csrfTokenJS = json_encode($csrfToken);
        $heightJS = json_encode($height);

        // 本地域名白名单：这些域名下的图片不会被抓取
        $localDomainsJS = json_encode($this->localDomains($disk));

        // 生成初始化脚本（heredoc 直接赋值，所有 PHP 变量用拼接注入）
        $this->script = <<<JS
(function () {
    var storedValue = {$contentJson};
    var remoteImageEnable = {$remoteImageEnableJS};
    var localDomains = {$localDomainsJS};
    if (typeof UEEDITOR_CONFIG !== 'undefined') {
        UEEDITOR_CONFIG.serverUrl = {$serverUrlJS};
        UEEDITOR_CONFIG.loadConfigFromServer = {$loadConfigFromServerJS};
        // 双重保险：UEditor 的 autoheight 插件在 ready 时会启用，
        // 按正文内容重新计算高度（body.style.height / iframe 容器高度），
        // 把编辑器压扁。这里在全局配置里也关掉，
        // 避免实例选项缺失时插件被意外启用。
        UEDITOR_CONFIG.autoHeightEnabled = false;
        // 内置 catchremoteimage 插件与本扩展的抓图逻辑重复，统一关闭
        UEDITOR_CONFIG.catchRemoteImageEnable = false;
    }
    var fetchTimer_{$id} = null;
    // 本次页面已尝试抓取过的图片地址：避免同一张图被反复抓取、反复弹提示
    var triedUrls = {};
    function showToast(message, type) {
        type = type || 'success';
        if (typeof Dcat !== 'undefined' && Dcat.notify) {
            if (type === 'success') { Dcat.notify.success(message); }
            else if (type === 'error') { Dcat.notify.error(message); }
            else { Dcat.notify(message); }
            return;
        }
        if (typeof toastr !== 'undefined') {
            if (type === 'success') { toastr.success(message); }
            else if (type === 'error') { toastr.error(message); }
            else { toastr.info(message); }
            return;
        }
        if (type !== 'success') { alert(message); }
    }
    function showProgress() {
        if (typeof Dcat !== 'undefined' && Dcat.NP && Dcat.NP.start) { Dcat.NP.start(); }
    }
    function hideProgress() {
        if (typeof Dcat !== 'undefined' && Dcat.NP && Dcat.NP.done) { Dcat.NP.done(); }
    }
    function getCsrfToken() {
        var token = '';
        try {
            var meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) { token = meta.getAttribute('content') || ''; }
        } catch (e1) {}
        if (!token) {
            try {
                var input = document.querySelector('input[name=_token]');
                if (input) { token = input.value || ''; }
            } catch (e2) {}
        }
        if (!token && typeof Dcat !== 'undefined' && Dcat.token) { token = Dcat.token; }
        if (!token && typeof LA !== 'undefined' && LA.token) { token = LA.token; }
        // 最后兜底：使用服务端渲染时注入的 token
        if (!token) { token = {$csrfTokenJS}; }
        return token || '';
    }
    function isRemoteSrc(src) {
        if (!src || src.indexOf('http') !== 0) { return false; }
        var host = '';
        var hostname = '';
        try {
            var a = document.createElement('a');
            a.href = src;
            host = a.host || '';
            hostname = a.hostname || '';
        } catch (e) {
            return src.indexOf(window.location.host) === -1;
        }
        // 与当前站点同域名（忽略端口差异）时不抓取
        if (!host || host === window.location.host || hostname === window.location.hostname) { return false; }
        // 命中本地域名白名单（当前站点、APP_URL、磁盘 URL、CDN 等）时不抓取，
        // 避免保存时使用的域名与后台访问域名不一致导致重复抓取
        if (localDomains.indexOf(hostname) !== -1) { return false; }
        // 兼容子域名，如 img.example.com 命中 example.com
        for (var i = 0; i < localDomains.length; i++) {
            var d = String(localDomains[i] || '').toLowerCase();
            if (!d) { continue; }
            // 精确匹配，或匹配其子域名（如 img.baidu.com 命中 baidu.com）
            if (hostname === d || hostname.lastIndexOf('.' + d) === hostname.length - d.length - 1) {
                return false;
            }
        }
        return true;
    }
    function fetchAndReplaceRemoteImages(editor, action) {
        var body = editor.body || editor.document.body;
        if (!body) return;
        var seen = {};
        var remoteSrcs = [];
        var imgs = body.querySelectorAll('img');
        for (var i = 0; i < imgs.length; i++) {
            var src = imgs[i].src || imgs[i].getAttribute('src') || '';
            // 已是本地图片、本次已抓取过的图片都不再处理：没有外链时不会发起请求、不会弹提示
            if (isRemoteSrc(src) && !seen[src] && !triedUrls[src]) {
                seen[src] = true;
                remoteSrcs.push({ el: imgs[i], src: src });
            }
        }
        if (remoteSrcs.length === 0) return;
        var total = remoteSrcs.length;
        var fetched = 0;
        var failed = 0;
        var serverUrl = editor.getOpt('serverUrl') || '';
        if (!serverUrl) return;
        showToast('\u68c0\u6d4b\u5230 ' + total + ' \u5f20\u5916\u90e8\u56fe\u7247\u6b63\u5728\u62d3\u53d6\u672c\u5730\u5316...', 'info');
        showProgress();
        var csrfToken = getCsrfToken();
        var disk = {$diskJS};
        var xhr = new XMLHttpRequest();
        var separator = serverUrl.indexOf('?') === -1 ? '?' : '&';
        xhr.open('POST', serverUrl + separator + 'action=catchimage&disk=' + encodeURIComponent(disk), true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        if (csrfToken) { xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken); }
        var fd = new FormData();
        // 同时放入表单字段，兼容只读取 _token 输入的场景（如自定义上传接口）
        if (csrfToken) { fd.append('_token', csrfToken); }
        for (var j = 0; j < remoteSrcs.length; j++) {
            fd.append('source[]', remoteSrcs[j].src);
            // 标记已尝试（抓取失败也不再重复请求，避免反复弹提示）
            triedUrls[remoteSrcs[j].src] = true;
        }
        xhr.onload = function () {
            hideProgress();
            if (xhr.status === 200) {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.state === 'SUCCESS' && res.list && res.list.length) {
                        for (var k = 0; k < res.list.length; k++) {
                            var item = res.list[k];
                            if (item.state === 'SUCCESS' && item.url && item.source) {
                                for (var m = 0; m < remoteSrcs.length; m++) {
                                    if (remoteSrcs[m].src === item.source) {
                                        remoteSrcs[m].el.src = item.url;
                                        // UEditor 用 _src 记录原始地址，保持同步避免再次抓取
                                        remoteSrcs[m].el.setAttribute('_src', item.url);
                                        remoteSrcs[m].el.setAttribute('data-catch-result', '1');
                                        fetched++;
                                        break;
                                    }
                                }
                            } else { failed++; }
                        }
                    } else { failed = total; }
                } catch (e) { failed = total; }
            } else if (xhr.status === 419) {
                // CSRF token 失效或缺失
                showToast('\u9875\u9762\u5df2\u8fc7\u671f\uff08CSRF token \u5931\u6548\uff09\uff0c\u8bf7\u5237\u65b0\u9875\u9762\u540e\u91cd\u8bd5', 'error');
                return;
            } else { failed = total; }
            if (fetched > 0) {
                // 关键：把本地化后的内容同步回隐藏域，否则提交表单时保存的还是外链地址
                try { jQuery('#{$id}-value').val(editor.getContent()); } catch (e3) {}
            }
            if (fetched === total) { showToast('\u8fdc\u7a0b\u56fe\u7247\u62d3\u53d6\u5b8c\u6210\u5171 ' + total + ' \u5f20', 'success'); }
            else if (fetched > 0) { showToast('\u8fdc\u7a0b\u56fe\u7247\u62d3\u53d6\u5b8c\u6210\uff1a' + fetched + ' \u5f20\u6210\u529f\uff0c' + failed + ' \u5f20\u5931\u8d25', 'error'); }
            else { showToast('\u8fdc\u7a0b\u56fe\u7247\u62d3\u53d6\u5931\u8d25\uff0c\u8bf7\u68c0\u67e5\u670d\u52a1\u5668\u914d\u7f6e', 'error'); }
        };
        xhr.onerror = function () {
            hideProgress();
            showToast('\u8fdc\u7a0b\u56fe\u7247\u62d3\u53d6\u8bf7\u6c42\u5931\u8d25', 'error');
        };
        xhr.send(fd);
    }
    // 编辑器高度：以服务端算好的值为准
    // UEditor 在 initialFrameHeight 缺失时，render() 会回退成容器的自然高度
    // （实测约 136~215px），所以这里直接写死并同步到实例选项
    var frameHeight = {$heightJS};
    var ue = UE.getEditor('{$id}', {$opts});
    // 双保险：setOpt 无法覆盖已存在的键，这里直接写入选项
    if (ue.options) {
        ue.options.initialFrameHeight = frameHeight;
        ue.options.minFrameHeight = frameHeight;
        ue.options.autoHeightEnabled = false;
    }
    ue.ready(function() {
        // 固定编辑器高度（setHeight 会直接改写 options.initialFrameHeight）
        if (ue.disableAutoHeight) { ue.disableAutoHeight(); }
        if (ue.setHeight) { ue.setHeight(frameHeight); }
        if (ue.body && ue.body.parentNode) {
            ue.body.parentNode.style.height = frameHeight + 'px';
        }
        if (storedValue) {
            ue.setContent(storedValue);
            jQuery('#{$id}-value').val(storedValue);
            // setContent 会触发 contentchange，这里再固定一次高度
            if (ue.setHeight) { ue.setHeight(frameHeight); }
            // 初始内容加载后立即检查并抓取远程图片
            if (remoteImageEnable) {
                fetchAndReplaceRemoteImages(ue, 'init');
            }
        }
        ue.execCommand('serverparam', '_token', '{$csrfToken}');
        ue.execCommand('serverparam', 'disk', '{$disk}');
        ue.addListener('contentchange', function() {
            jQuery('#{$id}-value').val(ue.getContent());
            if (!remoteImageEnable) return;
            clearTimeout(fetchTimer_{$id});
            fetchTimer_{$id} = setTimeout(function() { fetchAndReplaceRemoteImages(ue, 'paste'); }, 1000);
        });
        ue.addListener('blur', function() {
            jQuery('#{$id}-value').val(ue.getContent());
        });
    });
})();
JS;
    }

    /**
     * 生成唯一 ID
     * 
     * @return string
     */
    protected function generateId()
    {
        return 'ueditorplus_' . Str::random(8);
    }

    /**
     * 获取视图变量
     * 
     * 返回传递给视图的变量数组
     * 
     * @return array
     */
    public function variables()
    {
        return array_merge(parent::variables(), [
            // 资源文件基础路径
            'homeUrl' => admin_asset('vendor/chisanhe/ueditorplus') . '/',
            // serverUrl 用于覆盖 UEditor 默认配置
            'serverUrl' => $this->options['serverUrl'] ?? '',
            // 内容用于 data 属性（htmlspecialchars + ENT_QUOTES 避免 HTML 解析问题）
            'contentDataAttr' => htmlspecialchars(
                json_encode((string) $this->value(), JSON_UNESCAPED_UNICODE),
                ENT_QUOTES,
                'UTF-8'
            ),
        ]);
    }

    /**
     * 渲染表单字段
     *
     * @return string
     */
    public function render()
    {
        // 资源路径
        $base = admin_asset('vendor/chisanhe/ueditorplus');

        // 设置 UEDITOR_HOME_URL（direct 同步执行，优先于所有 JS 文件）
        Admin::script("window.UEDITOR_HOME_URL = '$base/';", true);

        // 加载 ueditor 三件套
        Admin::js([
            $base . '/ueditor.config.js',
            $base . '/ueditor.all.js',
            $base . '/ueditor.parse.js',
        ]);

        // 加载 ueditor 插件（135编辑器、秀米等必须在 ueditor.all.js 之后加载）
        Admin::js([
            $base . '/plugins/demo/demo.js',
        ]);

        // 135编辑器按钮和秀米按钮的图标样式
        Admin::style(".edui-button.edui-for-135editor .edui-button-wrap .edui-button-body .edui-icon{background-image:url(https://static.135editor.com/img/icons/editor-135-icon.png)!important;background-size:85%;background-position:center;background-repeat:no-repeat}.edui-button.edui-for-xiumi-connect .edui-button-wrap .edui-button-body .edui-icon{background-image:url({$base}/themes/default/images/xiumi-connect-icon.png)!important;background-size:contain}");

        // 设置磁盘配置
        $this->setDisk();

        // 设置脚本
        $this->setupScript();

        // 强制使用内置视图模板（视图名格式：namespace::view，namespace = $this->getName() = 'chisanhe.ueditorplus'）
        $this->view = 'chisanhe.ueditorplus::ueditor';

        // 调用父类渲染方法
        return parent::render();
    }
}
