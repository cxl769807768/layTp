<?php
/**
 * 用户表模型
 */
namespace app\common\model;

use model\Base;
use think\facade\Config;
use think\model\concern\SoftDelete;

class User extends Base
{
    use SoftDelete;
    //模型名
    protected $name = 'user';

    //时间戳字段转换

    //是否设置创建时间字段，当设置$createTime = false时，为关闭create_time自动写入，默认值为$createTime = 'create_time'

    //是否设置更新时间字段，当设置$updateTime = false时，为关闭update_time自动写入，默认值为$updateTime = 'update_time'

    //是否设置删除时间字段，当设置$deleteTime = false时，为关闭delete_time自动写入，默认值为$deleteTime = 'delete_time'


    //表名


    //数组常量
    public $const = [
        'status' => [
            '1'=>'正常'
            ,'2'=>'锁定'
        ],
        'vip_type' => [
            '0'=>'否'
            ,'1'=>'永久会员'
            ,'2'=>'年度会员'
            ,'3'=>'季度会员'
        ],
    ];

    //关联模型
    public function setAvatarAttr($avatar){

        return $avatar ? (strpos($avatar,Config::get('laytp.upload.domain'))===false ? $avatar : str_replace(Config::get('laytp.upload.domain'),'',$avatar)) : '/static/index/image/service.jpg';
    }
    public function getAvatarAttr($avatar){
        return $avatar ?  Config::get('laytp.upload.domain').$avatar:Config::get('laytp.upload.domain').'/static/index/image/service.jpg';
    }
    public function setNicknameAttr($value){
        return $value ? : GetRandStr(6);
    }

    public function getVipTypeAttr($val){
        return $this->const['vip_type'][$val];
    }

}
