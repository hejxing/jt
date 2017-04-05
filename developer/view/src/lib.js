export default {
    queryParam(name){
        let url = location.href;
        url = url.split('#')[0];
        if(url.indexOf('?') === -1){
            return null;
        }
        const arg = url.split('?', 2)[1];
        const reg = new RegExp(name + '=([^&]*)');
        const match = arg.match(reg);
        if(match){
            return match[1];
        }
        return null;
    },
    bind(event, fn, element){
        if(element['addEventListener']){
            element['addEventListener'](event, fn);
        }else if(this['attachEvent']){
            element['attachEvent']('on' + event, fn);
        }
    },
    fireEvent(event, element){
        if(document.createEventObject){
            // IE浏览器支持fireEvent方法
            let evt = document.createEventObject();
            return element.fireEvent('on' + event, evt)
        }else{
            // 其他标准浏览器使用dispatchEvent方法
            let evt = document.createEvent('HTMLEvents');
            // initEvent接受3个参数：
            // 事件类型，是否冒泡，是否阻止浏览器的默认行为
            evt.initEvent(event, true, true);
            return !element.dispatchEvent(evt);
        }
    }
};