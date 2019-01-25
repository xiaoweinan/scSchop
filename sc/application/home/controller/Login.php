<?php
namespace app\home\controller;
use think\captcha\Captcha;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
use app\home\controller\Base;

class Login extends Base
{
	/**
     * 
     *登录页面显示
     *
     */
    public function login()
    {
    	return view();
    }

    /**
     * 
     *生成验证码
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
     *处理登录
     *
     */
    public function dologin(Request $request){
    	//验证码验证
    	$captcha = input('post.verify_code');
    	if(!captcha_check($captcha)){
    		return ['status'=>0,'msg'=>'验证码错误','url'=>''];
    	}

    	//用户登录信息验证
    	$user_name = input('post.user_name');
    	$password = input('post.password');
    	//过滤用户名和密码
        if(empty(trim($user_name))){
            return ['status'=>0,'msg'=>'请填写用户名'];
        }
        if(empty(trim($password))){
            return ['status'=>0,'msg'=>'请填写密码'];
        }
        // 验证用户名
        $info = Db::name('member')
                        ->where('user_name',$user_name)
                        ->find();
        if(!$info){
            return ['status'=>0,'msg'=>'用户名不存在'];
        }
        //验证用户密码
        if($info['password']!=md5($password)){
            return ['status'=>0,'msg'=>'密码不正确'];
        }
        if($info['is_lock']==0){
            return ['status'=>0,'msg'=>'该账号已被锁定,无法登录'];
        }
        //更新登录信息
        Session::set('infos',$info);
        $data['last_ip'] = $request->ip();
        $data['last_time'] = time();
        Db::table('sc_member')->where('user_id',$info['user_id'])->update($data);
        return ['status'=>1,'url'=>url('Index/index')];
    }
    /**
     * 
     *退出登录
     *
     */
    public function LoginOut(){
    	Session::clear();
    	$this->redirect('Login/login');
    }
}