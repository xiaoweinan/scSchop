<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use think\Request;
use think\Db;
class Product extends Base
{
    /**
     * 后台品牌管理
     */
    public function productBrand()
    {
        $data = Db::name('brand')
                                ->alias('b')
                                ->field('b.*, c.name cat_name')
                                ->join('product_category c', 'b.cat_id=c.id')
                                ->select();
        $this->assign('data', $data);
        return view();
    }

    /**
     * 品牌添加
     */
    public function productBrandAdd(Request $request)
    {
        if($request->isPost()){
            $data = input('post.');
            $file = request()->file('image');
            $res = Db::name('brand')
                                ->where('brand_name', $data['brand_name'])
                                ->find();
            if($res){
                return ['code'=>0, 'msg'=>'该品牌名称已存在'];
            }
            if($file){
                $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
                if($info){
                    $data['logo'] = DS . 'uploads' . DS . $info->getSaveName();
                    // dump($data['logo']);
                    // exit;
                    $res = Db::name('brand')
                                        ->insertGetId($data);
                    if($res){
                        return ['code'=>1, 'msg'=>'添加成功'];
                    }else{
                        return ['code'=>0, 'msg'=>'添加失败'];
                    }
                }else{
                    return ['code'=>0, 'msg'=>'logo上传失败'];
                }
            }else{
                return ['code'=>0, 'msg'=>'logo上传失败'];
            }

            exit;
        }else{
            return view();
        }   
    }

    /**
     * 品牌编辑
     */
    public function productBrandEdit(Request $request)
    {   
        if($request->isAjax()){
            $data = input('post.');
            $res = Db::name('brand')
                            ->where('brand_id', '<>', $data['brand_id'])
                            ->where('brand_name', $data['brand_name'])
                            ->find();
            if($res){
                return ['code'=>0, 'msg'=>'该品牌名称已存在'];
            }
            $file = request()->file('image');
            if($file){
                $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
                if($info){
                    $data['logo'] = DS . 'uploads' . DS . $info->getSaveName();
                    unset($data['image']);
                }else{
                    return ['code'=>0, 'msg'=>'logo上传失败'];
                }
            }else{
                unset($data['image']);
            }
            Db::name('brand')
                            ->update($data);
            return ['code'=>1, 'msg'=>'编辑成功'];
        }else{
            $data = Db::name('brand')
                            ->find(input('get.id'));
            $this->assign('data', $data);
            return view();
        }  
    }

    /**
     * 品牌删除
     */
    public function productBrandDel()
    {
    	//获取被删除的品牌id
        $brand_id = input('post.brand_id');
        //判断品牌下是否存在商品
        $infos = Db::name('product')->where('brand_id',$brand_id)->select();
        if (empty($infos)) {
        	$res = Db::name('brand')
	                            ->delete($brand_id);
	        if($res){
	            return ['code'=>1, 'msg'=>'删除成功'];
	        }else{
	            return ['code'=>0, 'msg'=>'删除失败'];
	        }
        }else{
	            return -1;
	        }
	        
    }

    /**
     * 批量删除品牌
     */
    public function productBrandsDel()
    {
        if(!input('post.ids')){
            return ['code'=>0, 'msg'=>'请选择要删除的品牌'];
        }
        $ids = explode(',', substr(input('post.ids'), 0, -1));
        //根据id判断品牌下是否存在商品
        foreach($ids as $id){
        	$infos = Db::name('product')->where('brand_id',$id)->select();
        
        }
        if (empty($infos)) {
	        $res = Db::name('brand')->delete($ids);
	        if($res){
	            return ['code'=>1, 'msg'=>'删除成功'];
	        }else{
	            return ['code'=>0, 'msg'=>'删除失败'];
	        }
	    }else{
	            return -1;
	        }
    }

     /**
     * 后台分类管理
     */
    public function productCategory($id=0,$level=0)
    {
      //数据总数
      $all = Db::name('product_category')->count();
      $this->assign('all',$all);
      //显示所有分类
      $cates = Db::name('product_category')
                  ->order('parent_id','asc')       
                  ->select();
        $this->assign('cates',$this->recursiveCategory($cates));
        return view();
    }
    /**     
     * 数组处理
     * @param  [type]  $arr       [description]
     * @param  integer $parent_id [description]
     * @return [type]             [description]
     */
    public function recursiveCategory($arr,$parent_id=0){
      //无限循环分类（递归分类）
        static $array = [];
        foreach ($arr as $value) {
            if($value['parent_id'] == $parent_id){
                $array[] = $value;
                $this->recursiveCategory($arr,$value['id']);
            }
        }
        return $array;
    }
    /**
     * 后台添加分类管理
     */
    public function productCategoryAdd(Request $request)
    {
      //显示所有分类
      $cates = Db::name('product_category')
                                    ->where('level','<',3)
                                    ->order('parent_id','asc')
                                    ->select();
      $this->assign('cates',$this->recursiveCategory($cates));
      //添加数据库
      if ($request->ispost()) {
        $data = input('post.');
      if ($data['parent_id'] != 0) {
          $arr =  Db::name('product_category')->where('id',$data['parent_id'])->find();
          $data['level'] = $arr['level']+1;    
      }else{
        $data['level'] = 1;  
      }
        $data['is_hot'] = 0;
            $data['parent_id_path'] = 0;     
        if(Db::name('product_category')->insertGetId($data)){
                $id = Db::name('product_category')->getLastInsID();
                if ($data['level']==1) {
                    $data['parent_id_path'] = '0_'.$id;
                }elseif($data['level']==2){
                    $data['parent_id_path'] = '0_'.$data['parent_id'].'_'.$id;
                }elseif($data['level']==3){
                    $arr =  Db::name('product_category')->where('id',$data['parent_id'])->find();
                    $data['parent_id_path'] = '0_'.$arr['parent_id'].'_'.$data['parent_id'].'_'.$id;
                }else{
                    $data['parent_id_path'] = '超过三级';
                }
                    if(Db::name('product_category')->where('id',$id)->update(['parent_id_path'=>$data['parent_id_path'] ])){
                        return ['status' =>1 , 'msg' =>'操作成功' , 'id' =>$id];
                    }else{
                  return ['status' =>0 , 'msg' =>'操作失败'];
                    }
                }
      }
        return view();
    }
    /**
     * 后台编辑分类管理
     */
    public function productCategoryEdit(Request $request)
    {
      //获取点击的id
      $id = $request->param('id');
      //显示所有分类
      $cates = Db::name('product_category')
                                    ->where('level','<',3)
                                    ->order('parent_id','asc')
                                    ->select();
      $this->assign('cates',$this->recursiveCategory($cates));
      //根据id获取对应的数据
      $cateEdit = Db::name('product_category')
                  ->where('id' , $id)
                  ->find();
      $this->assign('cateEdit' ,$cateEdit); 
      //更新数据
      if ($request->isPost()) {
        $data = input('post.');
        if(!empty($data['parent_id'])){
        if ($data['parent_id'] != 0) {
          $arr =  Db::name('product_category')->where('id',$data['parent_id'])->find();
          $data['level'] = $arr['level'];    
      }else{
        $data['level'] = 1;  
      }
         if ($data['level']==1) {
                    $data['parent_id_path'] = '0_'.$data['id'];
                }elseif($data['level']==2){
                    $data['parent_id_path'] = '0_'.$data['parent_id'].'_'.$data['id'];
                }elseif($data['level']==3){
                    $arr =  Db::name('product_category')->where('id',$data['parent_id'])->find();
                    $data['parent_id_path'] = '0_'.$arr['parent_id'].'_'.$data['parent_id'].'_'.$data['id'];
                }else{
                    $data['parent_id_path'] = '超过三级';
                }
              }
        if(Db::name('product_category')->update($data)){
          return ['status'=>1, 'msg'=>'编辑成功'];    
        }else{
          return ['status'=>0, 'msg'=>'编辑失败'];    
        }
      }    
        return view();
    }
    /**
     * 后台删除分类管理
     */
    public function productCategoryDel(Request $request)
    {
        $id = input('post.id');
        $infos = Db::name('product_category')
        							->alias('c')
        							->join('product p','p.cat_id=c.id')
        							->where('c.id',$id)
        							->select();
        //通过id获取等级
        //$c = Db::name('product_category')->where('id',$id)->find();
        $c1 = Db::name('product_category')->where('parent_id',$id)->count();
       	//$parent_id_path = $c['parent_id_path'];
        //$parent = explode('_',$parent_id_path);
        //dump($parent);
        //exit;
        //foreach($parent as $p){
        //	$data = ['parent_id_path'=>$p];
       	// }
        //通过level判断有没有下级分类
        //$c1 = Db::name('product_category')
		//				        ->where('parent_id_path',$data)
		//				        ->select();
		//dump($c1);
		//exit;
		if (empty($c1)) {
			if(empty($infos)){    
		        if(Db::name('product_category')->delete($id)){
		            return ['status'=>1, 'msg'=>'删除成功'];
		        }else{
		            return ['status'=>0, 'msg'=>'删除失败'];
		        }
	        }else{
	        	return -1;
	        }
		}else{
	        	return -1;
	        }
	        
        return view();
    }  
    /**
     * 后台分类状态的改变
     */
     public function cateLock(Request $request)
    {
        $id = input('post.id');
        $type = input('post.type');
        $val = input('post.val');
        
        if($type == 'is_show'){
          if($val == 0){
              $res = Db::name('product_category')->where('id', $id)->update(['is_show'=>0]);
              if($res){
                  return 'true';
              }else{
                  return '11111';
              }
          }else{
              $res = Db::name('product_category')->where('id', $id)->update(['is_show'=>1]);
              if($res){
                  return 'true';
              }else{
                  return '222222';
                }
            }
      }else{
        if($val == 0){
              $res = Db::name('product_category')->where('id', $id)->update(['is_hot'=>0]);
              if($res){
                  return 'true';
              }else{
                  return '333333';
              }
          }else{
              $res = Db::name('product_category')->where('id', $id)->update(['is_hot'=>1]);
              if($res){
                  return 'true';
              }else{
                  return '44444';
                }
              }
          }
      }       

    /**
     * 产品模型
     */
    public function productModelList()
    {       
        $models = Db::name('product_model')->select();
        $this->assign('models',$models);
        return view();
    }
    /**
     * 产品模型添加
     */
    public function productModelAdd(Request $request)
    {       
        if($request->isPost()){
            $infos = input('post.');
            if(Db::name('product_model')->insertGetId($infos)){
                return ['status'=>1 , 'msg' => '操作成功'];
            }else{
                return ['status'=>0 , 'msg' => '操作失败'];
            }
        }
        return view();
    }
    /**
     * 产品模型编辑
     */
    public function productModelEdit(Request $request)
    {       
        $id = $request->param('id');
        $model = Db::name('product_model')
                                    ->where('id',$id)
                                    ->find();
        $this->assign('model',$model);
        //更新数据库
        if($request->isAjax()){
            $infos = input('post.');
            if(Db::name('product_model')->update($infos)){
                return ['status'=>1 , 'msg' => '操作成功'];
            }else{
                return ['status'=>0 , 'msg' => '操作失败'];
            }
        }
        return view();
    }
    /**
     * 产品模型删除
     */
    public function productModelDel(Request $request)
    {       
        $id = input('post.id');
        //根据id判断模型下是否存在数据
        $infos = Db::name('product_spec')->where('type_id',$id)->select();
        $infos2 = Db::name('product_attribute')->where('type_id',$id)->select();
        								
        if(empty($infos)||empty($infos2)){
	        if(Db::name('product_model')->delete($id)){
	            return ['status'=>1, 'msg'=>'删除成功'];
	        }else{
	            return ['status'=>0, 'msg'=>'删除失败'];
	        }
        }else{
	        return -1;
	        }
        
    }
    /**
     * 产品模型批量删除
     */
    public function productModelDels(Request $request)
    {       
        if(!input('post.ids')){
            return -1;
        }
        $ids = explode(',', substr(input('post.ids'), 0, -1));
        $res = Db::name('product_model')->delete($ids);
        if($res){
            return 1;
        }else{
            return 0;
        }
    }

    /**
     * 产品属性
     */
    public function productAttribute()
    {       
        //显示所有分类
        $attrs = Db::name('product_attribute')
                                ->alias('p')
                                ->join('product_model m','p.type_id = m.id')
                                ->order('type_id','asc')
                                ->select();                 
        $this->assign('attrs',$attrs);
        return view();
    }
    /**
     * 产品属性添加
     */
    public function productAttributeAdd(Request $request)
    {       
        //显示所有模型
        $models = Db::name('product_model')->select();
        $this->assign('models' , $models);
        //添加至数据库
        if($request->isPost()){
            $infos = input('post.');
            $infos['attr_type'] = 0;
            if(Db::name('product_attribute')->insertGetId($infos)){
                return ['status'=>1 , 'msg' => '操作成功'];
            }else{
                return ['status'=>0 , 'msg' => '操作失败'];
            }
        }
        return view();
    }
    /**
     * 产品属性编辑
     */
    public function productAttributeEdit(Request $request)
    {       
        //显示所有模型
        $models = Db::name('product_model')->select();
        $this->assign('models' , $models);
        //获取id
        $attr_id = $request->param('attr_id');
        $attr = Db::name('product_attribute')
                                    ->where('attr_id',$attr_id)
                                    ->find();
        $this->assign('attr',$attr);
        //更新数据库
        if($request->isAjax()){
            $infos = input('post.');
            if(Db::name('product_attribute')->update($infos)){
                return ['status'=>1 , 'msg' => '操作成功'];
            }else{
                return ['status'=>0 , 'msg' => '操作失败'];
            }
        }
        return view();
    }
    /**
     * 产品属性删除
     */
    public function productAttributeDel(Request $request)
    {       
        $attr_id = input('post.attr_id');
        if($attr_id == 1){
            return -1;
        }
        if(Db::name('product_attribute')->delete($attr_id)){
            return ['status'=>1, 'msg'=>'删除成功'];
        }else{
            return ['status'=>0, 'msg'=>'删除失败'];
        }
    }
    /**
     * 产品属性批量删除
     */
    public function productAttributeDels(Request $request)
    {       
        if(!input('post.ids')){
            return -1;
        }
        $ids = explode(',', substr(input('post.ids'), 0, -1));
        $res = Db::name('product_attribute')->delete($ids);
        if($res){
            return 1;
        }else{
            return 0;
        }
    }

    /**
     * 后台产品管理
     */
    public function productList()
    {   
        $data = Db::name('product')
                            ->alias('p')
                            ->join('product_category c', 'p.cat_id = c.id')
                            ->join('brand b', 'p.brand_id = b.brand_id')
                            ->join('product_model m', 'p.type_id = m.id')
                            ->field('p.*, c.name cat_name, b.brand_name, m.id type_id')
                            ->select();
        // dump($data);
        // exit;
        $this->assign('data', $data);
        return view();
    }

    /**
     * 产品添加规格
     */
    public function productItemAdd(Request $request)
    {       
        if($request->isAjax()){
            $data = input('post.');
            $info = [];
            $info['good_id'] = $data['good_id'];
            foreach($data['id'] as $vo){
                $info['item_id'] = $vo;
                Db::name('product_item')
                            ->insertGetId($info);
            }
            return ['code'=>1, 'msg'=>'操作成功'];
        }else{
            $type_id = input('get.type_id');
            $spec = Db::name('product_spec')
                            ->where('type_id', $type_id)
                            ->select();
            $item = Db::name('product_spec_item')
                            ->select();
            $this->assign('good_id',input('good_id'));
            $this->assign('item', $item);
            $this->assign('spec', $spec);
            return view();
        }   
    }

    /**
     * 后台添加产品
     */
    public function productAdd(Request $request)
    {
        if($request->isAjax()){
            $datas = input('post.');
            $datas['cost_price'] = 0;
            $data = [];
            foreach($datas as $key=>$value){
                if(!is_int($key)){
                   $data[$key] = $value;
                }
            }
            $files = $request->file('img');
            // 判断产品名称是否重复
            $res = Db::name('product')
                            ->where('good_name', $data['good_name'])
                            ->find();
            if($res){
                return ['code'=>0, 'msg'=>'已存在该商品'];
            }

            if($data['type_id'] == 0){
                return ['code'=>0, 'msg'=>'请选择产品类型'];
            }

            if(count($files) < 5){
                return ['code'=>0, 'msg'=>'请至少上传5张商品图片'];
            }

            // 保存图片
            $img_info = '';
            foreach($files as $file){
                $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
                $img_info .= DS . 'uploads' . DS . $info->getSaveName() . ',';
            }
            $data['img'] = substr($img_info, 0, -1);
            $data['add_time'] = time();
            
            unset($data['local_img']);
            // 入库
            $res = Db::name('product')
                                ->insertGetId($data);

            if($res){
                $attrs = [];
                // 属性值入库
                $attrs['good_id'] = $res;
                foreach($datas as $k=>$v){
                    if(is_int($k)){
                       $attrs['attr_id'] = $k;
                        $attrs['attr_value'] = $v;
                        $attr_res = Db::name('product_attr')
                                    ->insertGetId($attrs);
                        if(!$attr_res){
                            return ['code'=>0, 'msg'=>'属性添加失败'];
                        } 
                    }   
                }
                return ['code'=>1, 'msg'=>'操作成功'];
            }else{
                return ['code'=>0, 'msg'=>'操作失败'];
            }
        }else{
            $brand = Db::name('brand')
                            ->select();
            $types = Db::name('product_model')
                            ->select();
            $this->assign('brand', $brand);
            $this->assign('types', $types);
            return view();
        }     
    }

    /**
     * 分类三级
     */
    public function doCat()
    {
        $infos = Db::name('product_category')
                                ->where('parent_id', input('post.id'))
                                ->select();
        return $infos;
    }

    /**
     * 分类自动品牌
     */
    public function doBrand()
    {
        $brand = Db::name('brand')
                                ->where('cat_id', input('post.id'))
                                ->select();
        return $brand;
    }

    /**
     * 类型自动展示属性
     */
    public function doType()
    {
        $attr = Db::name('product_attribute')
                                ->where('type_id', input('post.id'))
                                ->select();
        return $attr;
    }

    /**
     * 产品编辑
     */
    public function productEdit(Request $request){
        if($request->isAjax()){
            $datas = input('post.');
            $data = [];
            foreach($datas as $key=>$value){
                if(!is_int($key)){
                   $data[$key] = $value;
                }
            }
            $files = $request->file('img');
            // 判断产品名称是否重复
            $res = Db::name('product')
                            ->where('good_id', '<>', $data['good_id'])
                            ->where('good_name', $data['good_name'])
                            ->find();
            if($res){
                return ['code'=>0, 'msg'=>'已存在该商品'];
            }

            if($data['type_id'] == 0){
                return ['code'=>0, 'msg'=>'请选择产品类型'];
            }
            if($files){
                if(count($files) < 5){
                return ['code'=>0, 'msg'=>'请至少上传5张商品图片'];
                }
                // 删除老图片
                $old_img = Db::name('product')
                                    ->field('img')
                                    ->find($data['good_id']);
                $old_img = explode(',', $old_img['img']);
                foreach($old_img as $vo){
                    unlink('.' . $vo);
                }
                // 保存新图片
                $img_info = '';
                foreach($files as $file){
                    $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
                    $img_info .= DS . 'uploads' . DS . $info->getSaveName() . ',';
                }
                $data['img'] = substr($img_info, 0, -1);
            }else{
                unset($data['img']);
            }         
            unset($data['local_img']);

            // 更新数据库
            Db::name('product')
                                ->update($data);
            
            // 属性值入库
            Db::name('product_attr')
                                ->where('good_id', $datas['good_id'])
                                ->delete();
            $attrs = [];
            $attrs['good_id'] = $datas['good_id'];
            foreach($datas as $k=>$v){
                if(is_int($k)){
                   $attrs['attr_id'] = $k;
                    $attrs['attr_value'] = $v;
                    $attr_res = Db::name('product_attr')
                                ->insertGetId($attrs);
                    if(!$attr_res){
                        return ['code'=>0, 'msg'=>'属性添加失败'];
                    } 
                }   
            }
            return ['code'=>1, 'msg'=>'操作成功'];
        }else{
            $good_id = input('get.id');
            $data = Db::name('product')
                                ->find($good_id);
            $cat = Db::name('product_category')
                                ->select();
            $brand = Db::name('brand')
                                ->select();
            $type = Db::name('product_model')
                                ->select();
            $attrs = Db::name('product_attr a')
                                ->join('product_attribute b', 'a.attr_id=b.attr_id')
                                ->field('a.attr_id,a.attr_value,b.attr_name')
                                ->where('good_id', $good_id)
                                ->select();
            $this->assign('attrs', $attrs);
            $this->assign('data', $data);
            $this->assign('cat', $cat);
            $this->assign('brand', $brand);
            $this->assign('type', $type);
            return view();
        } 
    }

    /**
     * 产品删除
     */
    public function productDel(){
        $good_id = input('post.good_id');
        $img = Db::name('product')
                            ->field('img')
                            ->find($good_id);
        $img = explode(',', $img['img']);
        foreach($img as $v){
            unlink('.' . $v);
        }
        $res = Db::name('product')
                            ->delete($good_id);
        if($res){
            return ['code'=>1, 'msg'=>'删除成功'];
        }else{
            return ['code'=>0, 'msg'=>'删除失败'];
        }
    }

    /**
     * 产品批量删除
     */
    public function productsDel(){
        if(!input('post.ids')){
            return ['code'=>0, 'msg'=>'请选择要删除的产品'];
        }
        $ids = explode(',', substr(input('post.ids'), 0, -1));
        $imgs = Db::name('product')
                            ->field('img')
                            ->where('good_id', 'in', $ids)
                            ->select();
        foreach($imgs as $v){
            $img = explode(',', $v['img']);
            foreach($img as $vo){
                unlink('.' . $vo);
            }
        }
        $res = Db::name('product')
                            ->delete($ids);
        if($res){
            return ['code'=>1, 'msg'=>'删除成功'];
        }else{
            return ['code'=>0, 'msg'=>'删除失败'];
        }
    }

    /**
     * 上下架
     */
    public function is_sale(){
        $good_id = input('post.good_id');
        $type = input('post.type');
        if($type == 0){
            // 下架
            $res = Db::name('product')->where('good_id', $good_id)->update(['is_sale'=>0]);
            if($res){
                return 'true';
            }else{
                return 'false';
            }
        }else{

            // 上架
            $res = Db::name('product')->where('good_id', $good_id)->update(['is_sale'=>1]);
            if($res){
                return 'true';
            }else{
                return 'false';
            }
        }
    }

    /**
     * 包邮否
     */
    public function shipping(){
        $good_id = input('post.good_id');
        $type = input('post.type');
        if($type == 0){
            // 不包邮
            $res = Db::name('product')->where('good_id', $good_id)->update(['is_free_shipping'=>0]);
            if($res){
                return 'true';
            }else{
                return 'false';
            }
        }else{

            // 包邮
            $res = Db::name('product')->where('good_id', $good_id)->update(['is_free_shipping'=>1]);
            if($res){
                return 'true';
            }else{
                return 'false';
            }
        }
    }

    /**
     * 推荐否
     */
    public function hot(){
        $good_id = input('post.good_id');
        $type = input('post.type');
        if($type == 0){
            // 不推荐
            $res = Db::name('product')->where('good_id', $good_id)->update(['is_hot'=>0]);
            if($res){
                return 'true';
            }else{
                return 'false';
            }
        }else{

            // 推荐
            $res = Db::name('product')->where('good_id', $good_id)->update(['is_hot'=>1]);
            if($res){
                return 'true';
            }else{
                return 'false';
            }
        }
    }

    /**
     * 商品规格列表
     */
    public function productSpec(){
        //查询所有规格
        $specs = Db::name('product_spec')
                            ->alias('p')
                            ->join('product_model m','p.type_id = m.id')
                            ->order('type_id','asc')
                            ->select();
        $this->assign('specs',$specs);
        //查询所有规格项
        $items = Db::name('product_spec')
                            ->alias('p')
                            ->join('product_spec_item i','i.spec_id = p.sp_id')
                            ->order('type_id','asc')
                            ->select();
        $this->assign('items',$items);
        return view();
    }
    /**
     * 商品规格添加
     */
    public function productSpecAdd(Request $request){
        $models = Db::name('product_model')->select();
        $this->assign('models',$models);
        //获取值
        if($request->isPost()){
            $infos = input('post.');
            $infos['search_index'] = 1;
            if(Db::name('product_spec')->insertGetId($infos)){
                return ['status'=>1,'msg'=>'操作成功'];
            }else{
                return ['status'=>0,'msg'=>'操作失败'];
            }
        }
        return view();
    }
    /**
     * 商品规格项添加
     */
    public function itemAdd(Request $request){
        $sp_id = $request->param('sp_id');
        $spec = Db::name('product_spec')
                                ->where('sp_id',$sp_id)
                                ->find();
        $this->assign('spec',$spec);
        //获取值
        if($request->isPost()){
            $infos = input('post.');
            if(Db::name('product_spec_item')->insertGetId($infos)){
                return ['status'=>1,'msg'=>'操作成功'];
            }else{
                return ['status'=>0,'msg'=>'操作失败'];
            }
        }
        return view();
    }
    /**
     * 商品规格项添加
     */
    public function itemDel(Request $request)
    {
            $id = input('post.id');

           
	            if(Db::name('product_spec_item')->delete($id)){
	                return ['status'=>1, 'msg'=>'删除成功'];
	            }else{
	                return ['status'=>0, 'msg'=>'删除失败'];
	            }
            
            
            return view();
    }
    /**
     * 商品规格编辑
     */
    public function productSpecEdit(Request $request){
        //获取id
        $sp_id = $request->param('sp_id');
        //显示所有模型
        $models = Db::name('product_model')->select();
        $this->assign('models',$models);
        //显示所有规格项
        $items = Db::name('product_spec_item')
                                ->where('spec_id',$sp_id)
                                ->select();
        $this->assign('items',$items);
        //根据id获取值
        $spec = Db::name('product_spec')
                                ->where('sp_id',$sp_id)
                                ->find();
        $this->assign('spec',$spec);
        //获取值
        if($request->isPost()){
            $infos = input('post.');
            if(Db::name('product_spec')->update($infos)){
                return ['status'=>1,'msg'=>'操作成功'];
            }else{
                return ['status'=>0,'msg'=>'操作失败'];
            }
        }
        return view();
    }
    /**
     * 商品规格删除
     */
    public function productSpecDel(Request $request){
        //获取id
        $sp_id = $request->param('sp_id');
        //根据id删除
        //检查规格下是否存在规格值
            $infos = Db::name('product_spec_item')->where('spec_id',$sp_id)->select();
           // dump($infos);
           // exit;
             if(empty($infos)){
			        if($request->isPost()){
			            $infos = input('post.');
			            if(Db::name('product_spec')->delete($infos)) {
			                if (Db::name('product_spec_item')->where('spec_id', $infos['sp_id'])->delete()) {
			                    return ['status' => 1, 'msg' => '操作成功'];
			                } else {
			                    return ['status' => 0, 'msg' => '操作失败'];
			                }
			            }
			        }
			  }else{
	                return -1;
	          }
        return view();
    }
    /**
     * 商品规格批量删除
     */
    public function productSpecDels(Request $request){

        if(!input('post.ids')){
            return -1;
        }
        $ids = explode(',', substr(input('post.ids'), 0, -1));
         foreach($ids as $id){
        	$infos = Db::name('product_spec_item')->where('spec_id',$id)->select();        
        }
        if (empty($infos)) {
        	if(Db::name('product_spec')->where('sp_id','in',$ids)->delete()) {
	            if (Db::name('product_spec_item')->where('spec_id','in', $ids)->delete()) {
	                return ['status'=>1,'msg'=>'操作成功'];
	            } else {
	                return ['status'=>0,'msg'=>'操作失败'];
	            }
	        }
        }else {
	                return -2;
	          }
	        

    }
    /**
     * 所有是否状态管理
     */
    public function specLock(){
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