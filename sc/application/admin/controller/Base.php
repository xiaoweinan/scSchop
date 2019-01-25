<?php
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\Session;
class Base extends Controller
{
    public function _initialize(){
        if(!Session::has('admin.admin_id')){
            $this->redirect('Login/login');
        }
    }
}