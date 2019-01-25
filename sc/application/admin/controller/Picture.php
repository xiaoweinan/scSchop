<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use think\Request;
class Picture extends Base
{
    /**
     * 后台图片管理
     */
    public function pictureList()
    {
        return view();
    }
    /**
     * 后台添加图片
     */
    public function pictureAdd()
    {
        return view();
    }
}