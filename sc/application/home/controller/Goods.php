<?php

namespace app\home\controller;

use think\Controller;
use think\Request;

class Goods extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function goodslist()
    {
        //
        return view();
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function goodsinfo()
    {
        //
        return view();
    }
    /**
     * 搜索功能
     *
     * @return \think\Response
     */
    public function search()
    {
        //重定向
        $this->redirect('goodslist', ['cate_id' => 2]);
    }

}
