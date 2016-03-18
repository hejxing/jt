{{extends file="./layout.tpl"}}
{{block name="body"}}
	<div class="panel-body">{{$packageDesc}}</div>
	<!-- Default panel contents -->
	<div class="panel-heading">该空间下的类</div>
	<div class="panel-body">
		<p>
		<pre>描述:{{ class.descript }}</pre>
		<ul>
			<li ng-repeat="method in class.method | filter:search">
				<p><a href="javascript:;" ng-click="toAim(method.name)">{{ method.url }}</a></p>
				<footer>{{ method.statement }}</footer>
			</li>
		</ul>
		</p>
		<p>
            <pre>
		<p align="center"  ng-repeat="annotation in class.annotation">{{ annotation.descript }}</p>
		</pre>
		</p>
	</div>

	<table class="table table-bordered" id="{{ method.name }}" ng-repeat="method in class.method | filter:search">
		<tr class="info" name="getList2">
			<th style="width:76px;">接口声明</th>
			<th>{{ method.statement }}</th>
		</tr>
		<tr>
			<td>描述</td>
			<td html-decode txt="{{ method.descript }}">
		</tr>
		<tr>
			<td>请求URL</td>
			<td>{{ method.url }}</td>
		</tr>
		<tr ng-if="method.mode.path !== undefined">
			<td>请求方式</td>
			<td>path</td>
		</tr>
		<tr ng-if="method.mode.path !== undefined">
			<td>参数列表</td>
			<td ng-if="method.mode.path.length != 0">
				<table class="table table-bordered" >
					<tr>
						<th>参数名</th>
						<th>描述</th>
						<th>是否必须</th>
						<th>其他</th>
					</tr>
					<tr ng-repeat="param in method.mode.path">
						<td>{{ param.name }}</td>
						<td>{{ param.descript }}</td>
						<td html-decode txt="{{ param.isRequire }}"></td>
						<td>{{ param.value }}</td>
					</tr>
				</table>
			</td>
			<td ng-if="method.mode.get.length == 0">无</td>
			<!-- <td class="danger">参数不合法</td> -->
		</tr>
		<tr ng-if="method.mode.get !== undefined">
			<td>请求方式</td>
			<td>get</td>
		</tr>
		<tr ng-if="method.mode.get !== undefined">
			<td>参数列表</td>
			<td ng-if="method.mode.get.length != 0">
				<table class="table table-bordered" >
					<tr>
						<th>参数名</th>
						<th>描述</th>
						<th>是否必须</th>
						<th>其他</th>
					</tr>

					<tr ng-repeat="param in method.mode.get">
						<td>{{ param.name }}</td>
						<td>{{ param.descript }}</td>
						<td html-decode txt="{{ param.isRequire }}"></td>
						<td>{{ param.value }}</td>
					</tr>
				</table>
			</td>
			<td ng-if="method.mode.get.length == 0">无</td>
			<!-- <td class="danger">参数不合法</td> -->
		</tr>
		<tr ng-if="method.mode.post !== undefined">
			<td>请求方式</td>
			<td>post</td>
		</tr>
		<tr ng-if="method.mode.post !== undefined">
			<td>参数列表</td>
			<td ng-if="method.mode.post.length != 0">
				<table class="table table-bordered" >
					<tr>
						<th>参数名</th>
						<th>描述</th>
						<th>是否必须</th>
						<th>其他</th>
					</tr>

					<tr ng-repeat="param in method.mode.post">
						<td>{{ param.name }}</td>
						<td>{{ param.descript }}</td>
						<td html-decode txt="{{ param.isRequire }}"></td>
						<td>{{ param.value }}</td>
					</tr>
				</table>
			</td>
			<td ng-if="method.mode.post.length == 0">无</td>
			<!-- <td class="danger">参数不合法</td> -->
		</tr>
		<tr ng-if="method.mode.any !== undefined">
			<td>请求方式</td>
			<td>post</td>
		</tr>
		<tr ng-if="method.mode.any !== undefined">
			<td>参数列表</td>
			<td ng-if="method.mode.any.length != 0">
				<table class="table table-bordered" >
					<tr>
						<th>参数名</th>
						<th>描述</th>
						<th>是否必须</th>
						<th>其他</th>
					</tr>

					<tr ng-repeat="param in method.mode.any">
						<td>{{ param.name }}</td>
						<td>{{ param.descript }}</td>
						<td html-decode txt="{{ param.isRequire }}"></td>
						<td>{{ param.value }}</td>
					</tr>
				</table>
			</td>
			<td ng-if="method.mode.any.length == 0">无</td>
			<!-- <td class="danger">参数不合法</td> -->
		</tr>
		<tr>
			<td>返回类型</td>
			<td html-decode txt="{ method.return }}">
			</td>
		</tr>
	</table>
{{/block}}