<?php
/**
 * @Copyright jentian.com
 * Auth: hejxi
 * Create: 2016/3/16 16:52
 */
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>api文档</title>
	<base href="/"/>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- 新 Bootstrap 核心 CSS 文件 -->
	<link rel="stylesheet" href="//api_docs.test.csmall.com/bootstrap/css/bootstrap.min.css">

	<!-- 可选的Bootstrap主题文件（一般不用引入） -->
	<link rel="stylesheet" href="//api_docs.test.csmall.com/bootstrap/css/bootstrap-theme.min.css">
	<link rel="stylesheet" href="//api_docs.test.csmall.com/css/api.css">
	<!-- jQuery文件。务必在bootstrap.min.js 之前引入 -->
	<script src="//api_docs.test.csmall.com/bootstrap/js/jquery.min.js"></script>
	<script src="//api_docs.test.csmall.com/js/gototop.js"></script>
	<script>
		var jq = $.noConflict();
		//返回类型参数表格展示隐藏
		function createUuid(){
			var str = '';
			for(var i= 0 ; i < 32; i++){
				str += parseInt(Math.random()*10);
			}
			return str;
		}
		function toSwitch(obj) {
			var _this = jq(obj);
			if (!_this.attr('uuid')){
				var _table = _this.next('.table');
				var td = jq("<td colspan='3'></td>").append(_table);
				td.css({
					'borderLeft' : '2px dashed gray',
					'borderRight' : '1px solid #fff'
				});
				var tr = jq("<tr></tr>").append(td);
				var uuid = createUuid();
				_this.attr('uuid', uuid);
				tr.attr('id', uuid);
				tr.insertAfter(_this.closest('tr'));
			}
			_table = jq("#"+_this.attr('uuid')+'>td>.table');

			if (_table.hasClass('none')) {
				jq("#"+_this.attr('uuid')).slideDown();
				_this.attr('title', '点击关闭');
			} else {
				jq("#"+_this.attr('uuid')).slideUp();
				_this.attr('title', '点击打开');
			}
			_table.toggleClass('none');
		}
	</script>
</head>
<body>
<div class="container-fluid">
	<div class="container-fluid">
		<div class="navbar-header">
			<a class="navbar-brand" href="/api">API接口文档
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
	<div class="row" ng-controller="mainCtrl">
		<!-- 导航 -->
		<div ng-include="navUrl"></div>
		<!-- 内容 -->
		<div ui-view></div>
	</div>
	<p class="text-center">copyright@csmall金猫银猫</p>

	<p class="text-center">power by 井田云</p>
</div>
<div style="display: none;" id="rocket-to-top">
	<div style="opacity:0;display: block;" class="level-2"></div>
	<div class="level-3"></div>

	<script src="//api_docs.test.csmall.com/static/bootstrap/js/bootstrap.min.js"></script>
	<script src="//api_docs.test.csmall.com/angular/angular.js"></script>
	<script src="//api_docs.test.csmall.com/angular/angular-ui-router.js"></script>
	<script src="//api_docs.test.csmall.com/app/app.js"></script>
</div>
</body>
</html>
