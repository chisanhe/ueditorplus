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

### 1. 发布资源文件和配置文件

```bash
php artisan vendor:publish --provider="Chisanhe\Ueditorplus\ChisanheUeditorplusServiceProvider"
```

发布后前端资源位于 `public/vendor/chisanhe/ueditorplus/`，配置文件位于 `config/ueditorplus.php`。
**配置以 `config/ueditorplus.php` 为准**，包内那份只在未发布时作为默认值。

### 2. 完整配置示例（config/ueditorplus.php）

```php
<?php

return [
    /**
     * 存储磁盘配置
     *
     * 使用 Laravel Filesystem 的磁盘（config/filesystems.php 中定义的 disks）
     * 留空则使用 Dcat Admin 的上传磁盘（admin.upload.disk），一般为 public/admin
     */
    'disk' => 'admin',

    /**
     * 初始化编辑器高度（像素）
     *
     * 可通过 $form->ueditorplus('content')->height(600) 单独覆盖
     */
    'initialFrameHeight' => 600,

    /**
     * 是否使用哈希文件名
     *
     * true:  使用 MD5 哈希作为文件名（保留原扩展名）
     * false: 使用原始文件名
     */
    'hashFilename' => true,

    // ==================== 图片上传配置 ====================

    /** 图片上传 action 名称 */
    'imageActionName' => 'uploadimage',
    /** 图片上传表单字段名 */
    'imageFieldName' => 'upfile',
    /** 图片最大上传大小（字节），默认 2MB */
    'imageMaxSize' => 2048000,
    /** 允许上传的图片格式 */
    'imageAllowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp'],
    /** 图片访问路径前缀，用于拼接 CDN 域名，留空则使用磁盘自身的 URL */
    'imageUrlPrefix' => '',
    /** 图片保存路径格式，可用变量见下方「路径变量」 */
    'imagePathFormat' => '/ueditorplus/image/{yyyy}{mm}{dd}/{time}{rand:6}',

    // ==================== 视频上传配置 ====================

    'videoActionName' => 'uploadvideo',
    'videoFieldName' => 'upfile',
    'videoMaxSize' => 102400000, // 100MB
    'videoAllowFiles' => [
        '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb',
        '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv',
        '.mp4', '.webm', '.mp3', '.wav', '.mid',
    ],
    'videoUrlPrefix' => '',
    'videoPathFormat' => '/ueditorplus/video/{yyyy}{mm}{dd}/{time}{rand:6}',

    // ==================== 文件上传配置 ====================

    'fileActionName' => 'uploadfile',
    'fileFieldName' => 'upfile',
    'fileMaxSize' => 51200000, // 50MB
    'fileAllowFiles' => [
        '.png', '.jpg', '.jpeg', '.gif', '.bmp',
        '.flv', '.swf', '.mkv', '.avi', '.rm', '.rmvb',
        '.mpeg', '.mpg', '.ogg', '.ogv', '.mov', '.wmv',
        '.mp4', '.webm', '.mp3', '.wav', '.mid',
        '.rar', '.zip', '.tar', '.gz', '.7z', '.bz2', '.cab', '.iso',
        '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx',
        '.pdf', '.txt', '.md', '.xml',
    ],
    'fileUrlPrefix' => '',
    'filePathFormat' => '/ueditorplus/file/{yyyy}{mm}{dd}/{time}{rand:6}',

    // ==================== 远程图片抓取配置 ====================

    /** 是否启用远程图片自动抓取（粘贴/加载内容时自动本地化外部图片） */
    'catchRemoteImageEnable' => true,

    /** 本地域名白名单：图片地址命中这些域名时不抓取（避免重复下载本站图片） */
    'catcherLocalDomain' => ['127.0.0.1', 'localhost'],

    /** 远程图片抓取 action 名称 */
    'catcherActionName' => 'catchimage',
    /** 远程图片字段名 */
    'catcherFieldName' => 'source',
    /** 抓取图片最大大小（字节） */
    'catcherMaxSize' => 2048000,
    /** 允许抓取的图片格式 */
    'catcherAllowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp'],
    /** 抓取图片访问路径前缀（CDN 域名），留空则使用磁盘自身的 URL */
    'catcherUrlPrefix' => '',
    /** 抓取图片保存路径格式 */
    'catcherPathFormat' => '/ueditorplus/catcher/{yyyy}{mm}{dd}/{time}{rand:6}',

    /** 单个远程图片的下载超时时间（秒） */
    'catcherTimeout' => 30,
    /** 单次请求最多抓取的图片数量，0 表示不限制 */
    'catcherMaxCount' => 50,
    /** 下载时使用的 User-Agent，部分图床/CDN 会拦截脚本 UA */
    'catcherUserAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    /** Referer 策略：auto（图片所在站点根地址）/ none（不发送）/ 自定义字符串 */
    'catcherReferer' => 'auto',
    /** 是否校验 SSL 证书，服务器未配置 CA 证书时可临时设为 false */
    'catcherVerifySsl' => true,

    // ==================== 图片管理器配置 ====================

    'imageManagerActionName' => 'listimage',
    /** 图片管理器读取的目录 */
    'imageManagerListPath' => '/ueditorplus/image/',
    /** 每页数量 */
    'imageManagerListSize' => 20,
    'imageManagerUrlPrefix' => '',
    'imageManagerAllowFiles' => ['.png', '.jpg', '.jpeg', '.gif', '.bmp'],

    // ==================== 文件管理器配置 ====================

    'fileManagerActionName' => 'listfile',
    'fileManagerListPath' => '/ueditorplus/file/',
    'fileManagerListSize' => 20,
    'fileManagerUrlPrefix' => '',
    'fileManagerAllowFiles' => [
        '.png', '.jpg', '.jpeg', '.gif', '.bmp',
        '.rar', '.zip', '.tar', '.gz', '.7z', '.bz2', '.cab', '.iso',
        '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx',
        '.pdf', '.txt', '.md', '.xml',
    ],

    // ==================== 路由配置 ====================

    'route' => [
        // 路由前缀，最终地址 /{admin 前缀}/ueditorplus/serve
        'prefix' => 'ueditorplus',
        // 路由中间件
        'middleware' => ['web', 'admin'],
    ],

    // ==================== 135编辑器配置 ====================

    'editor135' => [
        'enable' => true,
        'apiUrl' => '',
        'key' => '',
    ],

    // ==================== 秀米编辑器配置 ====================

    'xiumi' => [
        'enable' => true,
        'biz' => '',
        'apiUrl' => '',
    ],

    // ==================== AI 智能配置 ====================

    'ai' => [
        'enable' => false,
        'driver' => 'OpenAi',
        'apiUrl' => '',
        'apiKey' => '',
        'model' => 'gpt-3.5-turbo',
    ],
];
```

### 3. 配置项速查

| 配置项 | 默认值 | 说明 |
|--------|--------|------|
| `disk` | `''` | 存储磁盘名，留空取 `admin.upload.disk`（通常 `public`/`admin`） |
| `initialFrameHeight` | `400` | 编辑器初始高度（px），可用 `->height()` 覆盖 |
| `hashFilename` | `true` | 上传文件是否使用 MD5 哈希命名 |
| `imageActionName` | `uploadimage` | 上传图片接口 action 名 |
| `imageFieldName` | `upfile` | 上传图片的表单字段名 |
| `imageMaxSize` | `2048000` | 图片最大字节数 |
| `imageAllowFiles` | 5 种图片后缀 | 允许上传的图片扩展名 |
| `imageUrlPrefix` | `''` | 图片 URL 前缀（CDN 域名），留空用磁盘自带 URL |
| `imagePathFormat` | `/ueditorplus/image/{yyyy}{mm}{dd}/{time}{rand:6}` | 图片保存路径格式 |
| `video*` / `file*` | 见示例 | 视频 / 附件的同名配置（MaxSize、AllowFiles、UrlPrefix、PathFormat） |
| `catchRemoteImageEnable` | `true` | 是否开启远程图片自动抓取 |
| `catcherLocalDomain` | `['127.0.0.1','localhost']` | 这些域名下的图片不抓取 |
| `catcherMaxSize` | `2048000` | 单张远程图片最大字节数 |
| `catcherAllowFiles` | 5 种图片后缀 | 允许保存的远程图片扩展名 |
| `catcherUrlPrefix` | `''` | 抓取图片的 URL 前缀（CDN 域名） |
| `catcherPathFormat` | `/ueditorplus/catcher/{yyyy}{mm}{dd}/{time}{rand:6}` | 抓取图片保存路径格式 |
| `catcherTimeout` | `30` | 单张图片下载超时（秒） |
| `catcherMaxCount` | `50` | 单次请求最多抓取张数，0 为不限制 |
| `catcherUserAgent` | Chrome UA | 下载时使用的 UA，绕过 UA 拦截 |
| `catcherReferer` | `auto` | `auto` / `none` / 自定义 Referer |
| `catcherVerifySsl` | `true` | 是否校验 SSL 证书 |
| `imageManagerListPath` | `/ueditorplus/image/` | 图片管理器读取目录（需与 `imagePathFormat` 保持一致） |
| `fileManagerListPath` | `/ueditorplus/file/` | 文件管理器读取目录 |
| `route.prefix` | `ueditorplus` | 后端服务路由前缀 |
| `route.middleware` | `['web','admin']` | 后端服务路由中间件 |

### 4. 路径变量

所有 `*PathFormat` 支持以下变量：

| 变量 | 含义 | 示例 |
|------|------|------|
| `{yyyy}` | 四位年份 | `2026` |
| `{yy}` | 两位年份 | `26` |
| `{mm}` / `{dd}` | 月 / 日 | `10` / `20` |
| `{hh}` / `{ii}` / `{ss}` | 时 / 分 / 秒 | `13` / `05` / `09` |
| `{time}` | 时间戳 | `1792474739` |
| `{rand:n}` | n 位随机数 | `{rand:6}` → `627763` |
| `{filename}` | 生成好的文件名（含扩展名） | `146ec7d959a2f8d963a2460f633efdf0.png` |

> 格式末尾**不含** `{filename}` 时，文件名会作为最后一段路径拼上去，例如
> `.../{yyyy}{mm}{dd}/{time}{rand:6}` 会生成 `20261020/1792474739627763/xxx.png`（多一层随机目录）。
> 想让文件名直接落在日期目录下，请写成 `.../{yyyy}{mm}{dd}/{filename}`。

### 5. 存储位置与最终 URL

返回结果中的 URL 由三部分组成：

```
{磁盘 url} + {PathFormat 生成的路径} + {文件名}
例：http://127.0.0.1:8000/storage  +  /ueditorplus/catcher/20261020/...  +  xxx.png
```

- **磁盘 url**：`config/filesystems.php` 中对应磁盘的 `url`（如 public 磁盘默认 `env('APP_URL').'/storage'`，需执行 `php artisan storage:link`）
- **物理位置**：对应磁盘的 `root`（如 public 磁盘为 `storage/app/public`）
- **`*UrlPrefix`**：配置后只拼接 URL 中的路径部分作为前缀，用于 CDN 场景，例如
  `catcherUrlPrefix = https://cdn.example.com` → `https://cdn.example.com/storage/ueditorplus/catcher/...`

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

### 远程图片自动抓取

编辑器会自动检测正文中的外部图片链接，并在内容变化（如粘贴）时自动将图片下载到本地服务器，替换为本地路径，确保文章在任何环境下都能正常显示。

**功能特点：**

- 粘贴外部图片链接后 **1 秒内**自动触发抓取
- 同时支持从 **135 编辑器** 粘贴内容时自动抓取
- 抓取完成后显示成功/失败提示
- 自动去重，避免同一图片重复抓取
- 兼容 Dcat Admin 提示组件（`Dcat.notify`）和 `toastr`

**使用方式：** 无需额外配置，默认已启用。

**关闭自动抓取：** 配置文件全局关闭：

```php
// config/ueditorplus.php
'catchRemoteImageEnable' => false,
```

或对单个字段关闭：

```php
$form->ueditorplus('content', '内容')
    ->disableRemoteImageFetch();
```

**抓取相关配置**（完整说明见上方「配置项速查」）：

```php
'catchRemoteImageEnable' => true,   // 总开关
'catcherPathFormat'      => '/ueditorplus/catcher/{yyyy}{mm}{dd}/{time}{rand:6}',
'catcherMaxSize'         => 2048000,
'catcherAllowFiles'      => ['.png', '.jpg', '.jpeg', '.gif', '.bmp'],
'catcherLocalDomain'     => ['127.0.0.1', 'localhost'],
'catcherTimeout'         => 30,
'catcherMaxCount'        => 50,
'catcherUserAgent'       => 'Mozilla/5.0 ...',
'catcherReferer'         => 'auto',
'catcherVerifySsl'       => true,
```

抓取到的文件名为 `md5(远程图片URL) . 扩展名`，扩展名依次从 URL 路径、query 参数（如微信正文的 `wx_fmt=jpeg`）、响应 Content-Type 推断，最后兜底为 `jpg`。

**实现方式说明：** UEditorPlus 自带 `catchremoteimage` 插件，本扩展默认将其关闭，改由自带的批量抓取逻辑处理（一次请求抓取全部图片、带提示、完成后同步回表单值），避免两者重复请求、图片被替换成 loading 占位图后无法恢复。

> **提示：** 抓图接口地址为 `/{admin 前缀}/ueditorplus/serve?action=catchimage`，需要服务器可正常访问外网（PHP 需启用 curl 或开启 `allow_url_fopen`）。抓取失败时会把具体原因写入 `laravel.log`，便于排查（如证书、超时、防盗链、非图片响应等）。

> **CSRF：** 抓图请求会自动携带 CSRF token（依次从 `meta[name=csrf-token]`、`input[name=_token]`、`Dcat.token` 获取，最后使用服务端注入的值），并在请求头 `X-CSRF-TOKEN` 与表单字段 `_token` 中同时传递。若自定义 `server()` 接口，请确保该路由使用 `web` 中间件组并放行 CSRF 校验，否则会返回 `419 CSRF token mismatch`。

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

### 5. 远程图片抓取失败

按顺序排查：

1. 查看 `storage/logs/laravel.log`，抓图失败会记录 `[ueditorplus] 远程图片抓取失败: {url} - {原因}`
2. 服务器能否访问外网：PHP 需启用 `curl`（或开启 `allow_url_fopen`）
3. HTTPS 报证书错误：临时把 `catcherVerifySsl` 设为 `false`，或正确配置 `openssl.cafile`
4. 图片服务器有防盗链：把 `catcherReferer` 设为 `none`，或改为该站点要求的 Referer
5. 响应不是图片（返回 403/HTML 页面）：调整 `catcherUserAgent`
6. 图片过大：`catcherMaxSize`；一次粘贴图片过多：`catcherMaxCount`、`catcherTimeout`

### 6. 抓取请求返回 419（CSRF token mismatch）

抓图请求会自动携带 token（请求头 `X-CSRF-TOKEN` 与表单字段 `_token`）。若使用 `->server()` 指定了自定义接口，请确保该路由位于 `web` 中间件组内；页面长时间未操作导致会话过期时，刷新页面即可。

### 7. 图片能保存但前台访问 404

- 检查 `.env` 的 `APP_URL` 与实际访问域名一致（URL 由磁盘 `url` 配置拼接）
- 使用 `public` 磁盘时执行 `php artisan storage:link`
- 配置 CDN 时使用对应类型的 `*UrlPrefix`（如 `catcherUrlPrefix`），它只拼接路径部分
