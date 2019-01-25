<?php
namespace app\admin\controller;
use think\Request;
use think\Db;
use think\Session;
use app\admin\controller\Base;
use think\captcha\Captcha;
class Index extends Base
{
    /**
     * 后台首页
     */
    public function index()
    {   
        $role_id = Session::get('admin.role_id');
        $access = Db::name('access')
                            ->where('role_id', $role_id)
                            ->find();
        $all = Db::name('system_module')
                            ->select();
        $mods = Db::name('system_module')
                            ->where('mod_id', 'in', $access['mods'])
                            ->select();
        $child = Db::name('system_module')
                            ->where('mod_id', 'in', $access['child'])
                            ->select();           
        $data = Db::name('admin_role')
                            ->find($role_id);
        $this->assign('all', $all);
        $this->assign('mods', $mods);
        $this->assign('child', $child);
        $this->assign('role_name', $data['role_name']);
        return view(); 
    }
   
    /**
     * 后台欢迎页面
     */
    public function welcome()
    {
        return view();
    }
     /**
     * 后台意见反馈
     */
    public function feedbackList()
    {
        return view();
    }

     /**
     * 后台退出
     */
    public function logout()
    {
        Session::clear();
        $this->redirect('Login/login');
    }

     /**
     * 切换账户
     */
    public function adminChange(Request $request)
    {   
        if($request->isAjax()){
            $admin_name = input('post.admin_name');
            $password = md5(input('post.password'));
            $verify = input('post.verify');
            if(!captcha_check($verify, 'login')){
                return ['code'=>0, 'msg'=>'验证码错误'];
            }
            if($admin_name == Session::get('admin.admin_name')){
                return ['code'=>0, 'msg'=>'请勿重复登陆'];
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
            Session::clear();
            Session::set('admin', $data);
            $da['last_login'] = time();
            Db::name('admin')
                        ->where('admin_id', $data['admin_id'])
                        ->update($da);
            return ['code'=>1];
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