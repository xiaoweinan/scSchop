<?php
namespace app\admin\controller;

use think\Request;
use app\admin\controller\Base;
use think\Db;

class Adver extends Base
{
    /**
     * 后台资讯管理
     */
    public function adverList()
    {
        $advers = Db::name('adver')
                            ->alias('a')
                            ->join('adver_position p', 'p.position_id = a.pid')
                            ->order('position_id','asc')
                            ->select();
        $adver = Db::name('adver')->paginate(10);
        $page = $adver->render();
        return view('', ['advers' => $advers, 'page' => $page]);
    }

    /**
     * 后台添加资讯
     */
    public function adverAdd(Request $request)
    {
        //显示所有类型
        $types = Db::name('adver_position')->select();
        $this->assign('types', $types);
        //显示所有商品分类
        $cates = Db::name('product_category')->where('level',1)->select();
        $this->assign('cates',$cates);
        //判断是否是post传值
        if ($request->isPost()) {
            $data = input('post.');
            $bgcolor = $data['bgcolor'];
            // 保存图片
            $file = request()->file('ad_code');

            // 移动到框架应用根目录/public/uploads/ 目录下
            if ($file) {
                $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
                if ($info) {
                    // 成功上传后 获取上传信息
                    // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                    $data['ad_code'] = DS . 'uploads' . DS . $info->getSaveName();
                }
                //数据入库
                $data['click_count'] = 0;
                $data['enabled'] = 1;
                $data['target'] = 1;


                if (Db::name('adver')->insertGetId($data)) {
                    return ['status' => 1, 'msg' => '添加成功'];
                } else {
                    return ['status' => 0, 'msg' => '添加失败'];
                }
            }
        }
        return view();
    }
    /**
     *广告删除
     */
    public function adverdel(Request $request)
    {
        $article_id = input('post.ad_id');
        if($request->isPost()){
            if (Db::name('adver')->delete($article_id)) {
                return 1;
            } else {
                return 0;
            }
        }

    }

    /**
     *广告批量删除
     */
    public function adverdels()
    {
        $ids = input('post.ids');
        $ids = explode(',', substr($ids, 0, -1));
        if (Db::name('article')->delete($ids)) {
            return ['status' => 1, 'msg' => ''];
        } else {
            return ['status' => 0, 'msg' => ''];
        }
    }

    /**
     * 广告位置页面
     */
    public function adverposition()
    {
        $adverpositions = Db::name('adver_position')->select();
        $adver = Db::name('adver_position')->paginate(10);
        $page = $adver->render();
        return view('', ['adverpositions' => $adverpositions, 'page' => $page]);
    }
    /**
     * 广告位置页面添加
     */
    public function adverpositionAdd(Request $request)
    {
        $cates = Db::name('product_category')->where('level',1)->select();
        $this->assign('cates',$cates);
        if($request->isPost()){
            $data = input('post.');
            $data['position_style'] = 0;
            if(Db::name('adver_position')->insertGetId($data)){
                return ['stauts'=>1,'msg'=>'操作成功'];
            }else{
                return ['stauts'=>0,'msg'=>'操作失败'];
            }
        }
        return view();
    }
    /**
     * 获取菜单信息
     */
    public function getmenu()
    {
        // 获取菜单信息
        $menus = [];
        // (1) 获取顶级菜单进行处理
        $parents = Db::name('article_classfiy')
            ->where('parent_id', 0)
            ->select();
        $ids = [];
        foreach ($parents as $parent) {
            $menus[$parent['id']] = $parent;
            $ids[] = $parent['id'];
        }
        // (2) 通过 $ids 获取所有的顶级菜单的子菜单
        $childs = Db::name('article_classfiy')
            ->where('parent_id', 'in', $ids)
            ->select();
        // (3) 处理子菜单
        foreach ($childs as $child) {
            $menus[$child['parent_id']]['childNodes'][] = $child;
        }
        return $menus;
    }

    /**      $datas = Db::name('goods_type')
     * ->where($where)
     * ->order('type_id ASC')
     * ->paginate(20, false, ['query'=> $condition]);
     * $this->assign('condition', $condition);
     * 数组处理
     * @param  [type]  $arr       [description]
     * @param  integer $parent_id [description]
     * @return [type]             [description]
     */
    public function recursiveCategory($arr, $parent_id = 0)
    {
        static $array = [];
        foreach ($arr as $value) {
            if ($value['parent_id'] == $parent_id) {
                $array[] = $value;
                $this->recursiveCategory($arr, $value['id']);
            }
        }
        return $array;
    }
}


