{{extends file='layout\error.tpl'}}
{{block name="cssJs"}}
    <link rel="stylesheet" href="/css/error.css"/>
{{/block}}
{{block name="errorInfo"}}
    <h1>您未登录</h1>
    <h3>当前页面需要登录用户才能访问，请您先 <a href="{{URL_LOGIN}}" title="登录小银袋">登录</a></h3>
    <p id="downTimerBar"><span id="downTimer">-</span> 秒后将自动转到登录页</p>
{{/block}}
{{block name="javascript"}}
    <script>
        var delay = 5;
        var timer = setInterval(function(){
            if(delay == 0){
                clearInterval(timer);
                window.location.href = "{{URL_LOGIN}}";
            }
            freshTimer(delay);
            delay--;
        }, 1000);
        function freshTimer(delay){
            document.getElementById("downTimer").innerHTML = '' + delay;
        }
        freshTimer(delay);
    </script>
{{/block}}