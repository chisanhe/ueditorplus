/**
 * UEditor Plus 完整配置项
 * 
 * 支持功能：
 * - 135编辑器
 * - 秀米编辑器
 * - AI智能写作
 * - 自动保存
 * - 远程图片抓取
 * 
 * @package Chisanhe\Ueditorplus
 * @author  chisanhe
 * @since   1.0.0
 */
(function () {
    var URL, CORS_URL;
    
    // 获取资源基础路径
    if (window.UEDITOR_HOME_URL) {
        URL = window.UEDITOR_HOME_URL;
    } else if (window.__msCDN) {
        URL = window.__msCDN + 'asset/vendor/ueditor/';
    } else if (window.__msRoot) {
        URL = window.__msRoot + 'asset/vendor/ueditor/';
    } else {
        URL = getUEBasePath();
    }
    
    // 获取跨域资源路径
    if (window.UEDITOR_CORS_URL) {
        CORS_URL = window.UEDITOR_CORS_URL;
    } else if (window.__msRoot) {
        CORS_URL = window.__msRoot + 'asset/vendor/ueditor/';
    } else if (window.UEDITOR_HOME_URL) {
        CORS_URL = window.UEDITOR_HOME_URL;
    } else {
        CORS_URL = getUEBasePath();
    }

    window.UEDITOR_CONFIG = {
        // 编辑器资源路径
        UEDITOR_HOME_URL: URL,
        // 跨域资源路径
        UEDITOR_CORS_URL: CORS_URL,
        
        // 是否开启 Debug 模式
        debug: false,
        
        // 服务器统一请求接口路径
        // 实际使用中会被后端动态配置
        serverUrl: "",
        
        // 是否从服务器加载配置
        loadConfigFromServer: false,
        
        // 服务器请求头
        serverHeaders: {},
        
        // 服务器返回参数处理
        serverResponsePrepare: function (res) {
            return res;
        },
        
        // 工具栏配置 - 完整版工具栏，包含 135编辑器和秀米
        toolbars: [[
            "fullscreen",           // 全屏
            "source",               // 源代码
            "|",
            "undo",                 // 撤销
            "redo",                 // 重做
            "|",
            "bold",                 // 加粗
            "italic",               // 斜体
            "underline",            // 下划线
            "fontborder",           // 字符边框
            "strikethrough",        // 删除线
            "superscript",          // 上标
            "subscript",            // 下标
            "removeformat",         // 清除格式
            "formatmatch",          // 格式刷
            "autotypeset",          // 自动排版
            "blockquote",           // 引用
            "pasteplain",           // 纯文本粘贴模式
            "|",
            "forecolor",           // 字体颜色
            "backcolor",           // 背景色
            "insertorderedlist",    // 有序列表
            "insertunorderedlist",  // 无序列表
            "selectall",            // 全选
            "cleardoc",            // 清空文档
            "|",
            "rowspacingtop",        // 段前距
            "rowspacingbottom",     // 段后距
            "lineheight",           // 行间距
            "letterspacing",        // 字间距
            "|",
            "customstyle",          // 自定义标题
            "paragraph",            // 段落格式
            "fontfamily",          // 字体
            "fontsize",            // 字号
            "|",
            "directionalityltr",    // 从左向右输入
            "directionalityrtl",    // 从右向左输入
            "indent",              // 首行缩进
            "|",
            "justifyleft",          // 居左对齐
            "justifycenter",       // 居中对齐
            "justifyright",         // 居右对齐
            "justifyjustify",       // 两端对齐
            "|",
            "touppercase",         // 字母大写
            "tolowercase",         // 字母小写
            "|",
            "link",                // 超链接
            "unlink",              // 取消链接
            "anchor",              // 锚点
            "|",
            "imagenone",           // 图片默认
            "imageleft",           // 图片左浮动
            "imagecenter",         // 图片居中
            "imageright",          // 图片右浮动
            "|",
            "simpleupload",        // 单图上传
            "insertimage",         // 多图上传
            "emotion",             // 表情
            "scrawl",             // 涂鸦
            "insertvideo",        // 视频
            "insertaudio",        // 音频
            "attachment",          // 附件
            "insertframe",        // 插入Iframe
            "insertcode",         // 插入代码
            "pagebreak",          // 分页
            "template",           // 模板
            "background",         // 背景
            "formula",            // 公式
            "|",
            "horizontal",         // 分隔线
            "date",               // 日期
            "time",               // 时间
            "spechars",           // 特殊字符
            "wordimage",          // Word图片转存
            "|",
            "inserttable",        // 插入表格
            "deletetable",        // 删除表格
            "insertparagraphbeforetable",
            "insertrow",          // 前插入行
            "deleterow",          // 删除行
            "insertcol",          // 前插入列
            "deletecol",          // 删除列
            "mergecells",          // 合并多个单元格
            "mergeright",          // 右合并单元格
            "mergedown",           // 下合并单元格
            "splittocells",        // 完全拆分单元格
            "splittorows",         // 拆分成行
            "splittocols",         // 拆分成列
            "|",
            "print",              // 打印
            "preview",            // 预览
            "searchreplace",      // 查询替换
            "|",
            "contentimport",      // 内容导入
            "135editor",          // 135编辑器
            "dialog",             // 秀米编辑器
            "ai",                 // AI智能
            "|",
            "help"                // 帮助
        ]],
        
        // 动态工具栏显示配置
        toolbarShows: {},
        
        // 工具栏按钮点击回调
        toolbarCallback: function (cmd, editor) {
            // 可以在这里处理自定义按钮点击事件
            return false;
        },
        
        // 上传服务配置
        uploadServiceEnable: false,
        uploadServiceUpload: function (type, file, callback, option) {
            console.log('uploadServiceUpload', type, file, callback, option);
        },
        
        // 135编辑器配置
        editor135Config: {
            enable: true,
            // 135编辑器接口地址
            apiUrl: '',
        },
        
        // 秀米编辑器配置
        xiumiEditorConfig: {
            enable: true,
            // 秀米 business ID
            biz: '',
            // 秀米接口地址
            apiUrl: '',
        },
        
        // 图片上传配置
        imageConfig: {
            disableUpload: false,
            disableOnline: false,
            selectCallback: null,
        },
        
        // 视频上传配置
        videoConfig: {
            disableUpload: false,
            selectCallback: null,
        },
        
        // 音频上传配置
        audioConfig: {
            disableUpload: false,
            selectCallback: null,
        },
        
        // 公式配置
        formulaConfig: {
            imageUrlTemplate: 'https://r.latexeasy.com/image.svg?{}',
            editorMode: 'live',
            editorLiveServer: 'https://latexeasy.com',
        },
        
        // 自动保存配置
        autoSaveEnable: true,
        autoSaveRestore: false,
        autoSaveKey: null,
        
        // 初始化内容
        initialContent: '',
        
        // 是否获取焦点
        focus: false,
        
        // 初始化样式
        initialStyle: '',
        
        // 首行缩进
        indentValue: '2em',
        
        // 是否只读
        readonly: false,
        
        // 是否自动清除空节点
        autoClearEmptyNode: true,
        
        // 是否全屏
        fullscreen: false,
        
        // 是否启用上下文菜单
        enableContextMenu: true,
        
        // 快捷菜单
        shortcutMenu: [
            "ai",
            "bold",
            "italic",
            "underline",
            "strikethrough",
            "fontborder",
            "forecolor",
            "backcolor",
            "imagenone",
            "imageleft",
            "imagecenter",
            "imageright",
            "insertimage",
            "formula"
        ],
        
        // 快捷菜单显示配置
        shortcutMenuShows: {},
        
        // 是否显示元素路径
        elementPathEnabled: true,
        
        // 是否开启字数统计
        wordCount: true,
        
        // 最大字符数
        maximumWords: 100000,
        
        // 最大撤销次数
        maxUndoCount: 20,
        
        // 最大输入次数
        maxInputCount: 1,
        
        // 是否自动高度
        autoHeightEnabled: true,
        
        // 编辑器最小高度
        minFrameHeight: 220,
        
        // 是否保持工具栏浮动
        autoFloatEnabled: true,
        
        // 工具栏距离顶部偏移
        topOffset: 0,
        
        // 工具栏距离编辑区偏移
        toolbarTopOffset: 0,
        
        // 是否启用远程图片抓取
        catchRemoteImageEnable: true,
        
        // 自动排版配置
        autotypeset: {
            mergeEmptyline: true,
            removeClass: true,
            removeEmptyline: false,
            textAlign: "left",
            imageBlockLine: "center",
            pasteFilter: false,
            clearFontSize: false,
            clearFontFamily: false,
            removeEmptyNode: false,
            removeTagNames: {div: 1},
            indent: false,
            indentValue: "2em",
            bdc2sb: false,
            tobdc: false
        },
        
        // 是否允许 div 转 p 标签
        allowDivTransToP: true,
        
        // RGB 转 HEX
        rgb2Hex: true,
        
        // AI 智能配置
        ai: {
            // 大模型驱动
            // 支持: ModStart, OpenAi, DeepSeek, Anthropic, Google, Baidu, 
            //      Alibaba, Tencent, Huawei, ByteDance, Zhipu, Moonshot, iFlytek, Volcengine
            driver: 'OpenAi',
            
            // 大模型配置
            driverConfig: {
                url: '',
                key: '',
                model: '',
            },
        },
        
        // AI 功能列表
        aiFunctions: [
            {
                name: 'translate',
                text: '翻译',
                systemPrompt: '你是一个专业的翻译专家，擅长在中文和英文之间进行准确、自然的翻译。请将提供的文本翻译成目标语言，保持原文的含义、语气和上下文。只返回翻译后的内容，不要添加任何额外说明。',
                prompt: '{selectText}',
                enable: function (param) {
                    return !!param.selectText;
                }
            },
            {
                name: 'continueWrite',
                text: '续写',
                systemPrompt: '你是一个优秀的内容创作者，能够根据用户选中的文本进行自然流畅的续写。请续写内容，使其与原有文本无缝衔接，并保持一致的风格和主题。只返回续写后的内容，不要添加任何额外说明。',
                prompt: '{selectText}',
                enable: function (param) {
                    return !!param.selectText;
                }
            },
            {
                name: 'summarize',
                text: '简化内容',
                systemPrompt: '你是一个内容简化专家，能够将复杂的内容用简单易懂的语言表达出来。请简化提供的文本，使其更易理解，同时保留核心信息。只返回简化后的内容，不要添加任何额外说明。',
                prompt: '{selectText}',
                enable: function (param) {
                    return !!param.selectText;
                }
            },
            {
                name: 'extend',
                text: '丰富内容',
                systemPrompt: '你是一个内容扩展专家，能够将简短的内容进行扩展，增加细节和描述，使其更加丰富。请扩展提供的文本，添加相关细节、例子或解释，以增强内容的深度和吸引力。只返回扩展后的内容，不要添加任何额外说明。',
                prompt: '{selectText}',
                enable: function (param) {
                    return !!param.selectText;
                }
            },
            {
                name: 'optimizeWhole',
                text: '优化整篇内容',
                systemPrompt: '你是一个专业的写作优化专家，擅长提升文章的整体质量和可读性。请对提供的整篇文章进行润色和优化，包括但不限于：语法修正、结构调整、逻辑梳理、表达优化。确保文章流畅、清晰、有吸引力，同时保持原文的核心内容、意图和风格不变。可以适当添加Emoji表情来增强可读性，但不要过度使用。输出必须是有效的HTML内容（body部分），不要包含<html>或<body>标签本身，也不要添加任何额外的说明或注释。',
                prompt: '{html}',
                enable: function (param) {
                    return true;
                },
                param: {
                    showInsert: false,
                    showReplace: false,
                    showReplaceAll: true,
                },
            }
        ],
        
        // 错误提示回调
        tipError: function (msg, param) {
            if (window && window.MS && window.MS.dialog) {
                window.MS.dialog.tipError(msg);
            } else {
                alert(msg);
            }
        }
    };

    function getUEBasePath() {
        var docUrl = self.document.URL || self.location.href;
        var configPath = document.getElementsByTagName("script");
        configPath = configPath[configPath.length - 1].src;
        return getBasePath(docUrl, configPath);
    }

    function getBasePath(docUrl, confUrl) {
        var basePath = confUrl;
        
        if (/^(\/|\\)/.test(confUrl)) {
            basePath = /^.+?\w(\/|\\)/.exec(docUrl)[0] + confUrl.replace(/^(\/|\\)/, "");
        } else if (!/^[a-z]+:/i.test(confUrl)) {
            docUrl = docUrl.split("#")[0].split("?")[0].replace(/[^\\\/]+$/, "");
            basePath = docUrl + "" + confUrl;
        }
        
        return optimizationPath(basePath);
    }

    function optimizationPath(path) {
        var protocol = /^[a-z]+:\/\//.exec(path)[0],
            tmp = null,
            res = [];
            
        path = path.replace(protocol, "").split("?")[0].split("#")[0];
        path = path.replace(/\\/g, "/").split(/\//);
        path[path.length - 1] = "";
        
        while (path.length) {
            if ((tmp = path.shift()) === "..") {
                res.pop();
            } else if (tmp !== ".") {
                res.push(tmp);
            }
        }
        
        return protocol + res.join("/");
    }

    window.UE = {
        getUEBasePath: getUEBasePath
    };
})();
