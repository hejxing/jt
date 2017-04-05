<?php
/**
 * Auth: ax
 * Date: 2016/11/26 14:18
 *
 * @defaultAuth public
 */

namespace jt\developer\action;


use jt\developer\Base;

class Menu extends Base
{

    /**
     * 项目首页
     *
     * @router get /menu
     */
    public function loseWay()
    {
        $this->redirect($this->getFromData('baseHref').'menu/');
    }

    /**
     * 项目首页
     *
     * @router get /menu/*path tpl:/menu/index
     */
    public function index()
    {
        $this->out('pageTitle', '功能菜单管理');
    }

    /**
     * 获取功能菜单列表
     *
     * @router get /menu/list mime:json
     */
    public function getList()
    {
        $file = PROJECT_ROOT.'/'.$this->grantMenuListFile;
        $this->out('maxLevel', $this->maxMenuLevel);
        $list = [];
        if(file_exists($file)){
            $list = require($file);
        }

        return ['list' => $list];
    }

    /**
     * 保存功能菜单
     *
     * @param \jt\Requester $body
     * @router put /menu/list mime:json
     */
    public function saveList($body)
    {
        $file = PROJECT_ROOT.'/'.$this->grantMenuListFile;
        $dir  = dirname($file);
        if(!is_dir($dir)){
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
        $content .= var_export($body->get('list'), true).';';
        file_put_contents($file, $content, LOCK_EX);
    }
}