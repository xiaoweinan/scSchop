<?php
namespace app\admin\controller;
use think\Request;
use think\Db;
use think\Session;
use app\admin\controller\Base;

class Admin extends Base
{
    /**
     *  管理员角色管理
     */
    public function adminRole()
    {
        $data = Db::name('admin_role')->select();
        $this->assign('data',$data);
        return view();
    }
    /**
     * 添加角色
     */
    public function adminRoleAdd(Request $request)
    {   
        if($request->isAjax()){
            $role_name = input('post.role_name');
            $role_desc = input('post.role_desc');
            $mods = input('post.')['mods'];
            $child = input('post.')['child'];
            if(!$child){
                return ['code'=>0, 'msg'=>'请设置角色权限'];
            }
            $m = '';
            $c = '';
            foreach($mods as $v){
                $m .= $v.','; 
            }
            foreach($child as $v){
                $c .= $v.',';
            }
            $mods = substr($m, 0, -1);
            $child = substr($c, 0, -1);
            if($role_name == ''){
                return ['code'=>0, 'msg'=>'角色名不能为空'];
            }
            $res = Db::name('admin_role')
                                ->where('role_name', $role_name)
                                ->find();
            if($res){
                return ['code'=>0, 'msg'=>'角色名已存在'];
            }
            // 入库
            $res = Db::name('admin_role')
                                ->insertGetId(['role_name'=>$role_name, 'role_desc'=>$role_desc]);     
            if($res){
                Db::name('access')
                                ->insert(['role_id'=>$res, 'mods'=>$mods, 'child'=>$child]);
                return ['code'=>1, 'msg'=>'添加成功'];
            }else{
                return ['code'=>0, 'msg'=>'添加失败'];
            }
        }else{
            $mods = [];
            $mod_info = Db::name('system_module')
                                ->where('level',1)
                                ->select();
            foreach($mod_info as $vo){
                $mods[$vo['mod_id']] = $vo['title'];
            }

            $child = Db::name('system_module')
                                ->where('level',2)
                                ->select();
            $this->assign(['mods'=>$mods, 'child'=>$child]);
            return view();
        }
    }

    /**
     * 删除角色
     */
    public function roleDel(Request $request)
    {   
        $role_id = input('post.role_id');
        $res = Db::name('admin')
                            ->where('role_id', $role_id)
                            ->select();
        if($res){
            return ['code'=>0, 'msg'=>'请先删除该角色下的管理员'];
        }

        $res = Db::name('admin_role')
                            ->delete($role_id);
        if($res){
            Db::name('access')
                            ->where('role_id', $role_id)
                            ->delete();
            return ['code'=>1, 'msg'=>'已删除'];
        }else{
            return ['code'=>0, 'msg'=>'删除失败'];
        }
    }

    /**
     * 角色编辑
     */
    public function adminRoleEdit(Request $request)
    {   
        if($request->isAjax()){
            $role_id = input('post.role_id');
            $role_name = input('post.role_name');
            $role_desc = input('post.role_desc');
            $mods = input('post.')['mods'];
            $child = input('post.')['child'];
            if(!$child){
                return ['code'=>0, 'msg'=>'请设置角色权限'];
            }
            $m = '';
            $c = '';
            foreach($mods as $v){
                $m .= $v.','; 
            }
            foreach($child as $v){
                $c .= $v.',';
            }
            $mods = substr($m, 0, -1);
            $child = substr($c, 0, -1);
            if($role_name == ''){
                return ['code'=>0, 'msg'=>'角色名不能为空'];
            }
            $res = Db::name('admin_role')
                                ->where('role_id', '<>', $role_id)
                                ->where('role_name', $role_name)
                                ->find();
            if($res){
                return ['code'=>0, 'msg'=>'角色名已存在'];
            }
            // 入库
            $res = Db::name('admin_role')
                                ->where('role_id', $role_id)
                                ->update(['role_name'=>$role_name, 'role_desc'=>$role_desc]);     
            Db::name('access')
                                ->where('role_id', $role_id)
                                ->update(['mods'=>$mods, 'child'=>$child]);
            return ['code'=>1, 'msg'=>'编辑成功'];
           
        }else{ 
            // 角色信息   
            $role_id = input('get.role_id');
            $role = Db::name('admin_role')
                            ->find($role_id);
            // 权限信息
            $mods = [];
            $mod_info = Db::name('system_module')
                                ->where('level',1)
                                ->select();
            foreach($mod_info as $vo){
                $mods[$vo['mod_id']] = $vo['title'];
            }
            $child = Db::name('system_module')
                                ->where('level',2)
                                ->select();

            $access = Db::name('access')
                                ->where('role_id', $role_id)
                                ->find();

            $this->assign('access', $access);
            $this->assign(['mods'=>$mods, 'child'=>$child]);
            $this->assign('role', $role);
            return view();
        }
    }

     /**
     * 管理员列表
     */
    public function adminList()
    {   
        $start = input('get.start_date');
        $end = input('get.end_date');
        // 时间搜索
        $start_date = input('get.start_date')?strtotime(input('get.start_date').' 00:00:00'):0;
        $end_date = input('get.end_date')?strtotime(input('get.end_date').' 23:59:59'):time();
        // 搜用户
        $keyword = input('get.keyword');
        if($keyword == ''){
            $data = Db::name('admin')
                            ->where('add_time', 'between', [$start_date, $end_date])
                            ->paginate(10, '');
        }else{
            $data = Db::name('admin')
                            ->where('add_time', 'between', [$start_date, $end_date])
                            ->where('admin_name', 'like', "%$keyword%")
                            ->paginate(10, '');
        }
        $page = $data->render();
        $role_info = Db::name('admin_role')
                            ->select();
        $roles = [];
        foreach($role_info as $v){
            $roles[$v['role_id']] = $v['role_name'];
        }
        return view('',['data'=>$data, 'start'=>$start, 'end'=>$end, 'keyword'=>$keyword, 'page'=>$page, 'roles'=>$roles]);     
    }
    /**
     * 添加管理员
     */
    public function adminAdd()
    {
        if(request()->isAjax()){
            // 判断用户名是否被注册
            if(input('get.admin_name')){
                $admin_name = input('get.admin_name');
                $res = Db::name('admin')->where('admin_name',$admin_name)->count();
                if($res > 0){
                    echo "false";
                }else{
                    echo "true";
                }
            }else{
                // 入库
                $data = input('post.');
                $data['password'] = md5($data['password']);
                $data['add_time'] = time();
                $data['last_login'] = time();
                $res = Db::name('admin')->insertGetId($data);
                if($res){
                    $this->success('添加成功');
                }else{
                    $this->error('添加失败');
                }
            }
        }else{
            $role_info = Db::name('admin_role')
                            ->select();
            $roles = [];
            foreach($role_info as $v){
                $roles[$v['role_id']] = $v['role_name'];
            }
            $this->assign('roles', $roles);
            return view();
        }
    }
     /**
     * 删除管理员
     */
    public function adminDel()
    {
        $admin_id = input('post.admin_id');
        if($admin_id == 1){
            return -1;
        }
        $res = Db::name('admin')->delete($admin_id);
        if($res){
            return 1;
        }else{
            return 0;
        }
    }
     /**
     * 批量删除管理员
     */
    public function adminsDel()
    {
        if(!input('post.ids')){
            return -1;
        }
        $ids = explode(',', substr(input('post.ids'), 0, -1));
        $res = Db::name('admin')->delete($ids);
        if($res){
            return 1;
        }else{
            return 0;
        }
    }
     /**
     * 停用、启用管理员
     */
    public function adminLock()
    {
        $admin_id = input('post.admin_id');
        $type = input('post.type');
        if($type == 0){
            $res = Db::name('admin')->where('admin_id', $admin_id)->update(['is_lock'=>0]);
            if($res){
                return 'true';
            }else{
                return 'false';
            }
        }else{
            $res = Db::name('admin')->where('admin_id', $admin_id)->update(['is_lock'=>1]);
            if($res){
                return 'true';
            }else{
                return 'false';
            }
        }
    }
    /**
     * 编辑管理员
     */
    public function adminEdit()
    {   
        if(request()->isAjax()){
            $data = input('post.');
            if($data['password'] == ''){
                unset($data['password']);
            }
            // 判断用户名是否存在
            $res = Db::name('admin')
                        ->where('admin_id', 'neq', $data['admin_id'])
                        ->where('admin_name', $data['admin_name'])
                        ->count();
            if($res){
                return ['code'=>0, 'msg'=>'用户名已存在'];
            }
            // 编辑用户信息
            Db::name('admin')->update($data);
            return ['code'=>1, 'msg'=>'编辑成功'];
        }else{
            $info = Db::name('admin')
                        ->find(input('get.admin_id'));
            $role_info = Db::name('admin_role')
                        ->select();
            $role = [];
            foreach($role_info as $v){
                $role[$v['role_id']] = $v['role_name'];
            }
            $this->assign('role', $role);
            $this->assign('info', $info);
            return view();
        } 
    }

    /**
     * 编辑个人信息
     */
    public function myselfinfo(Request $request)
    {   
        if($request->isAjax()){
            $data = input('post.');
            if($data['admin_name'] == ''){
                return ['code'=>0, 'msg'=>'用户名不能为空'];
            }
            $res = Db::name('admin')
                            ->where('admin_id', '<>', $data['admin_id'])
                            ->where('admin_name', $data['admin_name'])
                            ->find();
            if($res){
                return ['code'=>0, 'msg'=>'用户名已存在'];
            }
            if($data['oldpwd'] == ''){
                if($data['newpwd'] != ''){
                    return ['code'=>0, 'msg'=>'请输入原密码'];
                }
            }else{
                if(md5($data['oldpwd']) != Session::get('admin.password')){
                    return ['code'=>0, 'msg'=>'原密码错误'];
                }
                if($data['newpwd'] != ''){
                    $data['password'] = md5($data['newpwd']);
                }else{
                    return ['code'=>0, 'msg'=>'请输入新密码'];
                }
            }
            unset($data['oldpwd']);
            unset($data['newpwd']);
            Db::name('admin')
                            ->update($data);
            Session::set('admin', $data);
            return ['code'=>'1', 'msg'=>'修改成功'];
        }else{
            $admin_id = input('admin_id');
            $data = Db::name('admin')->find($admin_id);
            return view('', ['data'=>$data]);
        }
    }  
}