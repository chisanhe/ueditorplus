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
        // 编辑器默认高度
        'initialFrameHeight' => 400,
        // 编辑器最大字数
        'maximumWords' => 100000,
        // 服务器上传接口
        'serverUrl' => '',
        // 是否自动清除初始化内容
        'autoClearinitialContent' => false,
        // 是否自动获取焦点
        'focus' => false,
        // 是否启用自动保存
        'enableAutoSave' => true,
    ];

    /**
     * 视图模板路径
     * 
     * @var string
     */
    protected $view = 'ueditorplus::ueditor';

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
        $this->options['initialFrameHeight'] = (int) $height;

        return $this;
    }

    /**
     * 设置编辑器宽度
     * 
     * @param int $width 编辑器宽度（像素）
     * @return $this
     */
    public function width($width)
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
        $this->options['enableAutoSave'] = false;

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

        // 添加扩展配置
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
    protected function setupScript()
    {
        // 生成唯一 ID
        $id = $this->generateId();
        
        // 设置元素属性
        $this->attribute('id', $id);
        $this->addVariables(['id' => $id]);
        
        // 格式化配置
        $opts = $this->formatOptions();

        // 获取资源路径
        $assetPath = Admin::asset()->package('chisanhe/ueditorplus');

        // 获取 CSRF 令牌
        $csrfToken = csrf_token();

        // 生成初始化脚本
        $this->script = <<<JS
(function () {
    // 获取已存储的内容
    var storedValue = $('#{$id}-value').val();
    
    // 检查是否已存在编辑器实例
    if (UE.getEditor('{$id}')) {
        UE.getEditor('{$id}').destroy();
    }
    
    // 创建新的编辑器实例
    var ue = UE.getEditor('{$id}', {$opts});
    
    // 编辑器准备就绪后
    ue.ready(function() {
        // 设置初始内容
        if (storedValue) {
            ue.setContent(storedValue);
        }
        
        // 设置 CSRF 令牌
        ue.execCommand('serverparam', '_token', '{$csrfToken}');
        
        // 设置磁盘配置
        ue.execCommand('serverparam', 'disk', '{$this->disk}');
        
        // 初始化 135 编辑器
        if (ue.ui && ue.ui.isEditor135Enabled && ue.ui.isEditor135Enabled()) {
            ue.ui.init135Editor();
        }
        
        // 初始化秀米编辑器
        if (ue.ui && ue.ui.isXiumiEnabled && ue.ui.isXiumiEnabled()) {
            ue.ui.initXiumi();
        }
    });
    
    // 监听内容变化，同步到隐藏字段
    ue.addListener('contentChange', function() {
        $('#{$id}-value').val(ue.getContent());
    });
    
    // 表单提交前同步内容
    ue.addListener('blur', function() {
        $('#{$id}-value').val(ue.getContent());
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
        return 'ueditorplus-' . Str::random(8);
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
            'homeUrl' => admin_asset('@chisanhe/ueditorplus') . '/',
        ]);
    }

    /**
     * 渲染表单字段
     * 
     * @return string
     */
    public function render()
    {
        // 加载必要的 CSS 和 JS 资源
        Admin::requireAssets([
            '@chisanhe.ueditorplus' => [
                // ueditorplus 核心文件
                'ueditor.config.js',
                'ueditor.all.js',
                'ueditor.parse.js',
            ],
        ]);

        // 设置磁盘配置
        $this->setDisk();

        // 设置脚本
        $this->setupScript();

        // 调用父类渲染方法
        return parent::render();
    }
}
