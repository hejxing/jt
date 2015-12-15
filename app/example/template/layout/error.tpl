<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>错误页面</title>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1"/>
</head>
<body>
<div class="container">
    <div id="header"></div>
    <div id="box">
        {{block name="errorInfo"}}<h1>Error</h1>

        <h3>很抱歉，页面发生了错误：</h3>

        <p>错误代码：{{$code}}</p>
        <p>错误描述：{{$msg}}</p>{{/block}}<a class="index" href="/" title="返回首页">去首页&gt;&gt;</a>
    </div>
    <p id="copyright">©2015 ***</p>
</div>
{{block name="javascript"}}{{/block}}
</body>
</html>