<?php

namespace app\admin\controller;

use app\admin\controller\Base;
use think\Request;
use think\Db;

class System extends Base
{
     /**
     * 权限管理
     */
    public function modList()
    {
        $mods_info = Db::name('system_module')
                            ->select();
        $mods = Db::name('system_module')
                            ->paginate(10);
        $page = $mods->render();
        $this->assign('page', $page);
        $this->assign('mods_info', $mods_info);
        $this->assign('mods', $mods);
        return view();
    }

    /**
     * 添加权限				
     */
    public function modAdd(Request $request)
    {
    	if($request->isPost()){
    		$data = input('post.');
    		if($data['parent_id'] == 0){
    			$data['module'] = 'menu';
    			$data['level'] = 1;
    		}else{
    			$data['module'] = 'module';
    			$data['level'] = 2;
    		}
    		$res = Db::name('system_module')->insertGetId($data);
    		$this->success('权限添加成功');
    	}else{
    		$infos = Db::name('system_module')->select();
    		return view('', ['infos'=>$infos]);
    	}
    }

    /**
     * 删除权限             
     */
    public function modDel(Request $request)
    {
        $mod_id = input('post.mod_id');
        $res = Db::name('system_module')
                                ->where('parent_id', $mod_id)
                                ->find();
        if($res){
            return ['code'=>0, 'msg'=>'请先删除该模块下的子模块'];
        }
        $res = Db::name('system_module')
                                ->delete($mod_id);
        if($res){
            return ['code'=>1, 'msg'=>'删除成功'];
        }else{
            return ['code'=>0, 'msg'=>'删除失败'];
        }
    }

    /**
     * 编辑权限             
     */
    public function modEdit(Request $request)
    {
        $mod_id = input('get.mod_id');
        $mod = Db::name('system_module')
                                ->find($mod_id);
        $mods = Db::name('system_module')
                                ->field('mod_id, level, title')
                                ->select();
        $this->assign('mods', $mods);
        $this->assign('mod', $mod);
        return view();
    }

    /**
     * 网站配置             
     */
    public function systemConfig(Request $request)
    {
        if($request->isAjax()){
            $data = input('post.');
            Db::name('system_config')
                                ->update($data);
            return ['code'=>1, 'msg'=>'操作成功'];
        }else{
            $cfgs = Db::name('system_config')
                                ->select();
            $this->assign('cfgs', $cfgs);
            return view();
        }    
    }

    /**
     * 添加配置项             
     */
    public function configAdd(Request $request)
    {
        if($request->isAjax()){
            $data = input('post.');
            $res = Db::name('system_config')
                                ->where('title', $data['title'])
                                ->find();
            if($res){
                return ['code'=>0, 'msg'=>'该配置项名称已存在'];
            }
            $res = Db::name('system_config')
                                ->where('varname', $data['varname'])
                                ->find();
            if($res){
                return ['code'=>0, 'msg'=>'该变量名已存在'];
            }
            $res = Db::name('system_config')
                                ->insertGetId($data);
            if($res){
                return ['code'=>1, 'msg'=>'添加成功'];
            }else{
                return ['code'=>0, 'msg'=>'添加失败'];
            }
        }else{
            return view();
        }
    }

    /**
     * 友情链接             
     */
    public function friendLink(Request $request)
    {   
        $links = Db::name('friend_link')
                                ->paginate(8);
        $count = Db::name('friend_link')
                                ->count();
        $this->assign('count', $count);
        $this->assign('page', $links->render());
        $this->assign('links', $links);
        return view();
    }

    /**
     * 添加链接             
     */
    public function linkAdd(Request $request)
    {   
        if($request->isAjax()){
            $data = input('post.');
            $res = Db::name('friend_link')
                                ->where('link_name', $data['link_name'])
                                ->find();
            if($res){
                return ['code'=>0, 'msg'=>'已存在该链接名称'];
            }
            $res = Db::name('friend_link')
                                ->where('link_url', $data['link_url'])
                                ->find();
            if($res){
                return ['code'=>0, 'msg'=>'已存在该链接地址'];
            }
            $res = Db::name('friend_link')
                                ->insertGetId($data);
            if($res){
                return ['code'=>1, 'msg'=>'添加成功'];
            }else{
                return ['code'=>0, 'msg'=>'添加失败'];
            }       
        }else{
            return view();
        }
    }

    /**
     * 编辑链接             
     */
    public function linkEdit(Request $request)
    {   
        if($request->isAjax()){
            $data = input('post.');
            $res = Db::name('friend_link')
                                ->where('link_id', '<>', $data['link_id'])
                                ->where('link_name', $data['link_name'])
                                ->find();
            if($res){
                return ['code'=>0, 'msg'=>'已存在该链接名称'];
            }
            $res = Db::name('friend_link')
                                ->where('link_id', '<>', $data['link_id'])
                                ->where('link_url', $data['link_url'])
                                ->find();
            if($res){
                return ['code'=>0, 'msg'=>'已存在该链接地址'];
            }
            Db::name('friend_link')
                                ->update($data);
            return ['code'=>1, 'msg'=>'编辑成功'];
                
        }else{
            if($request->isAjax()){

            }else{
                $link_id = input('get.link_id');
                $link = Db::name('friend_link')
                                        ->find($link_id);
                $this->assign('link', $link);
                return view();
            }
        }
    }

    /**
     * 链接删除         
     */
    public function linkDel(){
        $link_id = input('post.link_id');
        $res = Db::name('friend_link')
                                        ->delete($link_id);
        if($res){
            return ['code'=>1, 'msg'=>'删除成功'];
        }else{
            return ['code'=>0, 'msg'=>'删除失败'];
        }
    }
}
