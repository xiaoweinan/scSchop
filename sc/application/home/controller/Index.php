<?php

namespace app\home\controller;

use think\Controller;
use think\Request;
use think\Db;

class Index extends Base
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //所有商品表
        $products = Db::name('product')
                            ->alias('p')
                            ->join('product_category c','p.cat_id = c.id')
                            ->select();
        $this->assign('products',$products);
        //广告显示:商品分类的广告
        $adverall = Db::name('adver')
                                ->alias('a')
                                ->join('adver_position p','a.pid = p.position_id')
                                ->order('ad_id','desc')
                                ->where('position_name','商品分类的广告')
                                ->select();
        $this->assign('adverall',$adverall);
        //广告显示:商品分类的广告的小广告
        $adsm = Db::name('adver')
                                ->alias('a')
                                ->join('adver_position p','a.pid = p.position_id')
                                ->order('ad_id','desc')
                                ->where('position_name','商品分类的广告的小广告')
                                ->select();
        $this->assign('adsm',$adsm);
        //广告显示:滚动的广告
        $adver5 = Db::name('adver')
                                ->alias('a')
                                ->join('adver_position p','a.pid = p.position_id')
                                ->order('ad_id','desc')
                                ->where('position_name','滚动的广告')
                                ->limit(5)
                                ->select();
        $this->assign('adver5',$adver5);
        //广告显示:导航栏下右边的两张小图
        $adver2 = Db::name('adver')
                                ->alias('a')
                                ->join('adver_position p','a.pid = p.position_id')
                                ->order('ad_id','desc')
                                ->where('position_name','导航栏下右边的两张小图')
                                ->limit(2)
                                ->select();
        $this->assign('adver2',$adver2);
        //广告显示:滚动广告下的三张广告图
        $adver3 = Db::name('adver')
                                ->alias('a')
                                ->join('adver_position p','a.pid = p.position_id')
                                ->order('ad_id','desc')
                                ->where('position_name','滚动广告下的三张广告图')
                                ->limit(3)
                                ->select();
        $this->assign('adver3',$adver3);
        //广告显示:滚动广告下的三张广告图下的长图
        $adver1 = Db::name('adver')
                                ->alias('a')
                                ->join('adver_position p','a.pid = p.position_id')
                                ->order('ad_id','desc')
                                ->where('position_name','滚动广告下的三张广告图下的长图')
                                ->limit(1)
                                ->select();
        $this->assign('adver1',$adver1);
/*
        $adall = Db::name('adver')
                        ->alias('a')
                        ->join('adver_position p','a.pid = p.position_id')
                        ->where('position_name','导航栏下右边的两张小图')
                        ->order('ad_id','desc')
                        ->select();
        $this->assign('adall',$adall);
        $advers = Db::name('adver')->alias('a')->join('adver_position p','a.pid = p.position_id')
                        ->order('ad_id','desc')
                        ->select();
        $adall =[];
        foreach($advers as $adver){
            $adall[$adver['position_name']][] =$adver['ad_code'];
        }

        $this->assign('adall',$adall);
        print_r($adall);*/

        return view();
    }
    /**
     * 数组处理  无限分类
     * @param  [type]  $arr       [description]
     * @param  integer $parent_id [description]
     * @return [type]             [description]
     */
    public function recursiveCategory($arr,$parent_id=0){
        //无限循环分类（递归分类）
        static $array = [];
        foreach ($arr as $value) {
            if($value['parent_id'] == $parent_id){
                $array[] = $value;
                $this->recursiveCategory($arr,$value['id']);
            }
        }
        return $array;
    }
    /**
     * 文章跳转
     *
     * @return \think\Response
     */
    public function articleDetail(Request $request)
    {
        $id = input('');
        $this->redirect('Article/articleDetail', ['article_id' => $id['article_id']]);
    }
    /**
     * 外部下部分的友情链接
     *
     * @return \think\Response
     */
    public function ProductList(Request $request)
    {
        $id = input('');
        $this->redirect('Product/ProductList', ['cat_id' => $id['id']]);
    }

}
