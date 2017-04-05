{{extends './layout.tpl'}}

{{block 'body-header'}}项目介绍{{/block}}

{{block 'body'}}
    <div class="panel">
        <div class="panel-body">{{$projectDesc}}</div>
    </div>
{{/block}}