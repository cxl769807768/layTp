<?php
namespace app\index\controller;

use think\Controller;

class Login extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    public function save()
    {
        if($this->request('method') == 'post'){
            $mobile = $this->request('mobile');
            $code = $this->request('code');

        }
    }
}
