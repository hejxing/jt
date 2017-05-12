<?php
/**
 * User: ax
 * Date: 2016/11/26 14:18
 * @defaultAuth \jt\auth\DeveloperAuth
 *
 * @basePath /menu/
 */

namespace jt\developer\action;


use jt\developer\Base;
use jt\developer\service\PrepareRouter;
use jt\Exception;

class Menu extends Base
{
    private static $authPool = [];

    /**
     * 项目首页
     *
     * @router get /menu mime:html
     */
    public function loseWay()
    {
        $this->redirect($this->getFromData('baseHref').'menu/');
    }

    /**
     * 项目首页
     *
     * @router get *path tpl:/menu/index mime:html
     */
    public function index()
    {
        $this->out('pageTitle', '功能菜单管理');
    }

    /**
     * @param array $method
     * @return int
     * @throws \jt\Exception
     */
    protected function getAuthMode($method)
    {
        $authClass = $method['auth'];
        if (isset(self::$authPool[$authClass])) {
            return self::$authPool[$authClass];
        }
        $mark = '';
        $auth = $authClass;

        if (strpos($authClass, '.') > 0) {
            list($auth, $mark) = explode('.', $authClass, 2);
        }

        /** @var \jt\auth\Auth $auth */
        if (!class_exists($auth)) {
            throw new Exception('Class '.$auth.' not found. Trigger in '.$method['class'].'::'.$method['method'].'. At line '.$method['line']);
        }
        self::$authPool[$authClass] = $mark === 'auto'? 0: $auth::getGrantMode($mark);

        return self::$authPool[$authClass];
    }

    /**
     * 获取Api列表
     *
     * @router get api/list mime:json
     */
    public function getApiList()
    {
        $parsed = PrepareRouter::getParsed();
        $list   = [];
        foreach ($parsed['action'] as $classIndex => $classInfo) {
            $class             = $this->map(['auth' => 'Auth', 'create' => 'Create', 'label' => 'title', 'desc', 'notice'], $classInfo);
            $class['value']    = $classIndex;
            $class['children'] = [];
            if(!$class['label']){
                $class['label'] = $class['value'];
            }
            foreach ($classInfo['methods'] as $methodIndex => $method) {
                if (!$method['methods']){
                    continue;
                }
                if ($method['auth'] === 'public') {
                    continue;
                }
                if ($this->getAuthMode($method) === 0) {
                    continue;
                }

                $item          = $this->map([
                    'value' => '""',
                    'label' => 'name',
                    'auth',
                    'affix',
                    'name',
                    'notice'
                ], $method);
                $item['value'] = implode('|', $method['methods']).':'.$method['uri'];
                if (!$item['label']) {
                    $item['label'] = $item['value'];
                }
                $item['desc']        = implode(';', $method['desc']);
                $class['children'][] = $item;
            }
            if ($class['children']) {
                $list[] = $class;
            }
        }

        return $list;
    }

    /**
     * 获取功能菜单列表
     *
     * @router get list mime:json
     */
    public function getList()
    {
        $file = PROJECT_ROOT.'/'.$this->grantMenuListFile;
        $this->out('maxLevel', $this->maxMenuLevel);
        $this->out('mixing', $this->mixing);
        $data = [
            'list'    => [],
            'feature' => []
        ];

        if (file_exists($file)) {
            $data = require($file);
        }

        return ['list' => $data['list']];
    }

    /**
     * 保存功能菜单
     *
     * @param \jt\Requester $body
     * @router put list mime:json
     */
    public function saveList($body)
    {
        $file = PROJECT_ROOT.'/'.$this->grantMenuListFile;
        $dir  = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $now     = date('Y-m-d H:i:s');
        $content = <<<E_DOC
<?php
/**
* last-modify: {$now}
* 框架自动生成，不建议手工编写
*/
return 
E_DOC;
        $content .= var_export($body->get('data'), true).';';
        file_put_contents($file, $content, LOCK_EX);
    }
}