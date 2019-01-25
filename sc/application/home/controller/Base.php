<?php
namespace app\home\controller;

use think\Controller;
use think\Request;
use think\Session;
use think\Db;

class Base extends Controller
{
    public function _initialize()
    {
        //显示各级分类
        $cates = Db::name('product_category')->select();
        $cate1 = Db::name('product_category')
            ->where('level',1)
            ->where('is_show',1)
            ->select();
        $cate2 = Db::name('product_category')
            ->where('level',2)
            ->select();
        $cate3 = Db::name('product_category')
            ->where('level',3)
            ->select();
        $ishot = Db::name('product_category')
            ->where('level',2)
            ->where('is_hot',1)
            ->select();
        $this->assign('cates',$cates);
        $this->assign('cate1',$cate1);
        $this->assign('cate2',$cate2);
        $this->assign('cate3',$cate3);
        $this->assign('ishot',$ishot);
        //文章
        $articlecats = Db::name('article_classfiy')->where('show_in_nav',1)->select();
        $this->assign('articlecats',$articlecats);
        $articles = Db::name('article') ->select();
        $this->assign('articles',$articles);
        //友情链接
        $friends = Db::name('friend_link')->select();
        $this->assign('friends',$friends);
        //配置项
        $config = Db::name('system_config')->select();
        $cf = [];
        foreach($config as $con){
            $cf[$con['varname']] = $con['value'];
        }
        $this->assign('cf',$cf);

        // 搜索排名
        $search_ranks = Db::name('search')
        							->order('search_num desc')
        							->limit(5)
        							->select();
        $this->assign('search_ranks', $search_ranks);
        //显示全部商品数量
        $user_id = Session::get('infos.user_id');
         $count = Db::name('cart')
                  ->where('user_id', $user_id)
                            ->count();
        $this->assign('count',$count);
    }
}