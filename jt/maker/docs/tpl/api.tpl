{{extends file="./layout.tpl"}}
{{block name="body"}}
    <!-- Default panel contents -->
    <div class="panel-heading">接口名称: {{$api.name}}</div>
    {{if $api.desc}}
        <div class="box-wrap">
            <div class="title">说明:</div>
            <div class="content">{{foreach $api.desc as $desc}}
                <p>{{$desc}}</p>
            </div>{{/foreach}}
        </div>
    {{/if}}
    {{if $api.notice}}
        <div class="box-wrap notice">
            <div class="title">注意:</div>
            <div class="content">{{$api.notice}}</div>
        </div>
    {{/if}}
    <div class="content-box highlight">
        <p><span class="content-label">请求方法:</span>{{$action}}</p>
        <p><span class="content-label">请求路径:</span>{{$api.uri}} <a href="./" class="host">[host]</a></p>
        <p><span class="content-label">响应类型:</span>{{$mime}}</p>
        <p><span class="content-label">支持版本:</span>1.0</p>
    </div>
    <div class="content-box less-important">
        <div class="title">服务端信息:</div>
        <div class="content">
            <p><span class="content-label">控制器:</span>{{$classAssets}}::{{$api.method}}</p>
            <p><span class="content-label">源代码:</span>{{$scriptFile}} at Line {{$api.line}}</p>
            <p><span class="content-label">修改时间:</span>{{$lastModifyTime}}</p>
        </div>
    </div>
    <div class="content-box">
        <div class="title">请求参数:</div>
        {{foreach $params as $name => $param}}
            <dl class="sub-box">
                <dt class="sub-title">{{$name}}:</dt>
                {{foreach $param.nodes as $node}}
                    <dd class="list-item"><span class="name" data-level="{{$node.level}}" style="padding-left:{{$node.level * 32}}px;">{{$node.name}}:</span><span class="desc">[{{$node.ruler.raw}}] {{$node.desc}}</span></dd>
                {{/foreach}}
            </dl>
        {{/foreach}}
    </div>
    <dl class="content-box">
        <dt class="title">响应内容:</dt>
        {{foreach $return as $node}}
            <dd class="list-item"><span class="name" data-level="{{$node.level}}" style="padding-left:{{$node.level * 32}}px;">{{$node.name}}:</span><span class="desc">[{{$node.ruler.raw}}] {{$node.desc}}</span></dd>
        {{/foreach}}
    </dl>
{{/block}}