<?php

namespace app\api\controller;
use think\Loader;

Loader::import('aliSms\lib\Core\Config');
Loader::import('aliSms\lib\Core\Profile\DefaultProfile');
Loader::import('aliSms\Core\DefaultAcsClient');
Loader::import('aliSms\lib\Api\Sms\Request\V20170525\SendSmsRequest');
use think\facade\Validate;



class Common extends Api{
    /**
     * api 数据返回
     * @param  [int] $code [结果码 200:正常/4**数据问题/5**服务器问题]
     * @param  [string] $msg  [接口要返回的提示信息]
     * @param  [array]  $data [接口要返回的数据]
     * @return [string]       [最终的json数据]
     */
    public function return_msg($code, $msg = '', $data = []) {
        /*********** 组合数据  ***********/
        $return_data['code'] = $code;
        $return_data['msg']  = $msg;
        $return_data['data'] = $data;
        /*********** 返回信息并终止脚本  ***********/
        echo json_encode($return_data);die;
    }
    /**
     * 检测用户名是否符合要求
     * @param $username
     * @return string
     *
     */
    public function check_username($username) {
        /*********** 判断是否为邮箱  ***********/
        $is_email = Validate::is($username, 'email') ? 1 : 0;
        /*********** 判断是否为手机  ***********/
        $is_phone = preg_match('/^1[345789]\d{9}$/', $username) ? 4 : 2;
        /*********** 最终结果  ***********/
        $flag = $is_email + $is_phone;
        switch ($flag) {
            /*********** not phone not email  ***********/
            case 2:
                return '';
                break;
            /*********** is email not phone  ***********/
            case 3:
                return 'email';
                break;
            /*********** is phone not email  ***********/
            case 4:
                return 'phone';
                break;
        }

    }

    /**
     * @param $value
     * @param $type  手机号或者邮箱
     * @param $exist  1是必须存在  0是不用必须存在
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function check_exist($value, $type, $exist) {
        $type_num  = $type == "phone" ? 2 : 4;
        $flag      = $type_num + $exist;
        $phone_res = model('User')->where('phone', $value)->find();
        $email_res = model('User')->where('email', $value)->find();
        switch ($flag) {
            /*********** 2+0 phone need no exist  ***********/
            case 2:
                if ($phone_res) {
                    return ['status' => false, 'info'=> '此手机号已注册!'];
                }
                break;
            /*********** 2+1 phone need exist  ***********/
            case 3:
                if (!$phone_res) {
                    return ['status' => false, 'info'=> '此手机号不存在!'];
                }
                break;
            /*********** 4+0 email need no exist  ***********/
            case 4:
                if ($email_res) {
                    return ['status' => false, 'info'=> '此邮箱已被占用!'];
                }
                break;
            /*********** 4+1 email need  exist  ***********/
            case 5:
                if (!$email_res) {
                    return ['status' => false, 'info'=> '此邮箱不存在!'];
                }
                break;
        }
        $phone_res = empty($phone_res) ? array() :$phone_res->toArray();
        $email_res = empty($email_res) ? array() :$email_res->toArray();
        return ['status' => true, 'info'=> '','data'=>$type=='phone'?$phone_res:$email_res];

    }
    /* $scene  admin是后台
     * $exist  1是必须存在  0是不用必须存在
     */
    public function check_username_exist($value, $exist) {

        $res = model('User')->where('username|phone', $value)->find();

        $res = empty($res) ? array() :$res->toArray();
        switch ($exist) {
            /*********** 2+0 phone need no exist  ***********/
            case 1:
                if (!$res) {
                    return ['status' => false, 'info'=> '用户不存在!请先注册'];
                }
                break;
            /*********** 2+1 phone need exist  ***********/
            case 0:
                if ($res) {
                    return ['status' => false, 'info'=> '此账户已注册!'];
                }
                break;
        }
        return ['status' => true, 'info'=> '','data'=>$res];

    }

}