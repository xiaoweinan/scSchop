<?php
namespace app\admin\controller;
use think\Request;
use think\Db;
use think\Pagination;
use app\Admin\controller\Base;

class Order extends Base
{
    /**
     * 订单管理
     */
    public function OrderList()
    {
        $where=[];
        $condition=[];
        //搜索关键字处理
        $order_sn = input('get.keyword');
        if (!empty($order_sn)){
            $where['order_sn'] = ['like',"%{$order_sn}%"];
        }
         $condition['keyword']= !empty($order_sn) ? $order_sn : '';       
        //搜索开始日期
        $start_date = input('get.start_date');
        $start_date = !empty($start_date) ? strtotime($start_date."00:00:00") : 0;
        $condition['start_date'] = !empty($start_date) ? date("Y-m-d",$start_date) : '';
        //搜索结束日期
        $end_date = input('get.end_date');
        $end_date = !empty($end_date) ? strtotime($end_date."23:59:59") : time();
        $condition['end_date'] = !empty($end_date) ? date("Y-m-d",$end_date) : '';
        // 日期条件
        $where['add_time'] = ['between',[$start_date,$end_date]];
        //支付状态
        $pay_status = input('pay_status');
        if($pay_status != ''){
            $where['pay_status'] = $pay_status;
        }
        $condition['pay_status'] = $pay_status;
        //发货状态
        $shipping_status = input('shipping_status');
        if ($shipping_status != ''){
            $where['shipping_status'] = $shipping_status;   
        } 
        $condition['shipping_status'] = $shipping_status;       
        //订单状态
        $order_status = input('order_status');
        if ($order_status != '') {
            $where['order_status'] = $order_status;
        }    
        $condition['order_status'] = $order_status;
        // 分页查询
        $order = Db::name('order')
                            ->alias('o')
                            ->field('o.*,s.shipping_name')
                            ->join('__SHIPPING__ s','o.shipping_id = s.shipping_id')
                            ->where($where)
                            ->order('order_id ASC')
                            ->paginate(10,false,['query'=>$condition]);
                            //echo Db::name('order')->getlastsql();
                            //exit;
        $page = $order->render();
        // 订单总数
        $count = Db::name('order')
                            ->count(); 
        return view('',['order'=>$order,'count'=>$count,'condition'=>$condition,'page'=>$page]);
    }
    /**
     *编辑订单
     */
    public function  orderEdit(Request $request)
    {
        //判断是否是post传值
        if($request->isPost()){
            $data = input('post.');
            //检校是否付款
            if($data['pay_status'] == 0)
                {
                    return ['status'=>0,'msg'=>'请先付款！'];
                }
            //数据入库
            if(Db::name('order')->where('shipping_status',0)->where('order_id',$data['order_id'])->count()){
                $data['order_status'] = 2;
                $data['shipping_status'] = 1;
                $data['shipping_time'] = time();
                Db::name('order')->update($data);
                 return ['status'=>1,'msg'=>'发货成功'];
             }else{
                 return ['status'=>1,'msg'=>'已发货，请勿重复发货'];
                  }
        }else{
            $order_id=input('order_id');; 
            //订单
            $info=Db::name('order')
                            ->where('order_id',$order_id)
                            ->find();
            //获取省市县地址
             $id = Db::name('region')
                                 ->where('id', $info['province'])
                                 ->find();
             $address['province'] = $id['name'];
             $ids = Db::name('region')
                                  ->where('id', $info['city'])
                                  ->find();
             $address['city'] = $ids['name'];
             $idss = Db::name('region')
                                   ->where('id',$info['district'])
                                   ->find();
             $address['district'] = $idss['name'];
             //获取商品信息
             $goods = Db::name('order_goods')
             					->alias('o')
                                ->join('__PRODUCT__ p', 'o.good_id = p.good_id')
                                ->where('order_id',$order_id)
                                ->select();
             //获取物流信息
             $shipping = Db::name('shipping')
                                ->select();
             $this->assign('info',$info);
             $this->assign('goods',$goods);
             $this->assign('shipping',$shipping);
             $this->assign('address',$address);
             return view();
        }        
    }
    /**
     *订单删除
     */
    public function ordersdel()
    {
        $order_id=input('post.order_id');
         if(Db::name('order')->delete($order_id)){
               return ['status'=>1,'msg'=>''];
            }else{
               return ['status'=>0,'msg'=>''];
            }
    }
}