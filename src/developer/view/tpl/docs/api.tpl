{{extends './layout.tpl'}}
{{block 'body-header'}}接口名称: {{$api.name}}{{/block}}
{{block 'body'}}
    <!-- Default panel contents -->
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
        <p><span class="content-label">请求路径:</span><a class="uri" href="//{{$host}}{{$api.uri}}" target="_blank">{{$api.uri}}</a> <a
                    href="../../../../dist"
                    class="host">[host]</a>
        </p>
        <p><span class="content-label">响应类型:</span>{{$mime}}</p>
        <p><span class="content-label">支持版本:</span>1.0</p>
    </div>
    <div class="content-box less-important">
        <div class="title">服务端信息:</div>
        <div class="content">
            <p><span class="content-label">控制器:</span>{{$classAssets}}::{{$api.method}}</p>
            <p><span class="content-label">权限控制:</span>{{$api.auth}}</p>
            <p><span class="content-label">源代码:</span>{{$scriptFile}} at Line {{$api.line}}</p>
            <p><span class="content-label">修改时间:</span>{{$lastModifyTime}}</p>
        </div>
    </div>
    {{function drawLine($param,$level=0)}}
        {{if !isset($param.nodes)}}
            {{php}}return;{{/php}}
        {{/if}}
        <ul>
            {{foreach $param.nodes as $node}}
                <li class="list-item">
                <span class="name" style="padding-left:{{$level * 32 + 58}}px;">{{$node.name}}:</span>
                <span class="desc">[{{$node.ruler.rule}}] {{$node.desc}}</span>
                {{if isset($node.nodes)}}
                    <span class="expand" title="收起" style="margin-left:{{$node.level * 32 + 30}}px;">-</span>
                    {{void drawLine($node, $level+1)}}
                {{/if}}
                </li>{{/foreach}}
        </ul>
    {{/function}}
    <div class="content-box item-list-box">
        <ul class="wrap">
            <li>
                <div class="title">
                    <span class="expand" title="收起">-</span>
                    <span class="param-type-label">请求参数:</span>
                </div>
                {{foreach $params as $name => $param}}
                    <ul class="sub-box">
                        <li>
                            <div class="sub-title">
                                <span class="expand" title="收起">-</span>
                                <span class="param-type-label">{{$name}}:[{{$param.ruler.type||''}}] {{$param.nodes.desc||''}}</span>
                            </div>
                            {{void drawLine($param)}}
                        </li>
                    </ul>
                {{/foreach}}
            </li>
        </ul>
        <ul class="wrap">
            <li>
                <div class="title">
                    <span class="expand" title="收起">-</span>
                    <span class="param-type-label">响应内容:[{{$api.return.ruler.type||''}}] {{$api.return.desc||''}}</span>
                </div>
                {{void drawLine($api.return)}}
            </li>
        </ul>
    </div>
    <script>
        $('.content-box').find('.list-item').each(function(i){
            if(i % 2 === 0){
                $(this).addClass('gray-background');
            }
        });
        $('.content-box .expand').click(function(){
            var trigger = $(this);
            trigger.toggleClass('disabled');
            trigger.text(trigger.is('.disabled')? '+': '-');
            trigger.attr('title', trigger.is('.disabled')? '展开': '收起');
            trigger.closest('li').children('ul')[trigger.is('.disabled')? 'hide': 'show'](100);
        });
    </script>
{{/block}}