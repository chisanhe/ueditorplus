(function () {
    /**
     * 等待 UE 对象就绪后注册插件
     * 135编辑器、秀米编辑器、自动抓取远程图片
     */

    /**
     * 显示操作提示
     * 优先使用 Dcat Admin 的提示组件，回退到原生 alert/toast
     */
    function showToast(message, type) {
        type = type || 'success';
        // Dcat Admin notify
        if (typeof Dcat !== 'undefined' && Dcat.notify) {
            if (type === 'success') {
                Dcat.notify.success(message);
            } else if (type === 'error') {
                Dcat.notify.error(message);
            } else {
                Dcat.notify(message);
            }
            return;
        }
        // Admin LTE-style toast (兼容旧版 dcat-admin)
        if (typeof toastr !== 'undefined') {
            if (type === 'success') toastr.success(message);
            else if (type === 'error') toastr.error(message);
            else toastr.info(message);
            return;
        }
        // 原生 alert
        if (type === 'success') {
            // 静默成功，不打扰用户
        } else {
            alert(message);
        }
    }

    /**
     * 显示抓图进度提示
     */
    function showProgress(message) {
        if (typeof Dcat !== 'undefined' && Dcat.NP) {
            Dcat.NP.start();
        }
    }

    /**
     * 隐藏进度提示
     */
    function hideProgress() {
        if (typeof Dcat !== 'undefined' && Dcat.NP) {
            Dcat.NP.done();
        }
    }

    /**
     * 抓取并替换正文中的远程图片
     *
     * @param {object} editor UEditor 实例
     * @param {string} action 来源标识（用于提示文案）
     */
    function fetchAndReplaceRemoteImages(editor, action) {
        var body = editor.body || editor.document.body;
        if (!body) return;

        // 收集正文中的所有远程图片 URL（去重）
        var seen = {};
        var remoteSrcs = [];
        var imgs = body.querySelectorAll('img');
        for (var i = 0; i < imgs.length; i++) {
            var src = imgs[i].src || imgs[i].getAttribute('src') || '';
            // 只处理外链图片：http 开头且不含当前域名
            if (src && src.indexOf('http') === 0 && src.indexOf(window.location.host) === -1 && !seen[src]) {
                seen[src] = true;
                remoteSrcs.push({ el: imgs[i], src: src });
            }
        }

        if (remoteSrcs.length === 0) return;

        var total = remoteSrcs.length;
        var fetched = 0;
        var failed = 0;

        // 显示抓图提示
        if (action === 'paste') {
            showToast('检测到 ' + total + ' 张外部图片，正在抓取本地化...', 'info');
        }
        showProgress('正在抓取 ' + total + ' 张图片...');

        // 从页面 DOM 中取 _token 和 disk
        var tokenInput = document.querySelector('input[name=_token]');
        var csrfToken = tokenInput ? tokenInput.value : '';
        var disk = 'public';
        try {
            disk = editor.execCommand('serverparam', 'disk') || 'public';
        } catch (e) {}

        var serverUrl = editor.getOpt('serverUrl') || '';
        if (!serverUrl) {
            hideProgress();
            return;
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', serverUrl + '?action=catchimage&disk=' + encodeURIComponent(disk), true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        if (csrfToken) {
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        }

        // 构造 FormData：source[]=url1&source[]=url2...
        var fd = new FormData();
        for (var j = 0; j < remoteSrcs.length; j++) {
            fd.append('source[]', remoteSrcs[j].src);
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
                                // 用原始 URL 匹配正文中的 img 并替换 src
                                for (var m = 0; m < remoteSrcs.length; m++) {
                                    if (remoteSrcs[m].src === item.source) {
                                        remoteSrcs[m].el.src = item.url;
                                        fetched++;
                                        break;
                                    }
                                }
                            } else {
                                failed++;
                            }
                        }
                    } else {
                        failed = total;
                    }
                } catch (e) {
                    failed = total;
                }
            } else {
                failed = total;
            }

            // 显示抓图结果
            if (fetched === total) {
                showToast('远程图片抓取完成，共 ' + total + ' 张', 'success');
            } else if (fetched > 0) {
                showToast('远程图片抓取完成：' + fetched + ' 张成功，' + failed + ' 张失败', 'error');
            } else {
                showToast('远程图片抓取失败，请检查服务器配置', 'error');
            }
        };

        xhr.onerror = function () {
            hideProgress();
            showToast('远程图片抓取请求失败', 'error');
        };

        xhr.send(fd);
    }

    /**
     * 注册编辑器插件（135、秀米）
     */
    function registerPlugins() {
        if (typeof UE === 'undefined') {
            setTimeout(registerPlugins, 50);
            return;
        }

        // 注册 135 编辑器按钮
        UE.registerUI('135editor', function (editor, uiName) {
            var editor135;

            function onContentFrom135(event) {
                if (typeof event.data !== 'string') {
                    if (event.data && event.data.ready) {
                        editor135.postMessage(editor.getContent(), '*');
                    }
                    return;
                }
                if (event.data.indexOf('<') !== 0) return;

                // 解码 HTML 实体
                var div = document.createElement('div');
                div.innerHTML = event.data;
                var decoded = div.innerHTML;

                editor.setContent(decoded);
                // 手动触发远程图片抓取
                fetchAndReplaceRemoteImages(editor, '135editor');
                window.removeEventListener('message', onContentFrom135);
            }

            var btn = new UE.ui.Button({
                name: 'btn-dialog-' + uiName,
                className: 'edui-for-135editor',
                title: '135编辑器',
                onclick: function () {
                    editor135 = window.open(
                        'https://www.135editor.com/beautify_editor.html?callback=true&appkey=',
                        '135editor',
                        'height=' + (window.screen.availHeight - 100) + ',width=' + (window.screen.availWidth - 100) + ',top=50,left=50,help=no,resizable=no,status=no,scroll=no'
                    );

                    window.removeEventListener('message', onContentFrom135);
                    window.addEventListener('message', onContentFrom135, false);
                }
            });

            return btn;
        }, undefined);

        // 注册秀米编辑器按钮
        UE.registerUI('dialog', function (editor, uiName) {
            var btn = new UE.ui.Button({
                name: 'xiumi-connect',
                title: '秀米',
                onclick: function () {
                    var dialog = new UE.ui.Dialog({
                        iframeUrl: 'https://ent.xiumius.cn/ue/xiumi-ue-dialog-v5.html',
                        editor: editor,
                        name: 'xiumi-connect',
                        title: '秀米图文消息助手',
                        cssRules: 'width: ' + (window.innerWidth - 60) + 'px; height: ' + (window.innerHeight - 60) + 'px;'
                    });
                    dialog.render();
                    dialog.open();
                }
            });

            return btn;
        });

        // 注：contentChange 监听器在 UeditorPlusField.php 的 setupScript 中注册，
        // 这样可以正确地给每个编辑器实例单独添加监听
    }

    registerPlugins();
})();
