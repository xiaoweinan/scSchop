<?php

namespace app\home\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Session;
use app\home\controller\Base;

class Product extends Base
{
    /**
     * 商品列表
     */
    public function productList()
    {   
        //从外部获取id
        $cat_id = input('cat_id');
        //头部全部列表的搜索
        // if($cat_id==0){}
		//        数据库的查询
        $cat = Db::name('product_category')
                            ->find($cat_id);
        $this->assign('c_name', $cat['name']);
		//        如果等级为1
        if($cat['level'] == 1){
            $cats = Db::name('product_category')
                            ->where('parent_id', $cat_id)
                            ->select();
			// 声明数组ids
            $ids = [];
			//声明names
            $names = [];
			//遍历数据库等级为1的
            foreach($cats as $vo){

                $cat_3s = Db::name('product_category')
                            ->where('parent_id', $vo['id'])
                            ->select();
                foreach($cat_3s as $v){
                    $ids[] = $v['id'];
                    $names[] = $v['name'];
                }
            }
        }elseif($cat['level'] == 2){
            $cats = Db::name('product_category')
                            ->where('parent_id', $cat_id)
                            ->select();
            $ids = [];
            $names = [];
            foreach($cats as $vo){
                $ids[] = $vo['id'];
                $names[] = $vo['name'];
            }             
        }else{
            $ids = $cat_id;
            $cats = Db::name('product_category')
                            ->find($ids);
            $names = [$cats['name']];
        }
        // 品牌
        $brands = Db::name('brand')
                            ->where('cat_id', 'in', $ids)
                            ->select();
        // 产品
        $goods = Db::name('product')
                            ->where('cat_id', 'in', $ids)
                            ->select();
        // 图片
        foreach($goods as $key=>$good){
            $img = explode(',', $good['img']);
            $goods[$key]['img'] = $img;
        }
        // 推荐热卖
        $hots = Db::name('product')
                            ->where('is_hot', '1')
                            ->order('click_num desc')
                            ->limit(5)
                            ->select();
        // 图片
        foreach($hots as $key=>$hot){
            $img = explode(',', $hot['img']);
            $hots[$key]['img'] = $img;
        }

        $this->assign('hots', $hots);
        $this->assign('goods', $goods);
        // 分类名称
        $this->assign('names', $names);
        $this->assign('brands', $brands);
		//规格筛选 start
        $items = Db::name('product')
            ->alias('p')
            ->join('product_category c','c.id = p.cat_id')
            ->join('product_spec s ','s.type_id = p.type_id')
            ->join('product_spec_item i','i.spec_id = s.sp_id')
            ->where('c.id',$cat_id)
            ->select();
        /*$ite = Db::name('product_spec')
            ->alias('s')
            ->join('product_spec_item i','s.sp_id = i.spec_id')
            ->*/
        $this->assign('items', $items);
        return view();
    }
    /**
     * 品牌列表
     */
    public function brandList(){
    	$brand_id = input('brand_id');
    	// 品牌
        $brands = Db::name('brand')
                            ->where('brand_id', 'in', [$brand_id])
                            ->select();
        // 产品
        $goods = Db::name('product')
                            ->where('brand_id', $brand_id)
                            ->select();
        // 图片
        foreach($goods as $key=>$good){
            $img = explode(',', $good['img']);
            $goods[$key]['img'] = $img;
        }
        // 推荐热卖
        $hots = Db::name('product')
                            ->where('is_hot', '1')
                            ->order('click_num desc')
                            ->limit(5)
                            ->select();
        // 图片
        foreach($hots as $key=>$hot){
            $img = explode(',', $hot['img']);
            $hots[$key]['img'] = $img;
        }

        $this->assign('hots', $hots);
        $this->assign('goods', $goods);
        $this->assign('brands', $brands);
        return view();
    }
    /**
     * 价格筛选列表
     */

    public function priceList(){
    	
    	if(input('price')){
    		$price = explode(',', input('price'));
    		$goods = Db::name('product')
                            ->where('shop_price', '>', $price[0])
                            ->where('shop_price', '<', $price[1])
                            ->select();
  
    	}else{
    		$start_price = input('post.start_price');
    		$end_price = input('post.end_price');
    		$goods = Db::name('product')
                            ->where('shop_price', '>', $start_price)
                            ->where('shop_price', '<', $end_price)
                            ->select();
    	}      
        // 图片
        foreach($goods as $key=>$good){
            $img = explode(',', $good['img']);
            $goods[$key]['img'] = $img;
        }
        // 推荐热卖
        $hots = Db::name('product')
                            ->where('is_hot', '1')
                            ->order('click_num desc')
                            ->limit(5)
                            ->select();
        // 图片
        foreach($hots as $key=>$hot){
            $img = explode(',', $hot['img']);
            $hots[$key]['img'] = $img;
        }

        $this->assign('hots', $hots);
        $this->assign('goods', $goods);
   
        return view();
    }
    /**
     * 排序列表
     */
    public function orderList(){
    	$order = input('order');
    	if($order == 'sale_num'){
    		// 产品
        	$goods = Db::name('product')
                            ->order('sale_num desc')
                            ->select();
    	}elseif($order == 'shop_price'){
    		// 产品
        	$goods = Db::name('product')
                            ->order('shop_price asc')
                            ->select();
    	}elseif($order == 'comment_num'){
    		// 产品
        	$goods = Db::name('product')
                            ->order('comment_num desc')
                            ->select();
    	}else{
    		// 产品
        	$goods = Db::name('product')
                            ->where('is_hot', 1)
                            ->select();
    	}      
        // 图片
        foreach($goods as $key=>$good){
            $img = explode(',', $good['img']);
            $goods[$key]['img'] = $img;
        }
        // 推荐热卖
        $hots = Db::name('product')
                            ->where('is_hot', '1')
                            ->order('click_num desc')
                            ->limit(5)
                            ->select();
        // 图片
        foreach($hots as $key=>$hot){
            $img = explode(',', $hot['img']);
            $hots[$key]['img'] = $img;
        }

        $this->assign('hots', $hots);
        $this->assign('goods', $goods);
        return view();
    }
    /**
     * 商品详情
     */
    public function productDetails(){
        $good_id = input('good_id');
        if(!empty($good_id)){
            $good_click_num = Db::name('product')->field('click_num')->find($good_id);
            $click = $good_click_num['click_num']+1;
            $data = ['click_num'=>$click];
            Db::name('product')->where('good_id',$good_id)->update($data);
        }
        // 产品
        $good = Db::name('product a')
                            ->field('a.*,b.brand_name')
                            ->join('brand b', 'a.brand_id = b.brand_id')
                            ->find($good_id);
        // 分类
        $cat_id = $good['cat_id'];
        $cat_3 = Db::name('product_category')
                            ->find($cat_id);
        $cat_3_name = $cat_3['name'];
        $cat_2 = Db::name('product_category')
                            ->where('id', $cat_3['parent_id'])
                            ->find();
        $cat_2_name = $cat_2['name'];
        $cat_1 = Db::name('product_category')
                            ->where('id', $cat_2['parent_id'])
                            ->find();
        $cat_1_name = $cat_1['name'];
        $this->assign([
            'cat_3_name'=>$cat_3_name,
            'cat_2_name'=>$cat_2_name,
            'cat_1_name'=>$cat_1_name,
        ]);
        // 属性
        $attrs = Db::name('product_attribute a')
                            ->field('a.attr_name, b.attr_value')
                            ->join('product_attr b', 'a.attr_id=b.attr_id')
                            ->where('b.good_id', $good_id)
                            ->select();
        $this->assign('attrs', $attrs);

        // 图片
        $img = explode(',', $good['img']);
        $good['img'] = $img;
        // 商品规格
        $items = Db::name('product_item a')
                            ->join('product_spec_item b', 'a.item_id=b.id')
                            ->join('product_spec c', 'b.spec_id=c.sp_id')
                            ->field('c.sp_name, b.item')
                            ->where('good_id', $good_id)
                            ->select();
        $specs = [];
        foreach($items as $vo){
            $specs[$vo['sp_name']][] = $vo['item'];
        }
        // 热搜推荐
        $sea = Db::name('search')
        					->order('search_num desc')
        					->limit(6)
        					->select();
        $this->assign('sea', $sea);

        // 推荐热卖
        $hots = Db::name('product')
                            ->where('is_hot', '1')
                            ->order('sale_num desc')
                            ->limit(5)
                            ->select();
        // 图片
        foreach($hots as $key=>$hot){
            $img = explode(',', $hot['img']);
            $hots[$key]['img'] = $img;
        }
		$this->assign('hots', $hots);

        // 看了又看
        $looks = Db::name('product')
        					->order('click_num desc')
        					->select();
        // 图片
        foreach($looks as $key=>$look){
            $img = explode(',', $look['img']);
            $looks[$key]['img'] = $img;
        }
       	$this->assign('looks', $looks);

        // 评论
        $all_c = Db::name('comment')
            ->where('good_id', $good_id)
            ->count();
        $good_c = Db::name('comment')
            ->where('good_id', $good_id)
            ->where('goods_rank', 'in', [4,5])
            ->count();
        $mid_c = Db::name('comment')
            ->where('good_id', $good_id)
            ->where('goods_rank', 3)
            ->count();
        $img_c = Db::name('comment')
            ->where('good_id', $good_id)
            ->where('img', '<>', '')
            ->count();
        $this->assign([
            'all_c'=>$all_c,
            'good_c'=>$good_c,
            'mid_c'=>$mid_c,
            'bad_c'=>($all_c-$good_c-$mid_c),
            'img_c'=>$img_c
        ]);
        // dump($specs);
        $this->assign('specs', $specs);
        $this->assign('good', $good);
        return view();
    }
    /**
     * 评论
     */
    public function ajaxComment(){
        $commentType = input('commentType');
        $good_id = input('good_id');
        if($commentType == 1){
            // 全部评论
            $list = Db::name('comment')
                ->where('good_id', $good_id)
                ->select();

        }elseif($commentType == 2){
            // 好评
            $list = Db::name('comment')
                ->where('good_id', $good_id)
                ->where('goods_rank', 'in', [4,5])
                ->select();
        }elseif($commentType == 3){
            // 中评
            $list = Db::name('comment')
                ->where('good_id', $good_id)
                ->where('goods_rank', 3)
                ->select();
        }elseif($commentType == 4){
            // 差评
            $list = Db::name('comment')
                ->where('good_id', $good_id)
                ->where('goods_rank', 'in', [1,2])
                ->select();
        }else{
            // 有图
            $list = Db::name('comment')
                ->where('good_id', $good_id)
                ->where('img', '<>', '')
                ->select();
        }
        $this->assign('list', $list);
        return view();
    }
    /**
     * 加入购物车
     */
    public function addCart(){
        $data = input('post.');

       if(!Session::get('infos')){
           $this->redirect('login/login');
       }

        $spec = '';
        foreach($data as $k=>$v){
            if(preg_match("/[\x7f-\xff]/", $k)){
                $spec .= $k.':'.$v.',';
                unset($data[$k]);
            }
        }
        $data['spec_key_name'] = substr($spec, 0, -1);
        $data['add_time'] = time();
        $data['user_id'] = Session::get('infos.user_id');
        $rs = Db::name('cart')
                            ->where('user_id', $data['user_id'])
                            ->where('good_id', $data['good_id'])
                            ->where('spec_key_name', $data['spec_key_name'])
                            ->select();
        if($rs){
            $res = Db::name('cart')
                            ->where('user_id', $data['user_id'])
                            ->where('good_id', $data['good_id'])
                            ->where('spec_key_name', $data['spec_key_name'])
                            ->setInc('good_num', $data['good_num']);
        }else{
            $res = Db::name('cart')
                            ->insertGetId($data);
        }
        if($res){
            return true;
        }
    }
    /**
     * 添加成功
     */
    public function openAddCart(){
        $hots = Db::name('product')
                            ->where('is_hot', 1)
                            ->limit(4)
                            ->select();
        foreach($hots as $key=>$good){
            $img = explode(',', $good['img']);
            $hots[$key]['img'] = $img;
        }
        $this->assign('hots', $hots);
        return view();
    }

    /**
     * 搜索
     */
    public function searchList(){
        $q = input('get.q');
        $res = Db::name('search')
        				->where('search_name', $q)
        				->find();
        if($res){
        	Db::name('search')
        				->where('search_name', $q)
        				->setInc('search_num');
        }else{
        	Db::name('search')
        				->insertGetId(['search_name'=>$q]);
    	}
        
        $this->assign('search', $q);
        $data = Db::name('product')
                            ->select();
        $goods = [];
        foreach($data as $vo){
            if(preg_match('/'.$q.'/i', $vo['good_name'])){
                $goods[] = $vo;
            }
        }
        // 图片
        foreach($goods as $key=>$good){
            $img = explode(',', $good['img']);
            $goods[$key]['img'] = $img;
        }
        // 推荐热卖
        $hots = Db::name('product')
                            ->where('is_hot', '1')
                            ->limit(5)
                            ->order('click_num desc')
                            ->select();
        // 图片
        foreach($hots as $key=>$hot){
            $img = explode(',', $hot['img']);
            $hots[$key]['img'] = $img;
        }
        $this->assign('goods', $goods);
        $this->assign('hots', $hots);
        return view();
    }
    /*
     *随机查询几条数据    select * from table order by rand() limit 6
     */
}

