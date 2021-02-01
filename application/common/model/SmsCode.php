<?php
/**
 * 用户表模型
 */
namespace app\common\model;

use model\Base;
use think\facade\Config;
use think\model\concern\SoftDelete;

class SmsCode extends Base
{
    use SoftDelete;
    //模型名
    protected $name = 'sms';
    protected $table = 'lt_sms_code';
    protected $updateTime = false;
    protected $deleteTime = false;

}
