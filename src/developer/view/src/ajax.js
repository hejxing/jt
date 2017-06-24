/*
 * 适配jt-framework的ajax工具
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
    get(url, option){
        return ajax('GET', url, option || {});
    },
    post(url, option){
        return ajax('POST', url, option || {});
    },
    put(url, option){
        return ajax('PUT', url, option || {});
    },
    del(url, option){
        return ajax('DELETE', url, option || {});
    },
    /**
     * 请求超时时间
     */
    ajaxTimeout: 0,
    option: {
        handler: {
            start: function(xhr, q){
                promise.loading.info('发送请求...', 'start', 0);
                return true;
            },
            success: function(data, xhr, q){
                promise.loading.info(data.msg || '请求成功', 'success', 200);
                return true;
            },
            fail: function(err, xhr, q){
                if(!err.quiet){
                    promise.loading.info(err.msg, 'fail');
                }
                return true;
            },
            error: function(err, xhr, q){
                if(!err.quiet){
                    promise.loading.info(err.msg, 'error');
                }
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
                return xhr.data.success? 'success': 'fail';
            },
            '401': function(xhr){
                return 'error';
            },
            'other': function(xhr){
                return 'error';
            }
        }
    }
};

function serialParam(data){
    if(typeof data !== "object"){
        return data;
    }
    const params = [], e = encodeURIComponent;
    const loop = function(data, key){
        if(typeof data === "object"){
            for(let k in data){
                if(data.hasOwnProperty(k)){
                    loop(data[k], key + (key? '[' + e(k) + ']': e(k)));
                }
            }
        }else{
            params.push(key + '=' + e(data));
        }
    };
    loop(data, '');
    return params.join('&');
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

function triggerListen(status, xhr, listen, resolve, reject){
    const handler = promise.option.handler, q = {"resolve": resolve, "reject": reject};
    let fn;
    const data = status === 'success'?xhr.data.data:xhr.data;
    xhr.page = xhr.data.page || null;
    while(fn = listen[status].shift()){
        if(fn(data, xhr, q) === false){
            break;
        }
    }
    handler[status](data, xhr, q);
    while(fn = listen['done'].shift()){
        if(fn(xhr, q) === false){
            break;
        }
    }
    handler.done(xhr, q);
}

function send(pq, xhr, method, url, option){
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
            pq.reject();
        }, timeout);
    }

    xhr.onreadystatechange = function(){
        if(xhr.readyState === 4){
            if(timeout){
                clearTimeout(tid);
            }
            pq.resolve();
        }
    };

    promise.option.handler.start();
    xhr.send(payload);
}

function createLister(p, q, xhr) {
    const handler = promise.option.handler, listen = {};
    for(let h in handler){
        if(handler.hasOwnProperty(h)){
            listen[h] = [];
        }
    }

    return {
        watcher(fn, h){
            if(p.status === 'pending' && h === 'start'){
                fn(xhr, q);
            }
            listen[h].push(fn);
            if(p.status === 'onFulfilled'){
                q.resolve();
            }else if(p.status === 'onRejected'){
                q.reject();
            }
        },
        getListen(){
            return listen;
        }
    }
}

function ajax(method, url, option){
    const xhr = newXhr();
    const q = {};
    const p = new Promise((resolve, reject)=>{
        q.resolve = resolve;
        q.reject = reject;
    });

    const listen = createLister(p, q, xhr);

    p.status = 'pending';
    p.start = fn => {
        listen.watcher(fn, 'start');
        return p;
    };
    p.success = fn => {
        listen.watcher(fn, 'success');
        return p;
    };
    p.fail = fn => {
        listen.watcher(fn, 'fail');
        return p;
    };
    p.error = fn => {
        listen.watcher(fn, 'error');
        return p;
    };
    p.done = fn => {
        listen.watcher(fn, 'done');
        return p;
    };

    p.then((resolve, reject) => {
        p.status = 'onFulfilled';
        xhr.data = promise.option.convert.json(xhr);
        const status = (promise.option.judge[xhr.status] || promise.option.judge.other)(xhr);
        triggerListen(status, xhr, listen.getListen(), resolve, reject);
    });

    p.catch((resolve, reject)=>{
        p.status = 'onRejected';
        xhr.data = {msg: xhr.statusText || xhr.errorMsg || 'unKnow', code: 'fail'};
        triggerListen('fail', xhr, listen.getListen(), resolve, reject);
    });

    send(q, xhr, method, url, option);

    return p;
}

module.exports = promise;
