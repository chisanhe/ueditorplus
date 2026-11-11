# chisanhe/ueditorplus

Dcat Admin 的 UEditor Plus 富文本编辑器扩展，支持 135 编辑器、秀米编辑器、AI 写作与远程图片自动抓取。

> 原项目：https://github.com/mo3408/ueditorplus · 参考：https://github.com/weiaibaicai/ueditor

## 环境要求

| 依赖 | 支持版本 |
|------|----------|
| PHP | 8.1 ~ 8.4 |
| Laravel | 10 ~ 13.x |
| Dcat Admin | `printnow/laravel-admin` ^2.4 |

> Laravel 9 及以下请使用 **v1.0.4**（依赖官方 `dcat/laravel-admin`）。

## 安装

```bash
composer require chisanhe/ueditorplus

# 发布资源与配置文件
php artisan vendor:publish --provider="Chisanhe\Ueditorplus\ChisanheUeditorplusServiceProvider"
```

配置发布到 `config/ueditorplus.php`（**以发布后的配置为准**）。public 磁盘需执行 `php artisan storage:link`。

## 使用

```php
$form->ueditorplus('content', '文章内容')
    ->height(600)
    ->disk('public')
    ->enable135Editor()
    ->enableXiumiEditor();
```

### 常用方法

| 方法 | 说明 |
|------|------|
| `->height(600)` | 编辑器高度（px），优先于 `initialFrameHeight` 配置 |
| `->editorWidth('100%')` | 编辑器宽度 |
| `->disk('public')` | 指定存储磁盘 |
| `->toolbars([...])` | 自定义工具栏 |
| `->enableAutoSave()` / `->disableAutoSave()` | 自动保存 |
| `->enableRemoteImageFetch()` / `->disableRemoteImageFetch()` | 远程图片抓取 |
| `->enable135Editor()` / `->disable135Editor()` | 135 编辑器 |
| `->enableXiumiEditor()` / `->disableXiumiEditor()` | 秀米编辑器 |
| `->aiConfig([...])` | AI 配置 |
| `->wordCount(true, 100000)` | 字数统计 |
| `->server('/ueditorplus/serve')` | 自定义后端接口地址 |

## 配置

| 配置项 | 默认值 | 说明 |
|--------|--------|------|
| `disk` | `''` | 存储磁盘，留空取 Dcat Admin 的 `admin.upload.disk` |
| `initialFrameHeight` | `400` | 编辑器初始高度（px） |
| `hashFilename` | `true` | 上传文件是否使用 MD5 命名（建议开启） |
| `imageMaxSize` / `videoMaxSize` / `fileMaxSize` | 10MB / 100MB / 50MB | 各类文件最大字节数（图片上传另受 PHP `upload_max_filesize` 限制） |
| `imageAllowFiles` 等 | 见配置文件 | 允许上传的扩展名 |
| `imagePathFormat` 等 | `/ueditorplus/image/{yyyy}{mm}{dd}/{time}{rand:6}` | 保存路径格式 |
| `imageUrlPrefix` 等 | `''` | URL 前缀（CDN 域名），留空用磁盘自带 URL |
| `imageManagerListPath` | `/ueditorplus/image/` | 图片管理器读取目录（需与 `imagePathFormat` 一致） |
| `route.prefix` / `route.middleware` | `ueditorplus` / `['web','admin']` | 后端路由前缀与中间件（含鉴权） |

### 远程图片抓取

```php
'catchRemoteImageEnable' => true,  // 总开关
'catcherPathFormat'      => '/ueditorplus/catcher/{yyyy}{mm}{dd}/{filename}',
'catcherMaxSize'         => 2048000,
'catcherMaxCount'        => 50,     // 单次最多抓取张数，0 不限制
'catcherLocalDomain'     => ['127.0.0.1', 'localhost'], // 这些域名下的图片不抓取
'catcherTimeout'         => 30,     // 单张下载超时（秒）
'catcherReferer'         => 'auto', // auto / none / 自定义
'catcherVerifySsl'       => true,
```

### 安全配置（v1.2.2 新增）

| 配置项 | 默认值 | 说明 |
|--------|--------|------|
| `catcherDenyPrivateIp` | `true` | 抓取远程图片时拒绝内网/保留地址（SSRF 防护），请保持开启 |
| `allowDiskOverride` | `true` | 是否允许请求通过 `disk` 参数切换存储磁盘，无多磁盘需求建议关闭 |
| `catcherRateLimit` | `30` | 抓图接口限流（次/分钟，0 不限） |
| `uploadRateLimit` | `120` | 上传接口限流（次/分钟，0 不限） |

### 路径格式变量

`{yyyy}` `{yy}` `{mm}` `{dd}` `{hh}` `{ii}` `{ss}` `{time}` `{rand:n}` `{filename}`。

> 想要 `日期/文件名.png`，写成 `.../{yyyy}{mm}{dd}/{filename}`；末尾不含 `{filename}` 时文件名会作为最后一段路径拼上去。

## 安全说明

- 后端接口走 Dcat Admin 登录鉴权 + CSRF 校验，未登录不可访问。
- 上传仅允许扩展名白名单，且内置可执行文件黑名单兜底（php/jsp/asp 等）。
- 远程抓取仅接受 HTTP(S)，拒绝内网地址，内容需通过图片魔数校验。
- 存储路径过滤 `..` 段，无路径穿越风险。
- 建议在 Web 服务器层禁止上传目录执行脚本（Nginx 示例）：

```nginx
location ~* ^/storage/.*\.(php|phtml|phar|asp|aspx|jsp)$ { deny all; }
```

## 接口

默认路由 `/{admin 前缀}/ueditorplus/serve`，通过 `action` 参数分发：

| Action | 说明 | Action | 说明 |
|--------|------|--------|------|
| `config` | 获取编辑器配置 | `uploadimage` | 上传图片 |
| `uploadvideo` | 上传视频 | `uploadfile` | 上传文件 |
| `catchimage` | 抓取远程图片 | `listimage` / `listfile` | 图片 / 文件列表 |

## 存储扩展（OSS 等）

实现 `Chisanhe\Ueditorplus\Storage\StorageInterface` 接口后绑定即可：

```php
$this->app->bind(Chisanhe\Ueditorplus\Storage\StorageInterface::class, App\Storage\OssStorage::class);
```

## 常见问题

**上传没反应 / 资源 404**：检查路由 `php artisan route:list | grep ueditor`、磁盘可写、资源是否已发布。

**上传大小受限**：检查 PHP 的 `upload_max_filesize`、`post_max_size`，以及配置中的 `*MaxSize`。

**远程图片抓不到**：看 `laravel.log`（搜索 `[ueditorplus]`）；服务器需开启 `curl`；HTTPS 证书问题设 `catcherVerifySsl=false`；防盗链设 `catcherReferer='none'`。

**抓取返回 419**：会话过期，刷新页面即可；自定义 `->server()` 接口需放在 `web` 中间件组内。

**图片保存了但访问 404**：`.env` 的 `APP_URL` 与实际域名一致，public 磁盘执行 `php artisan storage:link`。
