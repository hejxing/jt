<?php
/**
 * 文档自动生成
 *
 * @defaultAuth public
 */

namespace jt\developer\action;

use jt\developer\Base;
use jt\lib\markdown\michelf\Markdown;
use jt\utils\mind_tpl\Mind;

class Docs extends Base
{
    protected $parsed    = [];
    protected $classList = [];

    /**
     * 读取所有分类信息
     */
    private function prepareClassList()
    {
        $list      = [];
        $classInfo = [];
        foreach($this->parsed['action'] as $className => $class){
            $className             = explode('action\\', $className, 2)[1];
            $classInfo[$className] = $this->map(['title', 'Auth', 'Create', 'version', 'notice', 'desc'], $class);
            foreach($class['methods'] as $method){
                if(strpos($method['affix'], 'doc_hidden') !== false){
                    continue;
                }
                $path = $method['uri'];
                foreach($method['methods'] as $m){
                    $list[$className][$path][$m] = [
                        'name' => $method['name']
                    ];
                }
                if(!empty($list[$className][$path])){
                    ksort($list[$className][$path]);
                }
            }
            if(!empty($list[$className])){
                ksort($list[$className]);
            }
        }
        ksort($list);
        $this->classList = $list;
        $this->out('pathList', $list);
        $this->out('classInfo', $classInfo);
    }

    /**
     * 项目首页
     *
     * @router get /docs/index mime:html
     */
    public function projectIndex()
    {
        $this->out('projectDesc', $this->readREADME('README.md'));
        $this->controller->setTemplate('/docs/index');
    }

    /**
     * 项目首页
     *
     * @router get /docs
     */
    public function loseWay()
    {
        $this->redirect($this->getFromData('baseHref').'docs/');
    }

    /**
     * 接口详情页
     *
     * @param string $path
     *
     * @router get /docs/*path mime:html
     */
    public function apiDetail($path)
    {
        list($action, $uri) = \explode('/', $path, 2);
        //寻找方法
        foreach($this->parsed['action'] as $classAssets => $class){
            foreach($class['methods'] as $method){
                if($method['uri'] === '/'.$uri && \in_array($action, $method['methods'])){
                    if($method['mime']){
                        $mime = array_intersect($method['mime'], \Config::ACCEPT_MIME);
                    }else{
                        $mime = \Config::ACCEPT_MIME;
                    }
                    $this->collectAssetsInfo($classAssets);
                    $this->clearField($method['return']);
                    $this->outMass([
                        'classAssets' => $classAssets,
                        'class'       => $class,
                        'api'         => $method,
                        'action'      => $action,
                        'mime'        => implode(', ', $mime),
                    ]);
                    $this->tidyParam($method['param'], $method['uri']);
                    $this->setTpl('/docs/api');

                    return;
                }
            }
        }
        $this->status(404);
    }

    private function collectAssetsInfo($className)
    {
        foreach($this->parsed['info'] as $file => $info){
            if($info['class'] === $className){
                $this->outMass([
                    'scriptFile'     => str_replace(PROJECT_ROOT, '', $file),
                    'lastModifyTime' => date('Y-m-d H:i:s', $info['seed'])
                ]);

                return;
            }
        }
    }

    private function clearField(&$method)
    {
        if(isset($method['nodes'])){
            foreach($method['nodes'] as &$node){
                $this->clearField($node);
                $node['ruler']['rule'] = preg_replace('/ field:[^ ]*/', '', $node['ruler']['rule']);
            }
        }
    }

    private function tidyParam(array $method, $uri)
    {
        $pathParam = [];
        $uriParts  = explode('/', $uri);
        foreach($uriParts as $value){
            if(strpos($value, ':') === 0 || strpos($value, '*') === 0){
                $v                  = substr($value, 1);
                $method[$v]['name'] = $v;
                $pathParam[]        = $method[$v];
            }
        }
        $params = [];
        if($pathParam){
            $params['Path']['nodes'] = $pathParam;
        }
        if(isset($method['query']['nodes']) && $method['query']['nodes']){
            $params['QueryString'] = $method['query'];
        }
        if(isset($method['body']['nodes']) && $method['body']['nodes']){
            $params['Body'] = $method['body'];
        }
        if(isset($method['header']['nodes']) && $method['header']['nodes']){
            $params['Header'] = $method['header'];
        }
        if(isset($method['cookie']['nodes']) && $method['cookie']['nodes']){
            $params['Cookie'] = $method['cookie'];
        }

        $this->out('params', $params);
    }

    private function readREADME($file)
    {
        $markerDown = new Markdown();

        return $markerDown->defaultTransform(file_get_contents(PROJECT_ROOT.'/'.$file));
    }

    private function loadParsed()
    {
        $parseFile = RUNTIME_PATH_ROOT.'/cache/router/'.MODULE.'.php';

        return require($parseFile);
    }

    /**
     * @router get opcache/status mime:html tpl:docs/opcache
     */
    public function showOpcache()
    {

    }

    public function init()
    {
        $this->out('projectName', $this->projectName);

        $this->setMime('html');

        $this->parsed = $this->loadParsed();
        $this->prepareClassList();

        $this->controller->getResponder()->setTplEngine(new Mind([
            'basePath' => dirname(__DIR__).'/view/tpl'
        ]));
    }
}