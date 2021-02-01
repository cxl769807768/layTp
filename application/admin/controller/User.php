<?php

namespace app\admin\controller;

use controller\Backend;
use think\Exception;
use think\facade\Config;

/**
 * 后台管理员表
 */
class User extends Backend
{
    public $has_del=1;
    public $has_soft_del=1;
    /**
     * admin_user模型对象
     * @var app\common\model\User
     */
    protected $model;

    public function initialize(){
        parent::initialize();
        $this->model = new \app\common\model\User();

        $this->assign('has_soft_del',0);//是否拥有回收站功能
        $this->assign('has_del',1);//是否拥有删除功能
    }

    //添加
    public function add(){
        if( $this->request->isAjax() && $this->request->isPost() ){
            $post = filterPostData($this->request->post("row/a"));
            if( $post['password'] != $post['re_password']){
                return $this->error('两次密码输入不相同');
            }
            $post['password'] = password_hash($post['password'], PASSWORD_DEFAULT);
            $post['avatar'] = $post['avatar'] ? $post['avatar'] : '/static/index/image/service.jpg';

            $unique_username = $this->model->withTrashed()->where('mobile','=',$post['mobile'])->find();
            if($unique_username){
                return $this->error('手机号:'.$post['username'].' 已存在');
            }

            if( $this->model->save($post) ){
                return $this->success('操作成功');
            }else{
                return $this->error('操作失败');
            }
        }
        return $this->fetch();
    }

    //设置状态
    public function set_status(){
        $field = $this->request->param('field');
        $field_val = $this->request->param('field_val');
        $save[$field] = $field_val;
        $ids = $this->request->param('ids');
        $ids_arr = explode(',',$ids);
        if(in_array(1,$ids_arr)){
            if($field == 'is_super_manager' && !$field_val){
                return $this->error('ID为1的管理员不能设置成非超管');
            }
        }
        try{
            return $this->success('操作成功');

        }catch (Exception $e){
            return $this->error($e->getMessage());
        }
    }

    //编辑
    public function edit(){
        $edit_where['id'] = $this->request->param('id');

        if( $this->request->isAjax() && $this->request->isPost() ){
            $post = filterPostData($this->request->post("row/a"));
            if( $post['password'] != $post['re_password']){
                return $this->error('两次密码输入不相同');
            }
            if($post['password']){
                $post['password'] = password_hash($post['password'], PASSWORD_DEFAULT);
                unset($post['re_password']);
            }else{
                unset($post['password']);
                unset($post['re_password']);
            }
            $post['avatar'] = !empty($post['avatar'])  ? str_replace(Config::get('laytp.upload.domain'),'',$post['avatar']) :  '/static/index/image/service.jpg';
            $update_res = $this->model->where($edit_where)->update($post);
            if( $update_res || $update_res === 0 ){
                return $this->success('操作成功');
            }else if( $update_res === null ){
                return $this->error('操作失败');
            }
        }
        $assign = $this->model->where($edit_where)->find()->toArray();
        $this->assign($assign);
        return $this->fetch();
    }

    //删除
    public function del(){
        $ids = $this->request->param('ids');
        $ids_arr = explode(',',$ids);
        if(!$this->has_del){
            return $this->error('控制器没有删除功能');
        }else{

            if( $this->model->where('id','in',$ids)->delete() ){
                return $this->success('操作成功');
            }else{
                return $this->error('操作失败');
            }
        }
    }
}
