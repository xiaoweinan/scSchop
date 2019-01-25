<?php
namespace app\admin\controller;
use think\Request;
use think\Db;
use think\Pagination;
use app\Admin\controller\Base;

class Article extends Base
{
    /**
     * 后台资讯管理
     */
    public function articleList()
    {
        $where=[];
        $condition=[];
        //搜索关键字处理
        $user_name = input('get.keyword');
        if (!empty($user_name)){
            $where['user_name']=['like',"%{$user_name}%"];
        }
        $condition['keyword']= !empty($user_name) ? $user_name : '';
        //搜索开始日期
        $start_date = input('get.start_date');
        $start_date = !empty($start_date) ? strtotime($start_date."00:00:00") : 0;
        $condition['start_date']= !empty($start_date) ? date("Y-m-d",$start_date) : '';
        //搜索结束日期
        $end_date = input('get.end_date');
        $end_date = !empty($end_date) ? strtotime($end_date."23:59:59") : time();
        $condition['end_date']= !empty($end_date) ? date("Y-m-d",$end_date) : '';
        //查询
        $where['add_time']=['between',[$start_date,$end_date]];
        $article = Db::name('article')
                                ->alias('a')
                                ->field('a.*,c.classfiy_name')
                                ->join('__ARTICLE_CLASSFIY__ c','a.cat_id = c.cat_id')
                                ->where($where)
                                ->order('article_id ASC')
                                ->paginate(10,false,['query'=>$condition]);
        $articles = Db::name('article')
                                ->select();
            $page = $article->render();
        return view('',['article'=>$article,'articles'=>$articles,'condition'=>$condition,'page'=>$page]);
    }
    /**
     * 后台添加资讯
     */
    public function articleAdd(Request $request)
    {
        //判断是否是post传值
        if($request->isPost()){
            $data=input('post.');
            $data['add_time']=time();
            //检校文章是否被注册
            if(Db::name('article')->where('title',$data['title'])->count())
                {
                   return ['status'=>0,'msg'=>'此文章名称已被添加，请更换'];
                }
            //数据入库
            if(Db::name('article')->insertGetId($data)){
                 return ['status'=>1,'msg'=>'添加成功'];
             }else{
                 return ['status'=>0,'msg'=>'添加失败'];
                  }
        }else{
            $role_info = Db::name('article_classfiy')
                            ->select();
            $roles = [];
            foreach($role_info as $v){
                $roles[$v['cat_id']] = $v['classfiy_name'];
            }
            $this->assign('roles', $roles);
            return view();
        }   
    }
    /**
     *编辑文章
     */
    public function articleEdit(Request $request)
    {
        //判断是否是post传值
        if($request->isPost()){
            $data = input('post.');
            //检校用户名是否被注册
            if(Db::name('article')
                    ->where('title',$data['title'])
                    ->where('article_id','<>',$data['article_id'])
                    ->count())
                {
                   return ['status'=>0,'msg'=>'此文章名称已被添加，请更换'];
                }
            //数据入库
            if(Db::name('article')->update($data)){
                 return ['status'=>1,'msg'=>'编辑成功'];
             }else{
                 return ['status'=>1,'msg'=>'编辑失败'];
                  }
        }else{
            $article_id=input('article_id');
            //管理员
            $info=Db::name('article')
                        ->where('article_id',$article_id)
                        ->find(); 
            //角色
            $roles=Db::name('article_classfiy')
                        ->order('cat_id ASC')
                        ->select(); 
            $this->assign('info',$info);
            $this->assign('roles',$roles);
            return view();
        }        
    }
     /**
     *文章删除
     */
    public function articledel()
    {
        $article_id=input('post.article_id');
         if(Db::name('article')->delete($article_id)){
               return ['status'=>1,'msg'=>''];
             }else{
               return ['status'=>0,'msg'=>''];
                  }
    }
    /**
     *文章批量删除
     */
    public function articlesdel()
    {
        $ids=input('post.ids');
        $ids=explode(',',substr($ids,0,-1));
         if(Db::name('article')->delete($ids)){
               return ['status'=>1,'msg'=>''];
             }else{
               return ['status'=>0,'msg'=>''];
                  }
    }
    /**
     *文章发布下架
     */
    public function articlelock(Request $request)
    {
       $article_id = input('post.article_id');
       $type = input('post.type');
       if($type == 1){
            $data = ['is_lock'=>1];
       }else{
            $data = ['is_lock'=>2];
       }
         if (Db::name('article')->where('article_id',$article_id)->update($data)){
              return 'true';
         }else{
              return 'false';
         }
    }
     /**
     * 后台资讯分类列表
     */
    public function articleclassfiylist()
    {
      $classfiy = Db::name('article_classfiy')->select();
      $this->assign('classfiy',$classfiy);
        return view();
    }
    /**
     * 后台资讯分类添加
     */
    public function articleclassifyadd(Request $request)
    {
      //显示所有的分类
      $classfiy = Db::name('article_classfiy')
                              ->where('parent_id', 0)
                              ->select();
      $this->assign('classfiy',$classfiy);
      //添加新的分类
      if ($request->isPost()) {
        $data = input('post.');
        if (Db::name('article_classfiy')->insertGetId($data)) {
          return ['status' =>1 , 'msg' =>'操作成功'];
        }else{
          return ['status' =>0 , 'msg' =>'操作失败'];
        }
      }
        return view();
    }
    /**
     * 后台资讯分类编辑
     */
    public function articleclassifyedit(Request $request)
    {
      $id = input('id');
      //显示所有的分类
      $classfiy = Db::name('article_classfiy')
                                ->where('parent_id', 0)
                                ->select();
      //通过id显示被编辑的信息
      $classfiyEdit = Db::name('article_classfiy')
                        ->where('cat_id',$id)
                        ->find();
      $this->assign('classfiyEdit',$classfiyEdit);
      $this->assign('classfiy',$classfiy);
      //更新
      if ($request->isPost()) {
        $data = input('post.');
        if(Db::name('article_classfiy')->update($data)){
          return ['status'=>1, 'msg'=>'编辑成功'];    
        }else{
          return ['status'=>0, 'msg'=>'编辑失败'];    
        }
      }
        return view();
    }
    /**
     * 停用、启用管理员
     */
    public function classfiyLock()
    {
        $id = input('post.id');
        $type = input('post.type');
        if($type == 0){
            $res = Db::name('article_classfiy')->where('cat_id', $id)->update(['show_in_nav'=>0]);
            if($res){
                return 'true';
            }else{
                return 'false';
            }
        }else{
            $res = Db::name('article_classfiy')->where('cat_id', $id)->update(['show_in_nav'=>1]);
            if($res){
                return 'true';
            }else{
                return 'false';
            }
        }
    }
    /**
     * 删除分类
     */
    public function classfiyDel()
    {
        $id = input('post.id');
        if($id == 1){
            return -1;
        }
        if(Db::name('article_classfiy')->delete($id)){
            return ['status'=>1, 'msg'=>'删除成功'];
        }else{
            return ['status'=>0, 'msg'=>'删除失败'];
        }
        return view();
    }
    /**
     * 所有是否状态管理
     */
     public function Lock(){
        //去值
        $val = input('post.val');
        $type = input('post.type');
        //判断是否为商品列表id(特殊)
        $data['sp_id'] = input('sp_id');
        //取数据库表名
        $table = input('table');
        //字段名和值
        $data[$type] = $val;
        //修改数据库
        $res = Db::name($table)->update($data);
        if($res){
            return 'true';
        }else{
            return 'false';
        }
    }
}