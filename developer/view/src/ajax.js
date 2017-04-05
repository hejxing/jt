/*
 *  Copyright 2012-2013 (c) Pierre Duquesne <stackp@online.fr>
 *  Licensed under the New BSD License.
 *  https://github.com/stackp/promisejs
 */
/*
 * AJAX requests
 */
const loading = document.createElement('div');
loading.setAttribute('id', 'waitingBar');
loading.className = 'waiting-slider-up';
loading.innerHTML = '<div class="con"></div>';
document.body.appendChild(loading);

loading.info = function(msg, status, closeDelay = 2000){
    if(loading.timer){
        clearTimeout(loading.timer);
    }
    loading.closeDelay = closeDelay;
    loading.querySelector('.con').innerHTML = msg;
    loading.show();
    if(loading.closeDelay){
        loading.close();
    }
};
loading.close = function(){
    loading.timer = setTimeout(function(){
        loading.className = 'waiting-slider-up';
    }, loading.closeDelay);
};
loading.show = function(){
    loading.className = 'waiting-slider-down';
};

const promise = {
    ajax: ajax,
    loading: loading,
    get: newAjax('GET'),
    post: newAjax('POST'),
    put: newAjax('PUT'),
    del: newAjax('DELETE'),
    /**
     * Configuration parameter: time in milliseconds after which a
     * pending AJAX request is considered unresponsive and is
     * aborted. Useful to deal with bad connectivity (e.g. on a
     * mobile network). A 0 value disables AJAX timeouts.
     *
     * Aborted requests resolve the promise with a ETIMEOUT error
     * code.
     */
    ajaxTimeout: 0,
    option: {
        handler: {
            start: function(){
                promise.loading.info('发送请求...', 'start', 0);
                return true;
            },
            success: function(data, xhr, q){
                promise.loading.info(data.msg || '请求成功', 'success', 200);
                return true;
            },
            fail: function(err, xhr, q){
                promise.loading.info(err.msg, 'fail');
                return true;
            },
            error: function(err, xhr, q){
                promise.loading.info(err.msg, 'error');
                return true;
            },
            done: function(xhr, q){
                promise.loading.close();
                return true;
            }
        },
        convert: {
            'json': function(xhr){
                if(!xhr.responseText){
                    return {
                        success: false,
                        code: 'noResponseContent',
                        msg: '获得的内容为空'
                    };
                }
                try{
                    return JSON.parse(xhr.responseText);
                }catch(e){
                    return {
                        success: false,
                        code: 'parseError',
                        msg: '解析数据出错'
                    };
                }
            }
        },
        judge: {
            '200': function(xhr){
                return xhr.data.success? 'success': 'error';
            },
            '401': function(xhr){
                return 'fail';
            },
            'other': function(xhr){
                return 'fail';
            }
        }
    }
};

function serialParam(data){
    let payload = "";
    if(typeof data === "string"){
        payload = data;
    }else{
        const e = encodeURIComponent;
        const params = [];

        for(let k in data){
            if(data.hasOwnProperty(k)){
                params.push(e(k) + '=' + e(data[k]));
            }
        }
        payload = params.join('&')
    }
    return payload;
}

function newXhr(){
    let xhr;
    if(window.XMLHttpRequest){
        xhr = new XMLHttpRequest();
    }else if(window.ActiveXObject){
        try{
            xhr = new ActiveXObject("Msxml2.XMLHTTP");
        }catch(e){
            try{
                xhr = new ActiveXObject("Microsoft.XMLHTTP");
            }catch(e){
                xhr = null;
            }
        }
    }
    return xhr;
}

function genUrl(url, option){
    if(option.path){
        for(let i in option.path){
            if(option.path.hasOwnProperty(i)){
                url = url.replace(new RegExp(':' + i, 'g'), option.path[i]);
            }
        }
    }

    let lastPath = url.split('/').pop();
    if(lastPath.slice(-1) === '.' || lastPath.indexOf('.') === -1){
        url += '.json';
    }

    if(option.query){
        url += (url.indexOf('?') === -1? '?': '&') + serialParam(option.query);
    }


    return url;
}

function MyPromise(callback, xhr){
    const p = this, handler = promise.option.handler, listen = {};
    p.status = 'pending';

    let q;

    const triggerListen = function(status, xhr){
        let fn;
        const data = status === 'success'? xhr.data.data: xhr.data;
        if(handler[status].call(q, data, xhr)){
            while(fn = listen[status].shift()){
                fn.call(q, data, xhr);
            }
        }
        if(handler.done.call(q, xhr)){
            while(fn = listen['done'].shift()){
                fn.call(q, xhr);
            }
        }
    };

    q = {
        resolve(){
            p.status = 'onFulfilled';
            xhr.data = promise.option.convert.json(xhr);
            const status = (promise.option.judge[xhr.status] || promise.option.judge.other)(xhr);
            triggerListen(status, xhr);
        },
        rejected(){
            p.status = 'onRejected';
            xhr.data = {msg: xhr.statusText || xhr.errorMsg || 'unKnow', code: 'fail'};
            triggerListen('fail', xhr);
        }
    };

    for(let h in promise.option.handler){
        if(promise.option.handler.hasOwnProperty(h)){
            listen[h] = [];
            p[h] = function(fn){
                if(p.status === 'pending' && h === 'start'){
                    fn.call(xhr, q);
                    return p;
                }
                listen[h].push(fn);
                if(p.status === 'onFulfilled'){
                    q.resolve();
                }else if(p.status === 'onRejected'){
                    q.reject();
                }
                return p;
            }
        }
    }

    callback(() =>{
        q.resolve();
    }, () =>{
        q.reject();
    });
}

function ajax(method, url, option){
    const xhr = newXhr();
    return new MyPromise(function(resolve, reject){
        method = method.toUpperCase();
        let payload;
        const data = option.data || {}, headers = option.header || {};
        if(xhr === null){
            reject({errorMsg: 'NoXhr'});
            return;
        }

        payload = null;
        if(method !== 'GET'){
            payload = serialParam(data);
        }

        xhr.open(method, genUrl(url, option));

        let content_type = 'application/x-www-form-urlencoded';
        for(let h in headers){
            if(headers.hasOwnProperty(h)){
                if(h.toLowerCase() === 'content-type'){
                    content_type = headers[h];
                }else{
                    xhr.setRequestHeader(h, headers[h]);
                }
            }
        }
        xhr.setRequestHeader('Content-type', content_type);

        let timeout = promise.ajaxTimeout, tid;
        if(timeout){
            tid = setTimeout(function(){
                xhr.abort();
                xhr.errorMsg = 'TimeOutInRequest';
                reject();
            }, timeout);
        }

        xhr.onreadystatechange = function(){
            if(xhr.readyState === 4){
                if(timeout){
                    clearTimeout(tid);
                }
                resolve();
            }
        };

        promise.option.handler.start();
        xhr.send(payload);
    }, xhr);
}

function newAjax(method){
    return function(url, option){
        return ajax(method, url, option || {});
    };
}

export default promise;
