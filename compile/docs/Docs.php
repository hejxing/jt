<?php
/**
 * 文档自动生成
 */
namespace jt\compile\docs;

use jt\Action;
use jt\lib\markdown\michelf\Markdown;
use jt\Responder;
use jt\Template;

class Docs extends Action
{
    /**
     * 项目标识
     *
     * @type string
     */
    protected $projectCode = '';
    /**
     * 项目名称
     *
     * @type string
     */
    protected $projectName = '接口文档';
    protected $parsed      = [];
    protected $classList   = [];

    protected function fetch($url)
    {
        $this->out('baseHref', str_replace($url, '', $_SERVER['SCRIPT_NAME']));
        $this->setMime('html');
        $this->out('projectName', $this->projectName);

        $this->out('host', $_SERVER['HTTP_HOST']);
        Responder::setTplEngine(new Template([
            'template_dir'    => __DIR__ . '/tpl',
            'left_delimiter'  => '{{',
            'right_delimiter' => '}}'
        ]));

        $this->parsed = $this->loadParsed();
        $this->prepareClassList();

        if ($url === 'index') {
            $this->projectIndex();
        }else {
            $this->apiDetail($url);
        }
    }

    /**
     * 读取所有分类信息
     */
    private function prepareClassList()
    {
        $list      = [];
        $classInfo = [];
        foreach ($this->parsed['action'] as $className => $class) {
            $className = explode('action\\', $className, 2)[1];
            $classInfo[$className] = $this->map(['title', 'Auth', 'Create', 'version', 'notice', 'desc'], $class);
            foreach ($class['methods'] as $method) {
                if (strpos($method['affix'], 'doc_hidden') !== false) {
                    continue;
                }
                $path = $method['uri'];
                foreach ($method['methods'] as $m) {
                    $list[$className][$path][$m] = [
                        'name' => $method['name']
                    ];
                }
                if (!empty($list[$className][$path])) {
                    ksort($list[$className][$path]);
                }
            }
            if (!empty($list[$className])) {
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
     */
    private function projectIndex()
    {
        $this->out('projectDesc', $this->readREADME('README.md'));
        $this->setTpl('index');
    }

    /**
     * 接口详情页
     *
     * @param $url
     */
    private function apiDetail($url)
    {
        list($action, $uri) = \explode('/', $url, 2);
        //寻找方法
        foreach ($this->parsed['action'] as $classAssets => $class) {
            foreach ($class['methods'] as $method) {
                if ($method['uri'] === '/' . $uri && \in_array($action, $method['methods'])) {
                    if ($method['mime']) {
                        $mime = array_intersect($method['mime'], \Config::ACCEPT_MIME);
                    }else {
                        $mime = \Config::ACCEPT_MIME;
                    }
                    $this->collectAssetsInfo($classAssets);
                    $this->outMass([
                        'classAssets' => $classAssets,
                        'class'       => $class,
                        'api'         => $method,
                        'action'      => $action,
                        'mime'        => implode(', ', $mime),
                    ]);
                    $this->tidyParam($method['param'], $method['uri']);
                    $this->setTpl('api');

                    return;
                }
            }
        }
        $this->status(404);
    }

    private function collectAssetsInfo($className)
    {
        foreach ($this->parsed['info'] as $file => $info) {
            if ($info['class'] === $className) {
                $this->outMass([
                    'scriptFile'     => str_replace(PROJECT_ROOT, '', $file),
                    'lastModifyTime' => date('Y-m-d H:i:s', $info['seed'])
                ]);

                return;
            }
        }
    }

    private function tidyParam(array $method, $uri)
    {
        $pathParam = [];
        $uriParts  = explode('/', $uri);
        foreach ($uriParts as $value) {
            if (strpos($value, ':') === 0 || strpos($value, '*') === 0) {
                $v                  = substr($value, 1);
                $method[$v]['name'] = $v;
                $pathParam[]        = $method[$v];
            }
        }
        $params = [];
        if ($pathParam) {
            $params['Path']['nodes'] = $pathParam;
        }
        if (isset($method['query']) && $method['query']['nodes']) {
            $params['QueryString'] = $method['query'];
        }
        if (isset($method['body']) && $method['body']['nodes']) {
            $params['Body'] = $method['body'];
        }
        if (isset($method['header']) && $method['header']['nodes']) {
            $params['Header'] = $method['header'];
        }
        if (isset($method['cookie']) && $method['cookie']['nodes']) {
            $params['Cookie'] = $method['cookie'];
        }

        $this->out('params', $params);
    }

    private function readREADME($file)
    {
        $markerDown = new Markdown();

        return $markerDown->defaultTransform(file_get_contents(PROJECT_ROOT . '/' . $file));
    }

    private function loadParsed()
    {
        $parseFile = RUNTIME_PATH_ROOT . '/cache/parse/' . MODULE . '.php';

        return require($parseFile);
    }

    protected function showOpcache()
    {
        $this->setMime('html');
        require __DIR__ . '/tpl/opcache.tpl';
    }
}