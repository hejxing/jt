{{extends file='layout\error.tpl'}}
{{block name="errorInfo" append}}
    <ul id="trace">
    {{foreach $trace as $error}}
        <li>{{str_replace(CORE_ROOT, '', $error['file'])}} {{$error['line']}} {{$error['class']}}
            ::{{$error['function']}}</li>
    {{/foreach}}
    </ul>{{/block}}