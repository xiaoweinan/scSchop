<?php

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\captcha\Captcha;
use think\Db;
use think\Session;


class Login extends Controller
{
    /**
     * 登录界面
     */
    public function login(Request $request)
    {   
        if(Session::has('admin.admin_id')){
            $this->redirect('Index/index');
        }
        if($request->isAjax()){
            $admin_name = input('post.admin_name');
            $password = md5(input('post.password'));
            $verify = input('post.verify');
            $captcha = new Captcha;
            if(!$captcha->check($verify,'login')){
                $this->error('验证码错误');
            }
            $data = Db::name('admin')
                        ->where('admin_name', $admin_name)
                        ->find();
            if(!$data){
                return ['code'=>'0', 'msg'=>'账号不存在'];
            }
            if($data['is_lock'] == 0){
                return ['code'=>'0', 'msg'=>'账号已锁定'];
            }
            if($data['password'] != $password){
                return ['code'=>'0', 'msg'=>'密码错误'];
            }

            Session::set('admin', $data);
            $da['last_login'] = time();
            Db::name('admin')
                        ->where('admin_id', $data['admin_id'])
                        ->update($da);
            return ['code'=>1, 'url'=>url('admin/Index/index')];
        }else{
            return view();
        }
        
    }
    /**
     * 验证码
     */
    public function verify()
    {
        $config =    [
        'fontSize'    =>    40,    
        'length'      =>    4,   
        'useNoise'    =>    false, 
        'codeSet'     =>    '1234567890'
        ];
        $captcha = new Captcha($config);
        return $captcha->entry('login');
    }

}
