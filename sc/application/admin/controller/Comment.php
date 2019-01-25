<?php
namespace app\admin\controller;
use think\Request;
use think\Db;
use think\Pagination;
use app\Admin\controller\Base;

class Comment extends Base
{
    /**
     * 后台评论管理
     */
    public function commentList()
    {
        $where=[];
        $condition=[];
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
        $comment = Db::name('comment')
                                ->alias('c')
                                ->field('c.*,g.good_name')
                                ->join('__ORDER_GOODS__ g','c.good_id = g.good_id')
                                ->where($where)
                                ->order('add_time ASC')
                                ->paginate(10,false,['query'=>$condition]);
        $comments = Db::name('comment')
                                ->select();
        $page = $comment->render();
        return view('',['comment'=>$comment,'comments'=>$comments,'condition'=>$condition,'page'=>$page]);
    }
    /**
     * 回复评论
     */
    public function commentCheck(Request $request)
    {
        //判断是否是post传值
        if($request->isPost()){
            $data = input('post.');
            $data['is_show'] = 1;
            $data['reply_time'] = time();
            //数据入库
            if(Db::name('comment')->update($data))
            {
                    return ['status'=>1,'msg'=>'回复成功'];
            }else{
                    return ['status'=>0,'msg'=>'回复失败'];
            }
        }else{
            $comment_id=input('comment_id');
            //评论
            $info=Db::name('comment')
                        ->where('comment_id',$comment_id)
                        ->find(); 
            $this->assign('info',$info);
            return view();
        }        
    }
}