{{extends './layout.tpl'}}
{{block 'body-header'}}Page Not Found{{/block}}
{{block 'body'}}
    <div class="panel">
        <div class="panel-body">
            <h1>{{$code}}</h1>
            <h2>{{$msg}}</h2>
        </div>
    </div>
{{/block}}