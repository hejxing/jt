{{extends file='layout\error.tpl'}}
{{block name="cssJs"}}
    <link rel="stylesheet" href="/static/css/error.css"/>
{{/block}}
{{block name="errorInfo"}}
    <h1>404</h1>
    <h3>很抱歉，没有找到要访问的页面，{{$code}}: {{$msg}}</h3>
    <p>1、访问的网址可能不正确；</p>
    <p>2、网页可能已被删除、重命名或暂时不可用。</p>
{{/block}}