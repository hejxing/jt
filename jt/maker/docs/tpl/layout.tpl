<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>api文档</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- 新 Bootstrap 核心 CSS 文件 -->
    <link rel="stylesheet" href="//api_docs.test.csmall.com/static/css/bootstrap.min.css">

    <!-- 可选的Bootstrap主题文件（一般不用引入） -->
    <link rel="stylesheet" href="//api_docs.test.csmall.com/static/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="//api_docs.test.csmall.com/static/css/api.css">
    <!-- jQuery文件。务必在bootstrap.min.js 之前引入 -->
    <script src="//api_docs.test.csmall.com/static/js/jquery.js"></script>
    <script src="//api_docs.test.csmall.com/static/js/gototop.js"></script>
</head>
<body>
<div class="container-fluid">
    <div class="container-fluid">
        <div class="navbar-header">
            <a class="navbar-brand" href="/docs/">API接口文档
                <small>Beta</small>
            </a>
        </div>
        <form class="form-inline pull-right" id="form" action="" method="GET">
            <div class="form-group">
                <label for="search"></label>
                <input type="text" class="form-control" placeholder="api名称" ng-model="search">
            </div>
            <a class="btn btn-default" ng-click="find()">搜索api</a>
        </form>
    </div>
    <div class="row">
        <!-- 导航 -->
        <div class="col-md-2 classList">
            <div class="list-group">
                <a href="/docs/" class="list-group-item">{{$projectName}}</a>
            </div>
            <div class="list-group">
                <a href="/docs/package" class="list-group-item">接口列表</a>
                <a href="/docs/package/user" class="list-group-item">用户</a>
                <a href="/docs/class/User" class="list-group-item">--账号管量</a>
            </div>
        </div>
        <!-- 内容 -->
        <div class="col-md-10">
            <div class="panel panel-primary">
                {{block name="body"}}{{/block}}
            </div>
        </div>
    </div>
</div>
<div style="display: none;" id="rocket-to-top">
    <div style="opacity:0;display: block;" class="level-2"></div>
    <div class="level-3"></div>
</div>
<p class="copyright">&copy;2016 csmall.com</p>
<script>
    var localUrl = location.href;
    $('.classList a').each(function(){
        if($(this).prop('href') === localUrl){
            $(this).addClass('active');
            return false;
        }
    });
    //返回类型参数表格展示隐藏
    function createUuid(){
        var str = '';
        for(var i = 0; i < 32; i++){
            str += parseInt(Math.random() * 10);
        }
        return str;
    }
    function toSwitch(obj){
        var _this = $(obj);
        if(!_this.attr('uuid')){
            var _table = _this.next('.table');
            var td = $("<td colspan='3'></td>").append(_table);
            td.css({
                'borderLeft': '2px dashed gray',
                'borderRight': '1px solid #fff'
            });
            var tr = $("<tr></tr>").append(td);
            var uuid = createUuid();
            _this.attr('uuid', uuid);
            tr.attr('id', uuid);
            tr.insertAfter(_this.closest('tr'));
        }
        _table = $("#" + _this.attr('uuid') + '>td>.table');

        if(_table.hasClass('none')){
            $("#" + _this.attr('uuid')).slideDown();
            _this.attr('title', '点击关闭');
        }else{
            $("#" + _this.attr('uuid')).slideUp();
            _this.attr('title', '点击打开');
        }
        _table.toggleClass('none');
    }
</script>
</body>
</html>