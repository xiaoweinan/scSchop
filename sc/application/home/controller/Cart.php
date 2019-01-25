<?php
namespace app\home\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Session;
use app\home\controller\Base;

class Cart extends Base
{
    /**
     * 购物车
     */
    public function Cart1()
    {
        $user_id = Session::get('infos.user_id');
        //通过session_id查询对应的商品
        $carts = Db::name('cart')
                            ->alias('c')
                            ->field('c.*,p.img')
                            ->join('__PRODUCT__ p','c.good_id = p.good_id')
                            ->where('user_id',$user_id)
                            ->select();
        //取出商品的规格和图片
        foreach($carts as $key=>$vo){
                $carts[$key]['spec_key_name']= explode(',',$vo['spec_key_name']);
                $carts[$key]['img']= explode(',',$vo['img']);
            }
        $count = Db::name('cart')
        					->where('user_id', $user_id)
                            ->count();
       //if(empty($count)){
        //	$this->error('您的购物车没有商品！', 'Index/index');
        //}
         //调用like方法--实现猜你喜欢
        $like = $this->like();
        $like1 = $this->like();
        $like2 = $this->like();
        $this->assign('like',$like);
        $this->assign('like1',$like1);
        $this->assign('like2',$like2);
        $this->assign('count',$count);
        $this->assign('count',$count);
        $this->assign('carts',$carts);
        return view();
    }
     /**
     * 猜你喜欢
     */
    public function like()
    {   
       $products = Db::name('product')
                    ->select();
        //获得商品的总数
       $product = Db::name('product')
                                ->count();
        //循环四个随机商品
        for ($i = 0; $i <= 3; $i++) {
            $cart = rand(0,$product-1);
            $goods[] = $products[$cart]; 
        }
        return $goods;
    }

    /**
     * 确认订单页面
     */
    public function Cart2()
    {
        $user_id = Session::get('infos.user_id');
        //通过selected来查询购买的商品
        $carts = Db::name('cart')
                            ->alias('c')
                            ->field('c.*,p.img')
                            ->join('__PRODUCT__ p','c.good_id = p.good_id')
                            ->where('user_id',$user_id)
                            ->where('selected',1)
                            ->select();
        if(empty($carts)){
        	$this->redirect('Cart/cart1');
        }
        //商品总金额
        $num = '';
        foreach ($carts as $key => $va) {
                $num += $va['good_num']*$va['good_price'];       
        }
           $members = Db::name('member_addr')
                                       ->where('user_id',$user_id)
                                       ->select();    
           foreach ($members as $key=>$member) {
            //根据user_id获取地址
            $province = Db::name('member_address')
                                        ->where('id',$member['province'])
                                        ->find();
            $city = Db::name('member_address')
                                    ->where('id',$member['city'])
                                    ->find();
            $district = Db::name('member_address')
                                        ->where('id',$member['district'])
                                        ->find();
            //把省市县存进一个数组

            $addrs[$key]['address_id'] = $member['address_id'];
            $addrs[$key]['consignee'] = $member['consignee'];
            $addrs[$key]['phone'] = $member['phone'];
            $addrs[$key]['province'] = $province['name'];
            $addrs[$key]['city'] = $city['name'];
            $addrs[$key]['district'] = $district['name'];
            $addrs[$key]['is_default'] = $member['is_default'];    
           }
            foreach($carts as $key=>$vo){
                $carts[$key]['spec_key_name']= explode(',',$vo['spec_key_name']);
                $carts[$key]['img']= explode(',',$vo['img']);
            }
            $this->assign('num',$num);
            $this->assign('carts',$carts);
            $data = Db::name('member_addr')
    							->where('user_id',$user_id)
    							->select();
         	if(empty($data)){
				return view();
			}else{
				foreach($data as $key=>$vo){
					$province = Db::name('member_address')
						->where('id',$vo['province'])
						->find();
					$city = Db::name('member_address')
						->where('id',$vo['city'])
						->find();
					$district = Db::name('member_address')
						->where('id',$vo['district'])
						->find();
					$addrs[$key]['province'] = $province['name'];
					$addrs[$key]['city'] = $city['name'];
					$addrs[$key]['district'] = $district['name'];
					$addrs[$key]['address_id'] = $vo['address_id'];
					$addrs[$key]['phone'] = $vo['phone'];
					$addrs[$key]['address'] = $vo['address'];
					$addrs[$key]['consignee'] = $vo['consignee'];
					$addrs[$key]['is_default'] = $vo['is_default'];
				}
				$this->assign('addrs',$addrs);
				return view();
			}
            return view();
    }
     /**
     * 新增地址
     */
    public function Car_addr(Request $request)
    {
            if($request->isPost()){
            //新增收货地址
            $data = input('post.');
            $uid = Session::get('infos.user_id');
            $data['user_id'] = $uid;
            if(isset($data['is_default'])==false){
                if(Db::name('member_addr')->insert($data)){
                    return ['code'=>1,'msg'=>'添加地址成功'];
                }
            }else{
                $res = Db::name('member_addr')
                                ->where('user_id',$uid)
                                ->where('is_default',1)
                                ->count();
                if($res){
                    $data['is_default'] = 1;
                    $addr_id = Db::name('member_addr')->insertGetId($data);
                    Db::name('member_addr')
                                    ->where('user_id',$uid)
                                    ->where('address_id','<>',$addr_id)
                                    ->update(['is_default'=>0]);
                    return ['code'=>1,'msg'=>'添加地址成功'];
                }else{
                    $data['is_default'] = 1;
                    $addr_id = Db::name('member_addr')->insertGetId($data);
                    Db::name('member_addr')
                                    ->where('user_id',$uid)
                                    ->where('address_id','<>',$addr_id)
                                    ->update(['is_default'=>0]);
                    return ['code'=>1,'msg'=>'添加地址成功'];
                }
            }
        }
     return view();
    }
    /**
     * 地址删除
     */
    public function addrdel()
    {   
       $id = input('get.id');
       if(Db::name('member_addr')->delete($id)){
               return ['status'=>1];
             }else{
               return ['status'=>0,'msg'=>'删除失败'];
             }
    }
    /**
     * 修改地址默认状态
     */
    public function isDefault()
    {   
       $id = input('post.');
       if(Db::name('member_addr')->delete($id)){
               return ['status'=>1];
             }else{
               return ['status'=>0,'msg'=>'删除失败'];
             }
    }
    /**
     * 商品价格小计
     */
    public function changeNum()
    {   
        $cart = input('cart/a',[]);
        // 通过cart id  到 cart表获取goods_id
        $carts = Db::name('cart')
                            ->where('id',$cart['id'])
                            ->find();
        // 通过goods_id--->商品库存
        $product = Db::name('product')
                                ->where('good_id',$carts['good_id'])
                                ->find();
        // 比较goods_num和库存
        if ($cart['good_num'] < $product['store_num']) {
            if(Db::name('cart')->update($cart) ){
                    $num = $cart['good_num']*$carts['good_price'];
                    return ['status'=>1,'num'=>$num];
                    }
            }else{
            return ['status'=>0,'good_num'=>$product['store_num'],'msg'=>'购买商品数量不能大于库存'];
            }         
    }
    /**
     * 商品价格总计
     */
    public function AsyncUpdateCart()
    {
        $carts = input('cart/a',[]);
        $num = '';
        foreach ($carts as $k=>$vo){
            if ($vo['selected'] == 1) {
                Db::name('cart')->update($vo);
                $cart = Db::name('cart')
                                    ->where('id',$vo['id'])
                                    ->find();
                $num += $cart['good_num']*$cart['good_price'];     
            }else{
               Db::name('cart')->update($vo);
            }
        }
         return ['status'=>1,'total_fee'=>$num];  
    }
    /**
     * 商品删除
     */
    public function delete()
    {
        $cart_ids = input('cart_ids/a',[]);
        foreach ($cart_ids as $value) {
        if(Db::name('cart')->where('id',$value)->delete()){
               return ['status'=>1];
        }else{
               return ['status'=>0];
        }
        }    
    }
    /**
     * 提交订单
     */
    public function order()
    {   
        $user_id = Session::get('infos.user_id');
        // 处理订单数据
        // (1) 用户信息
        $address_id = input('post.address_id');
        // (2) 收货地址信息
        $address = Db::name('member_addr')
                                ->where('address_id', $address_id)
                                ->find();
        // (3) 订单信息
        $order_sn = date('Ymd').time();
        // (4) 订单总价
        //通过selected来查询购买的商品
        $carts = Db::name('cart')
                            ->alias('c')
                            ->field('c.*,c.good_id')
                            ->join('__PRODUCT__ p','c.good_id = p.good_id')
                            ->where('user_id',$user_id)
                            ->where('selected',1)
                            ->select();
        //商品总金额
        $totalprice = '';
        foreach ($carts as $key => $va) {
                $totalprice += $va['good_num'] * $va['good_price'];       
        }
        //订单号
        $order_sn = date('Ymd',time()).time();
        $orderData = [
            'order_sn'      => $order_sn,
            'user_id'       => $user_id,
            'order_status'  => 1,
            'consignee'     => $address['consignee'],
            'province'      => $address['province'],
            'city'          => $address['city'],
            'district'      => $address['district'],
            'address'       => $address['address'],
            'phone'         => $address['phone'],
            'good_price'    => $totalprice,
            'order_amount'  => $totalprice,
            'add_time'      => time() 
        ];
        // 订单入库
        if (Db::name('order')->insert($orderData)) {
            $order_id = Db::name('order')->getLastInsID();
            //把order_id存到session中
            Session::set('order_id',$order_id);
            // 处理订单商品数据
            $order_goods = [];
            foreach ($carts as $key => $va) {
            //商品信息入库
                $order_goods[] = [
                    'order_id'=>$order_id,
                    'good_id'=>$va['good_id'],
                    'good_name'=>$va['good_name'],
                    'good_num'=>$va['good_num'],
                    'final_price'=>$va['good_price'],
                    'spec_key_name'=>$va['spec_key_name']
                ]; 
        }
        // 订单商品入库
        if (Db::name('order_goods')->insertAll($order_goods)) {
        // 清空购物车
        Db::name('cart')->where('selected',1)->delete(); 
              return ['status'=>1]; 
        }else {
             return ['status'=>0];
        }
    	}else {
        return ['status'=>0];
    	}            
}
    /**
     *确认支付页面
     */
    public function Cart4()
    {
        //获取order_id
        $order_id = Session::get('order_id');
        $order = Db::name('order')
                                ->where('order_id',$order_id)
                                ->select();
        $orders = $order[0];
        $totalprice = $orders['order_amount'];
        $user_id = Session::get('infos.user_id');
        Db::name('member')
                    ->where('user_id',$user_id)
                    ->update(['total_price'=>$totalprice]);
        //支付的时间限制
        $time = date('Y-m-d H:i:s',$orders['add_time']+48624);
        $this->assign('time',$time);
        $this->assign('orders',$orders);
        return view();
    }
    /**
    *支付跳转
    */
    public function getCode()
    {
         $order_id = input('post.order_id');
         //根据id获取订单表
         $data['order_id'] = $order_id;
         $data['order_status'] = 1;
         $data['pay_status'] = 1;
         if (Db::name('order')->update($data)) {
             $this->redirect('Cart/successOrder',['order_id' =>$order_id]);
         }else {
             $this->redirect('Cart/errorOrder',['order_id' =>$order_id]);
         }
    }
    /*
     *支付成功
     */
    public function successOrder()
    {
        $order_id = input('order_id');
        $order = Db::name('order')
                            ->where('order_id',$order_id)
                            ->find();
        $this->assign('order',$order);
        $infos = Db::name('order_goods')
        					->where('order_id', $order_id)
        					->select();
        foreach($infos as $info){
        	$good_num = $info['good_num'];
        	Db::name('product')
        					->where('good_id', $info['good_id'])
        					->setDec('store_num', $good_num);
        	Db::name('product')
        					->where('good_id', $info['good_id'])
        					->setInc('sale_num', $good_num);
        }
        return view();
    }
    /*
     *支付失败
     */
    public function errorOrder()
    {
        $order_id = input('order_id');
        $order = Db::name('order')
                            ->where('order_id',$order_id)
                            ->find();
        $this->assign('order',$order);
        return view();
    }
}
