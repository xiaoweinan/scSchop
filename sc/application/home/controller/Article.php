<?php

namespace app\home\controller;

use app\home\controller\Base;
use think\Request;
use think\Db;


class Article extends Base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function articleDetail(Request $request)
    {
        $id = input('');
        $datas = Db::name('article')->where($id)->find();
        $this->assign('datas',$datas);
        return view();
    }
    public function detail(Request $request)
    {

    }
}
