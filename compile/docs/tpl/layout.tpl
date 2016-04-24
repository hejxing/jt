<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{$projectName}}</title>
    <base href="{{$baseHref}}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- 新 Bootstrap 核心 CSS 文件 -->
    <link rel="stylesheet" href="//docs-resource.csmall.com/css/bootstrap.min.css">

    <!-- 可选的Bootstrap主题文件（一般不用引入） -->
    <link rel="stylesheet" href="//docs-resource.csmall.com/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="//docs-resource.csmall.com/css/api.css">
    <!-- jQuery文件。务必在bootstrap.min.js 之前引入 -->
    <script src="//docs-resource.csmall.com/js/jquery.js"></script>
</head>
<body>
<div class="container-fluid">
    <div class="navbar-header">
        <a class="navbar-brand" href="./">{{$projectName}}
            <small>Beta</small>
        </a>
    </div>
</div>
<!-- <form class="form-inline pull-right" id="form" action="" method="GET">
    <div class="form-group">
        <label for="search"></label>
        <input type="text" class="form-control" placeholder="api名称" ng-model="search">
    </div>
    <a class="btn btn-default" ng-click="find()">搜索api</a>
</form> -->
<!-- 导航 -->
<div class="body-box">
    <div class="col-md-3 classList">
        {{foreach $pathList as $className => $methods}}
            <div class="collect class-group">
                <div class="list-title list-group-item">
                    <span class="expand disabled" title="展开">+</span>
                    <span href="./class/{{str_replace("\\", '/', $className)}}.html">{{$classInfo.$className.title|default:$className}}</span>
                </div>
                {{foreach $methods as $path => $ms}}
                    {{foreach $ms as $method => $info}}
                        <a class="list-group-item" href="./{{$method}}{{$path}}.html">{{$info.name}}<br>{{$method}} {{$path}}</a>
                    {{/foreach}}
                {{/foreach}}
            </div>
        {{/foreach}}
    </div>
    <!-- 内容 -->
    <div class="col-md-9">
        <div class="panel-heading">{{block name="body-header"}}{{/block}}</div>
        <div class="detail-box">
            {{block name="body"}}{{/block}}
            <p class="copyright">&copy;2016 csmall.com</p>
        </div>
    </div>
</div>
<script>
    var localUrl = location.href;
    $('.classList a').each(function(){
        if($(this).prop('href') === localUrl){
            $(this).addClass('active');
            return false;
        }
    });
    $('a[href^="http"]').each(function(){
        $(this).attr('target', '_blank');
    });

    $('.classList .expand').click(function(){
        var trigger = $(this);
        trigger.toggleClass('disabled');
        trigger.text(trigger.is('.disabled')? '+': '-');
        trigger.attr('title', trigger.is('.disabled')? '展开': '收起');
        trigger.closest('.class-group')[(trigger.is('.disabled')? 'add': 'remove')+'Class']('collect');
    });
</script>
</body>
</html>