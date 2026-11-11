{{--
/**
 * UEditor Plus 表单视图模板
 *
 * 用于渲染 UEditor Plus 富文本编辑器表单字段
 *
 * @package Chisanhe\Ueditorplus\Resources\Views
 * @author  chisanhe
 * @since   1.0.0
 */
--}}
<div class="{{$viewClass['form-group']}} {!! !$errors->has($column) ?: 'has-error' !!}">
    {{-- 标签 --}}
    <label for="{{$id}}" class="{{$viewClass['label']}} control-label">
        {{$label}}
    </label>

    {{-- 字段容器 --}}
    <div class="{{$viewClass['field']}}">
        {{-- 错误提示 --}}
        @include('admin::form.error')

        {{-- 编辑器容器 --}}
        <div class="ueditor-plus-wrapper" style="line-height: normal;">
            {{--
                关键修复：不使用 textarea 作为 initialContent 载体，
                也不在 HTML 属性中放原始 HTML（浏览器会编码 < > & 字符）。
                textarea 为空，内容通过 PHP json_encode → JS setContent() 设置。
                原始内容存在 data-original-content 中（JSON 编码，不经过 HTML 解析）。
            --}}
            {{--
                注意：script 容器不要带 name 属性。UEditor 渲染时会把容器的 name
                赋给 editor.options.textarea，随后在原生 form submit 时创建一个
                <textarea name="content">，与下方隐藏域同名。Dcat 用 AJAX 提交时
                该 textarea 值长期过期，PHP 端同名参数后者覆盖前者，导致保存的
                永远是旧内容（表现为抓图结果不落库、每次编辑重复抓取）。
                字段值只通过下方隐藏域提交，由 JS 的 contentchange 事件同步。
            --}}
            <script id="{{$id}}" type="text/plain"></script>
            <input type="hidden" id="{{$id}}-value" name="{{$name}}" data-original-content="{{ $contentDataAttr }}">
        </div>

        {{-- 帮助提示 --}}
        @include('admin::form.help-block')
    </div>
</div>

{{-- 设置 UEditor 资源路径（在 DOM 中同步执行，早于所有外部 JS） --}}
<script>
    window.UEDITOR_HOME_URL = '{{ $homeUrl }}';
    // 关键：在 UEditor JS 加载之前覆盖 serverUrl，防止它用旧配置发 config 请求
    if (typeof UEEDITOR_CONFIG !== 'undefined') {
        UEEDITOR_CONFIG.serverUrl = '{{ $serverUrl }}';
    }
</script>
