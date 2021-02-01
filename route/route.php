<?php

use think\facade\Route;

//获取验证码图片地址
Route::get('verifyCode', '\app\common\controller\Common@verifyCode');
//Route::get('user', '\app\index\controller\User\index');

return [
    //api
    'api/:controller/:function'=>'api/:controller/:function',// 有方法名时
    'api/:controller'=>'api/:controller/index',// 省略方法名时


    //web

     'login/:function'=>'index/Login/:function',// 有方法名时
    'login'=>'index/Login/index',// 省略方法名时

    'user/:function'=>'index/User/:function',// 有方法名时
    'user'=>'index/User/index',// 省略方法名时

    'vip/:function'=>'index/Vip/:function',// 有方法名时
    'vip'=>'index/Vip/index',// 省略方法名时

];
