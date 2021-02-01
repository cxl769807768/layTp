<?php
namespace app\index\controller;

use think\Controller;
use think\facade\Config;

class Vip extends Controller
{
    public function index()
    {
        $url = Config::get('laytp.vip_address.address');
        $this->assign('url',$url);
        return $this->fetch();
    }
}
