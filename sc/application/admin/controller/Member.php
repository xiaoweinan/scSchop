<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use think\Controller;
use think\Request;
use think\Db;
class Member extends Base
{
    /**
     * 用户列表
     *
     */
    public function memberList()
    {
        // 搜索条件
        $where = [];
        $condition = [];
        $keywords = input('get.keywords');
        if(!empty($keywords)){
            $where['user_name|email|phone'] = ['like',"%{$keywords}%"];
            $condition['keywords'] = $keywords;
        }
        // 查询数据
        $data = Db::name('member')
                            ->where($where)
                            ->where('is_delete',1)
                            ->select();
        $this->assign('condition',$condition);
        // dump($data);
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 用户账号是否启用
     *
     */
    public function isLock()
    {
        $data = input('post.');
        $user_id = $data['id'];
        if($data['type']){
            $lock=['is_lock'=>0];
        }else{
            $lock=['is_lock'=>1];
        }
        $res = Db::table('sc_member')
                                ->where('user_id',$user_id)
                                ->update($lock);
        if($res){
            return 'true';
        }else{
            return 'false';
        }
   }

    /**
     * 添加用户
     */
    public function MemberAdd(Request $request)
    {   
        if($request->isAjax()){
            if($request->isGet()){
                if(input('get.logo')==''){
                    $id = input('get.id');
                    $province = Db::name('member_address')
                                        ->where('fid',$id)
                                        ->select();
                    return $province; 
                }else{
                    $username = input('get.user_name');
                    //检测用户名是否已被注册
                    $res = Db::name('member')
                                        ->where('user_name',$username)
                                        ->count();
                    if($res){
                        echo "false";
                    }else{
                        echo "true";
                    }
                }
            }elseif($request->isPost()){
              if($request->file()==null){
                    $infos = input('post.');
                    $infos['password'] = md5($infos['password']);
                    $infos['reg_time'] = time();
                    unset($infos['uploadfile']);
                    if($user_id = Db::name('member')->insertGetId($infos))
                        {   
                            return ['code'=>1,'msg'=>'添加用户成功'];
                        }else{
                            return ['code'=>0,'msg'=>'添加用户失败'];
                        }
                }else{
                    $img = $request->file('head_pic');
                    $res = $img->move('uploads/headpic');  
                    if($res)
                    {
                        $head_pic = '\uploads\headpic\\'.$res->getSaveName();
                        $infos = input('post.');
                        $infos['password'] = md5($infos['password']);
                        $infos['reg_time'] = time();
                        $infos['head_pic'] = $head_pic;
                        unset($infos['uploadfile']);
                        if($user_id = Db::name('member')->insertGetId($infos))
                        {   
                            return ['code'=>1,'msg'=>'添加用户成功'];
                        }else{
                            return ['code'=>0,'msg'=>'添加用户失败'];
                        }
                    } 
                }
            }    
        }else{
            return view(); 
        } 
    }
    public function checkUserName()
    {

    }
    public function area()
    {

    }
    /**
     * 用户信息展示
     *
     */
    
    public function MemberShow()
    {   
        $get = input();
        $user_id = $get['id'];
        $self = Db::name('member')
                                ->alias('m')
                                ->join('member_level ml','ml.level_id=m.level')
                                ->where('user_id',$user_id)
                                ->find();
        $province = $self['province'];
        $city = $self['city'];
        $district = $self['district'];
        $addr[] = $province;
        $addr[] = $city;
        $addr[] = $district;
        $address = Db::name('member_address')
                                    ->where('id','in',$addr)
                                    ->select();
        $this->assign('address',$address);
        $this->assign('self',$self);
        return view();
    }

    /**
     * 编辑会员
     *
     */

    public function MemberEdit(Request $request){
        if($request->isPost()){
            $data = input('post.');
            $uid = $data['user_id'];
            if(empty($data['oldpassword'])){
                unset($data['oldpassword']);
                unset($data['password']);
                $res = Db::name('member')
                                    ->where('user_id',$uid)
                                    ->update($data);
                if($res){
                    return ['code'=>1,'msg'=>'编辑会员成功'];
                }else{
                    return ['code'=>0,'msg'=>'编辑会员失败'];
                }
            }else{
                $oldpwd = md5($data['oldpassword']);
                $answer = Db::name('member')
                                    ->where('user_id',$uid)
                                    ->where('password',$oldpwd)
                                    ->count();
                if($answer){
                    $data['password'] = md5($data['password']);
                    unset($data['oldpassword']);
                    $res = Db::name('member')
                                        ->where('user_id',$uid)
                                        ->update($data);
                    if($res){
                        return ['code'=>1,'msg'=>'编辑会员成功'];
                    }else{
                        return ['code'=>0,'msg'=>'编辑会员失败'];
                    }
                }else{
                    return ['code'=>3,'msg'=>'旧密码不正确'];
                }
            }
        }else{
            //编辑会员默认显示
            $uid = input('get.uid');
            $data = Db::name('member')
                                ->where('user_id',$uid)
                                ->find();
            $this->assign('data',$data);
            $province = $data['province'];
            $city = $data['city'];
            $district = $data['district'];
            $this->assign('province',$province);
            $this->assign('city',$city);
            $this->assign('district',$district);
            $pro = Db::name('member_address')
                                    ->where('level',1)
                                    ->select();
            $this->assign('pro',$pro);
            $cty = Db::name('member_address')
                                    ->where('level',2)
                                    ->select();
            $this->assign('cty',$cty);
            $dist = Db::name('member_address')
                                    ->where('level',3)
                                    ->select();
            $this->assign('dist',$dist);
            return view(); 
        }       
    }

    /**
     * 编辑会员时的地区三级联动
     *
     */
    public function AddrEdit(){
        $id = input('id');
        $prov = Db::name('member_address')
                                ->where('fid',$id)
                                ->select();
        return $prov;
    }


    /**
     * 检测会员名是否已被注册
     *
     */

    public function CheckMember(){
        $self = input('get.');
        $uid = $self['uid'];
        $uname = $self['user_name'];
        $res = Db::name('member')
                            ->where('user_name',$uname)
                            ->where('user_id','<>',$uid)
                            ->count();
        if($res){
            echo "false";
        }else{
            echo "true";
        }
    }

    /**
     *修改会员密码
     *
     */
    public function ChangePassword(Request $request)
    {
        if($request->isPost()){
            $data = input('post.');
            $user_id = $data['uid'];
            $oldpassword = $data['oldpassword'];
            if(empty($oldpassword)){
                //不做任何修改
                return ['code'=>0,'msg'=>''];
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
                    if(Db::name('member')->where('user_id',$user_id)->update($info)){
                        return ['code'=>1,'msg'=>'修改密码成功'];
                    }else{
                        return ['code'=>'error','msg'=>'修改密码失败'];
                    }
                }
            }
        }else{
            $uid = input('get.uid');
            $selfinfo = Db::name('member')
                                        ->where('user_id',$uid)
                                        ->find();
            $this->assign('selfinfo',$selfinfo);
            return view();
        }
    }


    /**
     *删除会员
     *
     */
    
    public function MemberDelete(){
        $uid = input('post.id');
        if(Db::name('member')->where('user_id',$uid)->update(['is_delete'=>0])){
            return ['code'=>1,'msg'=>'删除成功'];
        }else{
            return['code'=>0,'msg'=>'删除失败'];
        }
    }

    /**
     *删除的会员列表
     *
     */
    

    public function MemberDel(Request $request)
    {
        if($request->isPost()){
            //彻底删除会员
            $uid = input('post.id');
            $res = Db::name('member')
                            ->where('user_id',$uid)
                            ->find();
            $file = $res['head_pic'];
            if(Db::name('member')->where('user_id',$uid)->delete()){
                unlink('.'.$file);
                return ['code'=>1,'msg'=>'删除成功'];
            }else{
                return ['code'=>0,'msg'=>'删除失败'];
            }
        }else{
             $infos = Db::name('member')
                            ->where('is_delete',0)
                            ->select();
            $this->assign('infos',$infos);
            return view();
        }
    }

     /**
     *还原删除的会员
     *
     */
    public function MemberRestore()
    {
        $uid = input('post.id');
        if(Db::name('member')->where('user_id',$uid)->update(['is_delete'=>1])){
            return ['code'=>1,'msg'=>'还原成功'];
        }else{
            return['code'=>0,'msg'=>'还原失败'];
        }
    }

    /**
     *会员等级列表
     *
     */
    public function MemberLevel(){
        $infos = Db::name('member_level')
                                ->select();
        $this->assign('infos',$infos);
        return view();
    }

    /**
     *增加会员等级
     *
     */    
    public function LevelAdd(Request $request){
        if($request->isPost()){
            $data = input('post.');
            $lname = $data['level_name'];
            $res = Db::name('member_level')
                            ->where('level_name',$lname)
                            ->count();
            if($res){
                return ['code'=>0,'msg'=>'该会员等级已存在'];
            }else{
                if(Db::name('member_level')->insert($data)){
                    return ['code'=>1,'msg'=>'添加成功'];
                }else{
                    return ['code'=>0,'msg'=>'添加失败'];
                }
            }
        }else{
            return view();
        }   
    }

    /**
     *编辑会员等级
     *
     */
    public function LevelEdit(Request $request){
        if($request->isPost()){
            $data = input('post.');
            $id = $data['level_id'];
            $data['update_time'] = time();
            if(Db::name('member_level')->where('level_id',$id)->update($data)){
                return ['code'=>1,'msg'=>'编辑成功'];
            }else{
                return ['code'=>0,'msg'=>'编辑失败'];
            }
        }else{
                $lid = input('get.lid');
                $info = Db::name('member_level')
                                    ->where('level_id',$lid)
                                    ->find();
                $this->assign('info',$info);
                return view();
        }   
    }

    /**
     *会员等级名称检测
     *
     */
    public function CheckLevel(){
        $data = input('get.');
        $lid = $data['id'];
        $level_name = $data['level_name'];
        if(Db::name('member_level')->where('level_name',$level_name)->where('level_id','<>',$lid)->count()){
            echo 'false';
        }else{
            echo 'true';
        }
    }

    /**
     * 删除会员等级
     *
     */
    public function LevelDel(){
        $lid = input('post.id');
        $res = Db::name('member_level')
                            ->alias('ml')
                            ->join('member m','m.level=ml.level_id')
                            ->where('level_id',$lid)
                            ->count();
        if($res){
            return ['code'=>0,'msg'=>'无法删除有用户的会员等级'];
        }else{
            if(Db::name('member_level')->where('level_id',$lid)->delete()){
                return ['code'=>1,'msg'=>'删除成功'];
            }else{
                return ['code'=>0,'msg'=>'删除失败'];
            }
        }
    }

    /**
     * 浏览记录
     *
     */
    public function MemberRecordBrowse()
    {
        return $this->fetch();
    }

    /**
     * 下载记录
     *
     * @return \think\Response
     */
    public function MemberRecordDownload()
    {
        return $this->fetch();
    }

}
