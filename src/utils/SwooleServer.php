<?php
/**
 * User: ax
 * Date: 2016/10/27 16:26
 */

namespace jt\utils;


use jt\Controller;
use jt\Exception;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

class SwooleServer
{
    private static function checkEnvironment()
    {
        //判断是否在Cli模式下运行
        if(PHP_SAPI !== 'cli'){
            exit('Allows you to run only in CLI mode');
        }
        if(!class_exists('\Swoole\Server', false)){
            exit('Need Swoole extension');
        }
    }

    private static function createSer($option)
    {
        $ser = new Server($option['host']??'0.0.0.0', $option['port']??9501, SWOOLE_BASE, SWOOLE_SOCK_TCP);
        $ser->set([
            'worker_num' => $option['worker_num']??1,
            'daemonize'  => $option['daemonize']??true,
            'backlog'    => $option['backlog']??128
        ]);

        return $ser;
    }

    /**
     * 通过Swoole扩展来启动应用
     *
     * @param array $option
     */
    public static function run(array $option = [])
    {
        self::checkEnvironment();
        $ser = self::createSer($option);

        $ser->on('request', function(Request $request, Response $response){
            ob_start();
            $controller = new Controller([
                'SCRIPT_NAME'    => $request->server['request_uri'],
                'REQUEST_METHOD' => $request->server['request_method']
            ], '0');
            try{
                $controller->run();
            }catch(Exception $e){
                echo $e->getMessage();
            }
            $response->header('Content-Type', 'text/html; charset=UTF-8');
            $response->end(ob_get_clean());
        });

        $ser->start();
    }
}