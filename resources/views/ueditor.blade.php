{{-- /**
 * UEditor Plus 表单视图模板
 * 
 * 用于渲染 UEditor Plus 富文本编辑器表单字段
 * 
 * @package Chisanhe\Ueditorplus\Resources\Views
 * @author  chisanhe
 * @since   1.0.0
 */ --}}
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
            {{-- 隐藏的 textarea 用于存储编辑器内容 --}}
            <script id="{{$id}}" name="{{$name}}" type="text/plain">{!! $value !!}</script>
            {{-- 隐藏字段用于表单提交 --}}
            <input type="hidden" id="{{$id}}-value" name="{{$name}}" value="{!! $value !!}">
        </div>

        {{-- 帮助提示 --}}
        @include('admin::form.help-block')
    </div>
</div>

{{-- 设置 UEditor 资源路径 --}}
<script>
    // 全局配置 UEditor 资源路径
    window.UEDITOR_HOME_URL = '{{ $homeUrl }}';
</script>
