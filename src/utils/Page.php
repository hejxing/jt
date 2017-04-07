<?php

namespace jt\utils;

/*
 * 分页类
 */
class Page
{
    /**
     * 总记录
     *
     * @var int
     */
    private $totalCount;
    /**
     * 每页显示多少条
     *
     * @var int
     */
    private $pageSize;
    /**
     * 当前页码
     *
     * @var int
     */
    private $pageIndex;
    /**
     * 总页码
     *
     * @var int
     */
    private $pageCount;
    /**
     * URL地址
     *
     * @var string
     */
    private $url;
    /**
     * 当前页两边保留的页码数
     *
     * @var int
     */
    private $bothNum;

    /**
     * 构造方法初始化
     *
     * @param int $totalCount 总记录
     * @param int $pageSize 每页显示的记录条数
     * @param int $bothNum 当前页两边保留的页码数
     */
    public function __construct($totalCount, $pageSize, $bothNum = 5)
    {
        $this->totalCount = $totalCount? $totalCount: 0;
        $this->pageSize   = $pageSize;
        $this->pageCount  = ceil($this->totalCount / $this->pageSize);
        $this->pageIndex  = $this->setPage();
        $this->url        = $this->setUrl();
        $this->bothNum    = $bothNum;
    }

    /**
     * 获取当前页码
     */
    public function setPage()
    {
        if(!empty($_GET['pageIndex'])){
            if($_GET['pageIndex'] > 0){
                if($_GET['pageIndex'] > $this->pageCount){
                    return $this->pageCount;
                }else{
                    return $_GET['pageIndex'];
                }
            }else{
                return 1;
            }
        }else{
            return 1;
        }
    }

    /**
     * 获取地址
     */
    private function setUrl()
    {
        $url = $this->request_uri();//IIS的$SERVER
        $par = parse_url($url);
        if(isset($par['query'])){
            parse_str($par['query'], $query);
            unset($query['pageIndex']);
            $url = $par['path'].'?'.http_build_query($query).'&';
        }else{
            $url = $par['path'].'?';
        }

        return $url;
    }

    /**
     *
     * @return string
     */
    private function request_uri()
    {
        if(isset($_SERVER['REQUEST_URI'])){
            $uri = $_SERVER['REQUEST_URI'];
        }else{
            if(isset($_SERVER['argv'])){
                $uri = $_SERVER['PHP_SELF'].'?'.$_SERVER['argv'][0];
            }else{
                $uri = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
            }
        }

        return $uri;
    }

    /**
     * 显示记录
     */
    private function showCount()
    {
        return '共'.$this->totalCount.'条记录	共'.$this->pageCount.'页';
    }

    /**
     * 数字目录
     */
    private function pageList()
    {
        $pageList = '';
        for($i = $this->bothNum; $i >= 1; $i--){
            $page = $this->pageIndex - $i;
            if($page < 1){
                continue;
            }
            $pageList .= '<a href="'.$this->url.'pageIndex='.$page.'">'.$page.'</a>';
        }
        $pageList .= '<span class="cur">'.$this->pageIndex.'</span>';
        for($i = 1; $i <= $this->bothNum; $i++){
            $page = $this->pageIndex + $i;
            if($page > $this->pageCount){
                break;
            }
            $pageList .= '<a href="'.$this->url.'pageIndex='.$page.'">'.$page.'</a>';
        }

        return $pageList;
    }

    /**
     * 首页
     */
    private function first()
    {
        if($this->pageIndex > $this->bothNum + 1){
            return ' <a href="'.$this->url.'pageIndex=1">1</a><em>...</em>';
        }
    }

    /**
     * 上一页
     */
    private function prev()
    {
        if($this->pageIndex == 1){
            return '<span class="disabled">◀</span>';
        }

        return '<a href="'.$this->url.'pageIndex='.($this->pageIndex - 1).'">◀</a> ';
    }

    /**
     * 下一页
     */
    private function next()
    {
        if($this->pageIndex == $this->pageCount){
            return '<span class="disabled">▶</span>';
        }

        return '<a href="'.$this->url.'pageIndex='.($this->pageIndex + 1).'">▶</a> ';
    }

    /**
     * 尾页
     */
    private function last()
    {
        if($this->pageCount - $this->pageIndex > $this->bothNum){
            return '<em>...</em><a href="'.$this->url.'pageIndex='.$this->pageCount.'">'.$this->pageCount.'</a> ';
        }
    }

    /**
     * 分页信息
     *
     * @param int $style 分页样式，1为详细的分页视图，2为简洁分页，只显示上下页按钮
     * @return string html
     */
    public function show($style = 1)
    {
        $page = '';
        switch($style){
            case 1:
                if($this->pageCount > 0){
                    $page .= $this->prev();
                    $page .= $this->first();
                    $page .= $this->pageList();
                    $page .= $this->last();
                    $page .= $this->next();
                }
                $page .= $this->showCount();
                break;
            case 2:
                $page .= $this->pageIndex.'/'.$this->pageCount;
                $page .= $this->prev();
                $page .= $this->next();
                break;
            default:
                $this->showPage(1);
                break;
        }

        return $page;
    }
}
