<?php
/**
 * 用户服务
 *  注册登录以及获取登录用户信息
 */
namespace app\common\service;
use service\Service;


class Sms extends Service
{
    protected static $instance;

    /**
     * 单例
     * @param array $options
     * @return static
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }


    /**
     * 检测code是否存在，是否正确
     * @param $user_name
     * @param $code
     */
    public function checkCode($user_name, $code) {
        /*********** 检测是否超时  ***********/
        $check = model('SmsCode')->where(['mobile'=>$user_name,'code'=>$code])->find();
        if(empty($check)){
            $this->setError('验证码不存在');
            return false;
        }
        /*********** 检测验证码是否正确  ***********/
        if (time()>$check['exceed_time']) {
            $this->setError('验证超时!请在五分钟内验证!');
            return false;

        }
//        model('SmsCode')->where(['mobile'=>$user_name,'code'=>$code])->delete();
        return true;
    }
}