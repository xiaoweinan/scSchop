<?php

namespace app\home\controller;

use think\captcha\Captcha;
use think\Controller;
use think\Pagination;
use think\Request;
use think\Db;
use think\Session;
use app\home\controller\UploadFile;
use app\home\controller\Base;

class User extends Base
{
    /**
     * 
     *用户注册
     *
     */
    public function reg(Request $request)
    {   
        if($request->isPost()){
            $captcha = input('post.verify_code');
            // 验证码验证
            if(!captcha_check($captcha)){
                return ['status'=>0,'msg'=>'验证码错误'];
            }

            //验证用户名邮箱手机号是否已被注册
            $data = input('post.');
            $user_name = $data['user_name'];
            $phone = $data['phone'];
            $email = $data['email'];
            if(Db::name('member')->where('user_name',$user_name)->count()){
                return ['status'=>0,'msg'=>'该用户名已被注册'];
            }
            if(Db::name('member')->where('phone',$phone)->count()){
                return ['status'=>0,'msg'=>'该手机号已被注册'];
            }
            if(Db::name('member')->where('email',$email)->count()){
                return ['status'=>0,'msg'=>'该邮箱已被注册'];
            }

            //处理数据并入库
            $data['password'] = md5($data['password']);
            $data['reg_time'] = time();
            $data['last_time'] = time();
            $data['last_ip'] = $request->ip();
            unset($data['scene']);
            unset($data['verify_code']);
            unset($data['password2']);
            if($user_id = Db::name('member')->insertGetId($data)){
                $data['user_id'] = $user_id;
                Session::set('infos',$data);
                return ['status'=>1,'msg'=>'注册成功'];
            }
        }else{
            return view();
        }  
    }
    /**
     *
     * 生成验证码
     *
     */
    public function MakeVerify(){
    	$config = [
            'length'=>4,
            'codeSet'=>'0123456789',
            'useCurve'=>false,
            'useNoise'=>false
        ];
    	$captcha = new Captcha($config);
    	return $captcha->entry();
    }
    /**
     *
     * 个人信息展示
     *
     */
    public function mineinfo(){
        $user_id = Session::get('infos.user_id');
        $level = Db::name('member')
                            ->alias('m')
                            ->field('m.*,l.*')
                            ->join('member_level l','l.level_id=m.level')
                            ->where('user_id',$user_id)
                            ->find();
        $this->assign('level',$level);
		
		$where=[];
		$condition=[];
		//搜索关键字处理
		$search_key = input('get.search_key');
		$condition['search_key']= !empty($search_key) ? $search_key : '';

		//查询所有的订单
		$infos = Db::name('order')
			->alias('o')
			->field('o.*,g.*,p.img')
			->join('__ORDER_GOODS__ g','o.order_id = g.order_id','left')
			->join('__PRODUCT__ p','g.good_id = p.good_id','left')
			->where('user_id',$user_id)
			->order('o.order_id ASC')			
			->select();
			
		foreach ($infos as $key=>$va) {
			$order[$va['order_sn']][] = $va;
			// $order[$va['order_sn']]['img'][]= explode(',',$va['img']);
		}
		
		// dump($order);
		if (!empty($infos)) {
			$this->assign('infos',$infos);
			$this->assign('orders',$order);
		}
        return view();
    }

    /**
     *
     * 编辑个人资料
     *
     */
    public function UserInfo(Request $request){
        if($request->isAjax()){
            $user_id = Session::get('infos.user_id');
            $userinfo = input('post.');
            $userinfo['birthday'] = strtotime($userinfo['birthday']); 
            $res = Db::name('member')
                            ->where('user_id',$user_id)
                            ->update($userinfo);
            return ['code'=>1,'msg'=>''];
        } else{
            $user_id = Session::get('infos.user_id');
            $infos = Db::name('member')
                                ->where('user_id',$user_id)
                                ->find();
            $this->assign('infos',$infos);
            return view();
        }
    }
    //头像上传
    public function headpic(){
        $config = 
        [
            'allowExts'=>['jpg','jpeg','png','gif','bmp'],
            'savePath'=>"uploads/headpic/userpic/"
        ];
        $obj = new UploadFile($config);
        $res=$obj->upload();
        if($res){
            $info = $obj->getUploadFileInfo()[0];
            $arr = ["code"=>0,"msg"=>"上传成功","files"=>$info['savepath'].$info['savename']];
            return $arr;    
        }
    }
    //修改密码
    public function ChangePassword(Request $request){
    	if($request->isPost()){
    		$data = input('post.');
    		$user_id = $data['user_id'];
    		$oldpassword = $data['oldpassword'];
    		//判断用户是否修改了密码
    		if(empty($oldpassword)){
                //不做任何修改
                return ['code'=>1,'msg'=>'修改密码成功'];
            }else{
                $password = md5($data['oldpassword']);
                $res =Db::name('member')
                                    ->where('password',$password)
                                    ->where('user_id',$user_id)
                                    ->count();
                if($res){
                    $password = md5($data['password']);
                    $info = [];
                    $info['password'] = $password;
                    Db::name('member')->where('user_id',$user_id)->update($info);
                    return ['code'=>1,'msg'=>'修改密码成功'];
                }else{
                	return ['code'=>0,'msg'=>'旧密码不正确'];
                }
            }
    	}else{
    		$uid = input('get.user_id');
    		$uinfo = Db::name('member')
    						->where('user_id',$uid)
    						->find();
    		$this->assign('uinfo',$uinfo);
        	return view();
    	}
    }
    //找回密码
    public function seachpwd(Request $request){
    	if($request->isPost()){
    		$seach = input('post.seach');
    		//如果用户输入的是邮箱
    		if(is_numeric(strpos($seach,'@'))){
    			//查找数据库中有没有该邮箱
    			$res = Db::name('member')
    								->where('email',$seach)
    								->count();
    			if($res){
    				$user_id = Db::name('member')
    								->field('user_id')
    								->where('email',$seach)
    								->find();
    				$uid = $user_id['user_id'];
    				$password = input('post.password');
    				$password = md5($password);
    				Db::name('member')->where('user_id',$uid)->update(['password'=>$password]);
    				return ['code'=>1,'msg'=>'密码找回成功','url'=>'/home/Login/login'];
    			}else{
    				return ['code'=>0,'msg'=>'预留邮箱错误'];
    			}
    		}
    		//如果用户输入的是手机号
    		if(is_numeric($seach)){
    			$res = Db::name('member')
    								->where('phone',$seach)
    								->count();
    			if($res){
    				$user_id = Db::name('member')
    								->field('user_id')
    								->where('phone',$seach)
    								->find();
    				$uid = $user_id['user_id'];
    				$password = input('post.password');
    				$password = md5($password);
    				Db::name('member')->where('user_id',$uid)->update(['password'=>$password]);
    				return ['code'=>1,'msg'=>'密码找回成功','url'=>'/home/Login/login'];
    			}else{
    				return ['code'=>0,'msg'=>'预留手机号错误'];
    			}
    		}
    	}else{
    		return view();
    	}
    }
    //显示收货地址列表
    public function AddressList(Request $request){
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
    	}else{
    		$uid = Session::get('infos.user_id');
    		$data = Db::name('member_addr')
    							->where('user_id',$uid)
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
					$addr[$key]['province'] = $province['name'];
					$addr[$key]['city'] = $city['name'];
					$addr[$key]['district'] = $district['name'];
					$addr[$key]['address_id'] = $vo['address_id'];
					$addr[$key]['phone'] = $vo['phone'];
					$addr[$key]['address'] = $vo['address'];
					$addr[$key]['consignee'] = $vo['consignee'];
					$addr[$key]['is_default'] = $vo['is_default'];
				}
				$this->assign('addr',$addr);
				return view();
			}
    	}
    }

    //地区三级联动
    public function AddAddr(){
    	$id = input('id');
        $prov = Db::name('member_address')
                                ->where('fid',$id)
                                ->select();
        return $prov;
    }

    //编辑收货地址
    public function AddrEdit(Request $request){
    	if($request->isPost()){
    		$data = input('post.');
    		$address_id = $data['address_id'];
            $uid = $data['user_id'];
    		if(isset($data['is_default'])==false){
    			$data['is_default'] = 0;
    			Db::name('member_addr')
    							->where('address_id',$address_id)
    							->update($data);
    			return ['code'=>1,'msg'=>'修改地址成功'];
    		}else{
    			$data['is_default'] = 1;
    			Db::name('member_addr')
    							->where('address_id',$address_id)
    							->update($data);
                Db::name('member_addr')
                                ->where('user_id',$uid)
                                ->where('address_id','<>',$address_id)
                                ->update(['is_default'=>0]);
    			return ['code'=>1,'msg'=>'修改地址成功'];
    		}
    	}else{
    		$addr_id = input('get.addr_id');
    		$info = Db::name('member_addr')
    						->where('address_id',$addr_id)
    						->find();
    		$province = $info['province'];
    		$city = $info['city'];
    		$district = $info['district'];
    		$prov = Db::name('member_address')
    							->where('level',1)
    							->select();
    		$cty = Db::name('member_address')
    							->where('level',2)
    							->select();
    		$dist = Db::name('member_address')
    							->where('level',3)
    							->select();
    		$this->assign('prov',$prov);
    		$this->assign('cty',$cty);
    		$this->assign('dist',$dist);
    		$this->assign('info',$info);
    		$this->assign('province',$province);
    		$this->assign('city',$city);
    		$this->assign('district',$district);
    		return view();
    	}
    }

    public function DelAddr(){
    	$address_id = input('post.aid');
    	$res = Db::name('member_addr')
    						->where('address_id',$address_id)
    						->delete();
    	if($res){
    		return ['code'=>1,'msg'=>'删除成功'];
    	}
    }

//	跳转到首页
	public function index(){
		$this->redirect('Index/index');
	}
	//我的足迹
	public function visitLog(){
		return view();
	}
	public function addComment(){

		$good_id = input('good_id');
		$order_id = input('order_id');
		$user_name = Session::get('infos.user_name');   //  登陆后存入session中的username
		$this->assign('good_id', $good_id);
		$this->assign('order_id', $order_id);
		$this->assign('user_name', $user_name);
		return view();
	}
//	评论处理
	public function doComment(Request $request){
		$data = input('post.');
		if($data['content'] == ''){

			return ['code'=>0, 'msg'=>'请输入评论内容'];
		}
		if(!isset($data['goods_rank'])){
			return ['code'=>0, 'msg'=>'请选择评论等级'];
		}
		$file = $request->file('img');
		if($file){
			$info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
			$data['img'] = DS . 'uploads' . DS . $info->getSaveName();
		}
		Db::name('order_goods')
			->where('order_id', $data['order_id'])
			->update(['is_comment'=>1]);

		$data['add_time'] = time();
		$data['ip'] = $request->ip();
		Db::name('comment')
			->insertGetId($data);

		return ['code'=>1, 'msg'=>'评论成功'];

	}
	/**
	 * 订单列表
	 */
	public function userOrderList()
    {
        // 订单查询条件
        $condition = [];
        $where = [];
        // 搜索
        $search_key = input('search_key');
        if(!empty($search_key)){
            $where['g.good_name|o.order_sn'] = ['like', "%$search_key%"];
            $condition['search_key'] = $search_key;
        }
        // 订单类型条件
        $type = input('type');
         switch ($type) {
            case 'PAY':
                //待付款
                $where['pay_status'] = 0;
                break;
            case 'SHIPPING':
                //发货状态
            $where['shipping_status'] = 0;
                break;
            case 'ORDER':
                //待收货
                $where['order_status'] = 1;
                break;
            case 'COMMENT':
                //待评论
                $where['order_status'] = 1;
                $where['is_comment'] = 0;
                break;
            case 'FINISH':
                //完成
                $where['order_status'] = 4;
                break;
            case 'CANCEL':
                //已取消
                $where['order_status'] = 3;
                break;
          
        }
        $condition['type'] = !empty($type) ? $type : '' ;
        //获取id
        $user_id = Session::get('infos.user_id');
        // 查询
        $infos= Db::name('order')
                        ->alias('o')
                        ->join('__ORDER_GOODS__ g', 'o.order_id = g.order_id','left')
                        ->join('__PRODUCT__ p', 'g.good_id = p.good_id')
                        ->where($where)
                        ->where('user_id',$user_id)
                        ->order('o.order_id DESC')
                        ->paginate(5, false,['query'=>$condition]);
        $orders = [];
        foreach($infos as $key => $vo){
            $orders[$vo['order_sn']][] = $vo;
        }
        $page = $infos->render();

        $this->assign('condition', $condition);
        $this->assign('page',$page);
        $this->assign('orders',$orders);
        $this->assign('infos', $infos);
        return view();
    }
    /**
    *取消订单
    */
	public function orderCancel(){
		$order_id = input('id');
		$data = [
			'order_id'=>$order_id,
			'order_status'=>3
		];
		if (Db::name('order')->update($data)) {
			return ['status'=>1,'msg'=>'订单已取消'];
		}else {
			return ['status'=>0,'msg'=>'订单取消失败'];
		}
	}
	/**
    *确认收货
    */
	public function orderEdit(){
		$order_id = input('order_id');
		$data = [
			'order_id'=>$order_id,
			'order_status'=>4
		];
		if (Db::name('order')->update($data)) {
			return ['status'=>1,'msg'=>'收货成功'];
		}else {
			return ['status'=>0,'msg'=>'收货失败'];
		}
	}
	/**
    *查看订单详情
    */
	public function details(){
		$order_id = input('order_id');
		//订单
		$info=Db::name('order')
			->where('order_id',$order_id)
			->find();
		//获取省市县地址
		$id = Db::name('region')->where('id', $info['province'])->find();
		$address['province'] = $id['name'];
		$ids = Db::name('region')->where('id', $info['city'])->find();
		$address['city'] = $ids['name'];
		$idss = Db::name('region')->where('id',$info['district'])->find();
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
	 /**
    *评论显示
    */
    public function userComment(){
        // 订单查询条件
        $condition = [];
        $where = [];
        // 搜索
        $search_key = input('search_key');
        if(!empty($search_key)){
            $where['g.good_name|o.order_sn'] = ['like', "%$search_key%"];
            $condition['search_key'] = $search_key;
        }
        // 订单类型条件
        $type = input('type');
         switch ($type) {
            case 'COMMENT':
                //待付款
                $where['is_comment'] = 0;
                break;
            case 'ISCOMMENT':
                //发货状态
            $where['is_comment'] = 1;
                break;
            default:
                //已取消
                $where = [];
                break;
        }
        $condition['type'] = !empty($type) ? $type : '' ;
        //查询所有的订单
         $infos= Db::name('order')
                        ->alias('o')
                        ->join('__ORDER_GOODS__ g', 'o.order_id = g.order_id','left')
                        ->join('__COMMENT__ c', 'g.good_id = c.good_id')
                        ->join('__PRODUCT__ p', 'c.good_id = p.good_id')
                        ->where('order_status',4)
                        ->where($where)
                        ->order('o.order_id DESC')
                        ->paginate(5, false,['query'=>$condition]);
        $orders = [];
        foreach($infos as $key => $vo){
            $orders[$vo['order_sn']][] = $vo;
        }
        $page = $infos->render();

        $this->assign('condition', $condition);
        $this->assign('page',$page);
        $this->assign('orders',$orders);
        $this->assign('infos', $infos);
        return view();
    }
	public function haveComment(){
		$where=[];
		$condition=[];
		//搜索关键字处理
		$search_key = input('get.search_key');
		$condition['search_key']= !empty($search_key) ? $search_key : '';

		//查询所有的订单
		$infos = Db::name('order')
			->alias('o')
			->field('o.*,g.*,p.img')
			->join('__ORDER_GOODS__ g','o.order_id = g.order_id','left')
			->join('__PRODUCT__ p','g.good_id = p.good_id','left')
			->order('o.order_id ASC')
			// ->where($where)
			->whereOr('o.order_sn','like',"%$search_key%")
			->whereOr('g.good_name','like',"%$search_key%")
			->whereOr('')
			->paginate(10,false,['query'=>$condition]);
		// ->select();
		$page = $infos->render();
		foreach ($infos as $key=>$va) {
			$order[$va['order_sn']][] = $va;
			// $order[$va['order_sn']]['img'][]= explode(',',$va['img']);
		}
		if (empty($order)) {
			$this->error('没找到您要找的商品');
		}
		// dump($order);
		$this->assign('page',$page);
		$this->assign('infos',$infos);
		$this->assign('orders',$order);

		return view();
	}
}
