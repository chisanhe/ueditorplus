# chisanhe/ueditorplus

Dcat Admin UeditorPlus 富文本编辑器扩展，基于 [ueditorplus](https://github.com/mo3408/ueditorplus) 开发。

> 原项目：https://github.com/mo3408/ueditorplus
> 参考：https://github.com/weiaibaicai/ueditor

## 环境要求

- PHP >=7.0 && <=8.3
- Laravel >=5.8 ~ 11.x
- Dcat Admin >=2.0

## 安装

```bash
composer require chisanhe/ueditorplus
```

## 配置

### 1. 发布资源文件

```bash
php artisan vendor:publish --provider="Chisanhe\Ueditorplus\ChisanheUeditorplusServiceProvider" --tag=chisanhe-ueditorplus-assets
```

### 2. 发布配置文件

```bash
php artisan vendor:publish --provider="Chisanhe\Ueditorplus\ChisanheUeditorplusServiceProvider" --tag=chisanhe-ueditorplus-config
```

### 3. 配置说明

发布配置后，可以在 `config/ueditorplus.php` 中修改以下配置：

```php
return [
    // 存储磁盘配置，默认使用 Dcat Admin 的上传磁盘
    'disk' => 'public',

    // 是否使用哈希文件名
    'hashFilename' => true,

    // 图片上传配置
    'imageActionName' => 'uploadimage',
    'imageFieldName' => 'upfile',
    'imageMaxSize' => 2048000, // 2MB
    'imageAllowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp'],
    'imagePathFormat' => '/ueditorplus/image/{yyyy}{mm}{dd}/{time}{rand:6}',

    // 视频上传配置
    'videoActionName' => 'uploadvideo',
    'videoMaxSize' => 102400000, // 100MB
    'videoAllowFiles' => ['.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb', '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv', '.mp4', '.webm', '.mp3', '.wav', '.mid'],

    // 文件上传配置
    'fileActionName' => 'uploadfile',
    'fileMaxSize' => 51200000, // 50MB

    // 135编辑器配置
    'editor135' => [
        'enable' => true,
        'apiUrl' => '',
        'key' => '',
    ],

    // 秀米编辑器配置
    'xiumi' => [
        'enable' => true,
        'biz' => '',
        'apiUrl' => '',
    ],

    // AI 智能配置
    'ai' => [
        'enable' => false,
        'driver' => 'OpenAi',
        'apiUrl' => '',
        'apiKey' => '',
        'model' => 'gpt-3.5-turbo',
    ],

    // 路由配置
    'route' => [
        'prefix' => 'ueditorplus',
        'middleware' => ['web', 'admin'],
    ],
];
```

## 使用

### 基本用法

在 Dcat Admin 表单中使用 `ueditorplus` 方法：

```php
use Chisanhe\Ueditorplus\Form\UeditorPlusField;

// 基础用法
$form->ueditorplus('content', '内容');

// 设置高度
$form->ueditorplus('content', '内容')->height(600);

// 自定义上传接口
$form->ueditorplus('content', '内容')->server('custom-upload-url');

// 使用特定磁盘
$form->ueditorplus('content', '内容')->disk('oss');
```

### 使用 135 编辑器

135 编辑器是一款强大的在线排版编辑器：

```php
// 启用 135 编辑器
$form->ueditorplus('content', '内容')
    ->enable135Editor();

// 自定义 135 编辑器配置
$form->ueditorplus('content', '内容')
    ->enable135Editor([
        'apiUrl' => 'https://api.135editor.com/api/',
        'key' => 'your-api-key',
    ]);
```

### 使用秀米编辑器

秀米编辑器是一款简洁美观的在线图文编辑工具：

```php
// 启用秀米编辑器
$form->ueditorplus('content', '内容')
    ->enableXiumiEditor();

// 自定义秀米编辑器配置
$form->ueditorplus('content', '内容')
    ->enableXiumiEditor([
        'biz' => 'your-business-id',
        'apiUrl' => 'https://xiumi.us/api/',
    ]);
```

### 同时启用多个编辑器

```php
$form->ueditorplus('content', '文章内容')
    ->height(600)
    ->enable135Editor()
    ->enableXiumiEditor()
    ->disk('public');
```

### 完整示例

```php
<?php

namespace App\Admin\Controllers;

use App\Models\Article;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Chisanhe\Ueditorplus\Form\UeditorPlusField;

class ArticleController extends AdminController
{
    protected function grid()
    {
        return Grid::make(new Article(), function (Grid $grid) {
            // ...
        });
    }

    protected function form()
    {
        return Form::make(new Article(), function (Form $form) {
            // 基本字段
            $form->text('title', '标题')->required();
            $form->text('author', '作者');
            
            // UEditor Plus 富文本编辑器（支持 135 和秀米）
            $form->ueditorplus('content', '文章内容')
                ->height(600)
                ->enable135Editor()
                ->enableXiumiEditor()
                ->disk('public');
            
            // 其他字段
            $form->image('cover', '封面');
            $form->switch('status', '状态')->default(1);
        });
    }
}
```

### 前端配置

在页面中引入 UEditor Plus 资源后，可以通过 `window.UEDITOR_HOME_URL` 设置资源路径：

```html
<script>
    window.UEDITOR_HOME_URL = '/vendor/ueditorplus/';
</script>
<script src="/vendor/ueditorplus/ueditor.config.js"></script>
<script src="/vendor/ueditorplus/ueditor.all.js"></script>
```

## 路由说明

扩展包会自动注册以下路由：

| 路由 | 方法 | 说明 |
|------|------|------|
| `/{prefix}/serve` | ANY | UEditor Plus 服务入口，处理所有请求 |

默认前缀为 `ueditorplus`，可在配置文件中修改。

## 支持的 Action

扩展包支持以下 UEditor action：

| Action | 说明 |
|--------|------|
| `config` | 获取编辑器配置 |
| `uploadimage` | 上传图片 |
| `uploadvideo` | 上传视频 |
| `uploadfile` | 上传文件 |
| `catchimage` | 抓取远程图片 |
| `listimage` | 获取图片列表 |
| `listfile` | 获取文件列表 |

## 功能特性

- ✅ 支持 135 编辑器（在线排版）
- ✅ 支持秀米编辑器（图文编辑）
- ✅ 支持 AI 智能写作（翻译、续写、总结等）
- ✅ 支持自动保存
- ✅ 支持远程图片抓取
- ✅ 支持图片/视频/文件上传
- ✅ 支持本地存储（可扩展 OSS）

## 存储扩展

默认使用本地存储，如需使用 OSS 等云存储，可以：

1. 实现 `Chisanhe\Ueditorplus\Storage\StorageInterface` 接口
2. 在 `config/ueditorplus.php` 中配置驱动
3. 修改服务提供者绑定

示例：

```php
// 在 AppServiceProvider 或扩展服务提供者中
use Chisanhe\Ueditorplus\Storage\StorageInterface;
use App\Storage\OssStorage;

$this->app->bind(StorageInterface::class, OssStorage::class);
```

## 常见问题

### 1. 上传文件没有反应

- 检查路由是否正确注册：运行 `php artisan route:list | grep ueditor`
- 检查存储磁盘是否可用
- 检查 `storage/app/public` 目录是否存在且可写

### 2. 资源文件 404

- 确保已执行资源发布命令
- 检查 public 目录下的 vendor/ueditorplus 目录是否存在

### 3. 上传大小限制

- 检查 PHP 配置 `upload_max_filesize` 和 `post_max_size`
- 修改 `config/ueditorplus.php` 中对应文件类型的 `MaxSize` 配置

### 4. 135 编辑器或秀米无法使用

- 确保已启用对应的编辑器功能
- 检查配置中的 API 地址和 Key 是否正确
- 查看浏览器控制台是否有错误信息

## 发布到 Packagist

1. 创建 GitHub 仓库并推送代码
2. 在 [Packagist](https://packagist.org) 注册账号
3. 点击 "Submit Package" 按钮
4. 输入仓库地址：`https://github.com/yourname/ueditorplus`
5. 点击 "Check" 验证
6. 点击 "Submit" 完成提交

### 设置自动更新

在 GitHub 仓库的 Settings > Webhooks 中添加 Packagist webhook：

- Payload URL: `https://packagist.org/api/github/`
- Events: Push

同时在 Packagist 的 Package 页面点击 "GitHub incoming webhook" 进行配置。

## License

MIT License
