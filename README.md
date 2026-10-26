# chisanhe/ueditorplus

Dcat Admin 的 UEditor Plus 富文本编辑器扩展，支持 135 编辑器、秀米编辑器、AI 写作与远程图片自动抓取。

> 原项目：https://github.com/mo3408/ueditorplus · 参考：https://github.com/weiaibaicai/ueditor

## 环境要求

| 依赖 | 支持版本 |
|------|----------|
| PHP | 8.1 ~ 8.4（即 `<8.5`） |
| Laravel | 10 ~ 13.x |
| Dcat Admin | `printnow/laravel-admin` ^2.4（`Dcat\Admin\` 命名空间，与官方版 API 一致） |

> **Laravel 9 及以下**：请继续使用 `chisanhe/ueditorplus` **v1.0.4**（依赖官方 `dcat/laravel-admin`，需将根项目 `minimum-stability` 设为 `dev`）。
> 官方 `dcat/laravel-admin` 的所有发布版本都是 `-beta`，且尚不支持 Laravel 13；
> 本包从 v1.2.0 起改用社区维护版 [printnow/laravel-admin](https://github.com/PrintNow/dcat-admin)（正式稳定发布，支持 Laravel 10 ~ 13），
> 因此 `composer require` 无需任何 `minimum-stability` 配置即可完成安装。

## 安装

```bash
composer require chisanhe/ueditorplus

# 发布资源与配置文件
php artisan vendor:publish --provider="Chisanhe\Ueditorplus\ChisanheUeditorplusServiceProvider"
```

资源发布到 `public/vendor/chisanhe/ueditorplus/`，配置发布到 `config/ueditorplus.php`（**以发布后的配置为准**，包内配置仅作为默认值）。

### 关于 Dcat Admin 依赖

本包通过 `replace` 声明兼容官方 `dcat/laravel-admin`，因此：

- 项目里**已有** `dcat/laravel-admin` 也能直接安装本包，Composer 会自动改用 `printnow/laravel-admin`，不会出现两个包同时提供 `Dcat\Admin\` 命名空间导致类冲突的问题；
- 本包只使用 `Dcat\Admin\` 下的 `Admin`、`Form`、`Form\Field`、`Extend\ServiceProvider` 等 API，两个版本行为一致，无需改动业务代码。

如果你希望继续使用官方 `dcat/laravel-admin`（仅支持 Laravel ≤ 12），需自行在根 `composer.json` 中把 `printnow/laravel-admin` 换成 `dcat/laravel-admin:^2.0@dev`，并设置 `minimum-stability: dev`。

## 使用

```php
$form->ueditorplus('content', '文章内容')
    ->height(600)
    ->disk('public')
    ->enable135Editor()
    ->enableXiumiEditor();
```

完整示例：

```php
use Dcat\Admin\Form;
use Dcat\Admin\Controllers\AdminController;
use App\Models\Article;

class ArticleController extends AdminController
{
    protected function form()
    {
        return Form::make(new Article(), function (Form $form) {
            $form->text('title', '标题')->required();
            $form->ueditorplus('content', '文章内容')
                ->height(600)
                ->enable135Editor()
                ->enableXiumiEditor();
        });
    }
}
```

### 常用方法

| 方法 | 说明 |
|------|------|
| `->height(600)` | 编辑器高度（px） |
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

### 编辑器高度

编辑器高度支持三种设置方式，**优先级从高到低**：

| 优先级 | 设置方式 | 说明 |
|--------|----------|------|
| 1 | `->height(600)` | 表单字段级设置，只影响当前字段 |
| 2 | `config/ueditorplus.php` 的 `initialFrameHeight` | 全局默认值，默认 `400` |
| 3 | UEditor 内置默认值 | `ueditor.config.js` 中的 `minFrameHeight` |

```php
// 方式 1：单个字段设置（推荐）
$form->ueditorplus('content', '文章内容')->height(600);
```

```php
// 方式 2：全局默认（config/ueditorplus.php）
'initialFrameHeight' => 400,
```

> **说明**
>
> UEditor 的 `render()` 在拿不到 `initialFrameHeight` 时，会退化成使用容器的自然高度
> （`options.initialFrameHeight = options.minFrameHeight = container.offsetHeight`），
> 实测在普通表单里约 136~215px。此时在控制台执行
> `UE.instants['ueditorInstant0'].getOpt('initialFrameHeight')` 看到的就是这个值，
> 表现为**编辑器只有内容那么高，而不是设置的高度**。
>
> 本包（v1.2.1 起）的处理方式：
>
> 1. 高度值在 PHP 端统一算出（`->height()` > `config('ueditorplus.initialFrameHeight')` > 400），
>    并直接写入编辑器实例的 `options`（`setOpt` 无法覆盖已存在的键，只能直接赋值）；
> 2. 编辑器 `ready` 之后调用 `ue.setHeight(height)` 强制固定容器高度，
>    写入初始内容后再固定一次（`setHeight` 会同时改写 `options.initialFrameHeight`）；
> 3. 关闭 UEditor 的自动高度（`ueditor.config.js` 与运行时 `UEDITOR_CONFIG` 双重设置），
>    避免 `autoheight` 插件按正文内容把编辑器重新压扁。
>
> 若修改后仍未生效，请确认已执行
> `php artisan vendor:publish --tag=chisanhe.ueditorplus --force`
> 重新发布资源与配置文件（包内文件仅为默认值）。

## 配置

编辑 `config/ueditorplus.php`，常用项如下（其余项在配置文件中均有中文注释）：

| 配置项 | 默认值 | 说明 |
|--------|--------|------|
| `disk` | `''` | 存储磁盘，留空取 Dcat Admin 的 `admin.upload.disk` |
| `initialFrameHeight` | `400` | 编辑器初始高度（px） |
| `hashFilename` | `true` | 上传文件是否使用 MD5 命名 |
| `imageMaxSize` / `videoMaxSize` / `fileMaxSize` | 2MB / 100MB / 50MB | 各类文件最大字节数 |
| `imageAllowFiles` 等 | 见配置文件 | 允许上传的扩展名 |
| `imagePathFormat` 等 | `/ueditorplus/image/{yyyy}{mm}{dd}/{time}{rand:6}` | 保存路径格式 |
| `imageUrlPrefix` 等 | `''` | URL 前缀（CDN 域名），留空用磁盘自带 URL |
| `imageManagerListPath` | `/ueditorplus/image/` | 图片管理器读取目录（需与 `imagePathFormat` 一致） |
| `route.prefix` / `route.middleware` | `ueditorplus` / `['web','admin']` | 后端路由前缀与中间件 |

### 路径格式变量

`{yyyy}` `{yy}` `{mm}` `{dd}` `{hh}` `{ii}` `{ss}` `{time}` `{rand:n}` `{filename}`。

> 末尾**不含** `{filename}` 时，文件名会作为最后一段路径拼上去（多一层随机目录）。
> 想要 `日期/文件名.png`，写成 `.../{yyyy}{mm}{dd}/{filename}`。

### 存储与 URL

最终 URL = `磁盘 url` + `PathFormat 路径` + `文件名`，例如：
`http://127.0.0.1:8000/storage` + `/ueditorplus/catcher/20261020/...` + `xxx.png`

- 磁盘 `url` / `root` 在 `config/filesystems.php` 中定义（public 磁盘需执行 `php artisan storage:link`）
- 配置 `*UrlPrefix` 后只拼接路径部分，用于 CDN，如 `catcherUrlPrefix = https://cdn.example.com`

## 远程图片抓取

粘贴或加载带外链图片的内容时，自动批量下载到本地并替换地址（编辑器初始化、内容变化时触发）。**没有外链时不发起请求、不弹提示**；同一张图在本页内只尝试抓取一次，失败也不会反复提示。

本地图片不会被重复抓取：当前站点域名、`APP_URL`、磁盘 URL、CDN 前缀、以及 `catcherLocalDomain` 中的域名都视为本地。

常用配置：

```php
'catchRemoteImageEnable' => true,  // 总开关
'catcherPathFormat'      => '/ueditorplus/catcher/{yyyy}{mm}{dd}/{filename}',
'catcherMaxSize'         => 2048000,
'catcherLocalDomain'     => ['127.0.0.1', 'localhost'], // 这些域名下的图片不抓取
'catcherTimeout'         => 30,     // 单张下载超时（秒）
'catcherMaxCount'        => 50,     // 单次最多抓取张数，0 不限制
'catcherReferer'         => 'auto', // auto / none / 自定义
'catcherVerifySsl'       => true,
```

抓取失败会记录到 `storage/logs/laravel.log`（搜索 `[ueditorplus] 远程图片抓取失败`）。文件名取 `md5(图片URL)`，扩展名从 URL、query（`wx_fmt` 等）、Content-Type 依次推断。

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

**远程图片抓不到**：看 `laravel.log` 的具体原因；服务器需开启 `curl`；HTTPS 证书问题设 `catcherVerifySsl=false`；防盗链设 `catcherReferer='none'`。

**抓取返回 419**：请求已自动携带 CSRF token；自定义 `->server()` 接口时需放在 `web` 中间件组内，会话过期刷新页面即可。

**图片保存了但访问 404**：`.env` 的 `APP_URL` 与实际域名一致，public 磁盘执行 `php artisan storage:link`。
