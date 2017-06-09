<?php
/**
 * User: ax
 * Date: 2016/11/26 14:18
 * @defaultAuth \jt\auth\DeveloperAuth.menu
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

        if($authClass === 'public'){
            return 0;
        }

        if(isset(self::$authPool[$authClass])){
            return self::$authPool[$authClass];
        }
        $mark = '';
        $auth = $authClass;

        if(strpos($authClass, '.') > 0){
            list($auth, $mark) = explode('.', $authClass, 2);
        }

        /** @var \jt\auth\Auth $auth */
        if(!class_exists($auth)){
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
        $fillLabel       = function($classInfo){
            return ($classInfo['title']?: '').' ['.$classInfo['value'].']';
        };
        $fillMethodLabel = function($method){
            return implode('|', $method['methods']).':'.$method['uri'];
        };
        $fillMethodName  = function($method)use($fillMethodLabel) {
            return ($method['name']?: '').' ['.$method['method'].'] ['.$fillMethodLabel($method).']';
        };
        $parsed          = PrepareRouter::getParsed();
        $list            = [];
        foreach($parsed['action'] as $classIndex => $classInfo){
            $classInfo['value'] = $classIndex;
            $class              = $this->map(['auth' => 'Auth', 'create' => 'Create', 'label' => $fillLabel, 'value', 'desc', 'notice'], $classInfo);

            $class['children'] = [];

            foreach($classInfo['methods'] as $methodIndex => $method){
                if(!$method['methods']){
                    continue;
                }
                $method['auth'] = $this->getAuthMode($method);

                $item                = $this->map([
                    'value' => $fillMethodLabel,
                    'label' => $fillMethodName,
                    'auth',
                    'affix',
                    'name',
                    'notice',
                    'desc'
                ], $method);
                $class['children'][] = $item;
            }
            if($class['children']){
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
    public function getGrantList()
    {
        $data = $this->getGrantMenuData();

        return ['list' => $data['menu']??[]];
    }

    public function getGrantMenuData()
    {
        $file = PROJECT_ROOT.'/'.$this->grantMenuListFile;
        $this->out('maxLevel', $this->maxMenuLevel);
        $this->out('mixing', $this->mixing);
        $data = [];

        if(file_exists($file)){
            $data = require($file);
        }

        return $data;
    }

    public function getAutoGrantList()
    {
        $data = $this->getGrantMenuData();

        return $data['autoGrant'];
    }

    protected function collectAllGrant($menu, &$list){
        foreach($menu as $item){
            if(isset($item['item']) && $item['item']){
                $this->collectAllGrant($item['item'], $list);
            }elseif(isset($item['feature']) && $item['feature']){
                foreach($item['feature'] as $feature){
                    $list[] = $feature['key'];
                    if(isset($feature['depend']) && $feature['depend']){
                        foreach($feature['depend'] as $depend){
                            $list[] = $depend['key'];
                        }
                    }
                }
            }
        }
    }

    public function getAllGrantList(){
        $data = $this->getGrantMenuData();
        $menu = $data['menu'];

        $list = [];
        $this->collectAllGrant($menu, $list);

        return $list;
    }

    protected function collectAutoGrant()
    {
        $parsed = PrepareRouter::getParsed();
        $list   = [];
        foreach($parsed['action'] as $classInfo){
            foreach($classInfo['methods'] as $methodIndex => $method){
                if(!$method['methods']){
                    continue;
                }
                if($this->getAuthMode($method) === 0){
                    $list[] = implode('|', $method['methods']).':'.$method['uri'];
                }
            }
        }

        return $list;
    }

    /**
     * 保存功能菜单
     *
     * @param \jt\Requester $body
     * @router put list mime:json
     */
    public function saveList($body)
    {
        $list    = $body->get('data')['list'];
        $menu = $this->collectInput($list);
        $this->writeMenuList($menu);
    }

    public function freshAutoGrant(){
        $menu = $this->getMenuList();
        $this->writeMenuList($menu);
    }

    public function writeMenuList($menu){
        $file = PROJECT_ROOT.'/'.$this->grantMenuListFile;
        $dir  = dirname($file);
        if(!is_dir($dir)){
            mkdir($dir, 0777, true);
        }
        $data['menu']      = $menu;
        $data['autoGrant'] = $this->collectAutoGrant();

        $now     = date('Y-m-d H:i:s');

        $content = <<<E_DOC
<?php
/**
* last-modify: {$now}
* 框架自动生成，不建议手工编写
*/
return 
E_DOC;
        $content .= var_export($data, true).';';
        file_put_contents($file, $content, LOCK_EX);
        chmod($file, 0777);
    }

    private function collectInput($data)
    {
        $menu = [];
        foreach($data as $group){
            $item = [];
            foreach(['key', 'type', 'icon', 'name', 'to', 'desc'] as $index){
                $item[$index] = $group[$index];
            }
            if(isset($group['item']) && $group['item']){
                $item['item'] = [];
                $item['item'] = $this->collectInput($group['item']);
            }
            if(isset($group['feature']) && $group['feature']){
                $item['feature'] = $this->combFeature($group['feature']);
            }
            $menu[] = $item;
        }

        return $menu;
    }

    private function combFeature($data)
    {
        $list = [];
        foreach($data as $feature){
            $item = [];
            foreach(['name', 'code', 'key', 'value', 'notice', 'desc'] as $index){
                if(!isset($feature['code'])){
                    $feature['code'] = end($feature['value']);
                }
                if(!isset($feature['key'])){
                    $feature['key'] = end($feature['value']);
                }
                $item[$index] = $feature[$index];
            }
            if(isset($feature['depend']) && $feature['depend']){
                foreach($feature['depend'] as $depend){
                    $dt = [];
                    if(!isset($depend['key'])){
                        $depend['key'] = end($depend['value']);
                    }
                    foreach(['name', 'key', 'value'] as $index){
                        $dt[$index] = $depend[$index];
                    }
                    $item['depend'][] = $dt;
                }
            }
            $list[] = $item;
        }

        return $list;
    }

    /**
     * 获取所有功能列表
     *
     * @return array
     */
    public function getMenuList()
    {
        $data = $this->getGrantMenuData();

        return $data['menu'];
    }

    /**
     * 将未在授权列表中的功能去除
     *
     * @param $feature
     * @param $grant
     */
    private function cutFeature(&$feature, $grant)
    {
        foreach($feature as $k => $f){
            if(!in_array($f['key'], $grant)){
                unset($feature[$k]);
            }
        }
    }

    /**
     * 去除未获授权的功能
     *
     * @param array $list
     * @param array $grant
     */
    public function filterByFeature(array &$list, array $grant)
    {
        foreach($list as $key => &$item){
            if(isset($item['feature'])){
                $this->cutFeature($item['feature'], $grant);
                if(empty($item['feature'])){
                    unset($list[$key]);
                }
            }elseif(isset($item['item'])){
                $this->filterByFeature($item['item'], $grant);
                if(empty($item['item'])){
                    unset($list[$key]);
                }
            }else{
                unset($list[$key]);
            }
        }
    }

    /**
     * 根据权限生成菜单
     *
     * @param array $grant
     * @return array
     */
    public function getByGrant(array $grant)
    {
        $grant = $this->fillAutoGrant($grant);
        $list  = $this->getMenuList();

        $this->filterByFeature($list, $grant);

        return $list;
    }

    /**
     * 添加上自动授权的权限
     *
     * @param array $grant
     * @return array
     */
    public function fillAutoGrant(array $grant)
    {
        return array_merge($grant, $this->getAutoGrantList());
    }
}