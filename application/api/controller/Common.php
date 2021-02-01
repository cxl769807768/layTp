<?php
namespace app\api\controller;

use addons\aliyun_mobilemsg\service\Mobile;
use addons\aliyun_oss\service\Oss;
use addons\email\service\Email;
use addons\email\validate\Send;
use addons\qiniu_kodo\service\Kodo;

use Aliyun\Core\Config as AliConfig;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Core\Profile\DefaultProfile;



use app\admin\model\Attachment;
use app\admin\service\Addons;
use controller\Api;
use library\Random;
use think\Exception;
use think\facade\Config;
use think\Model;


/**
 * 公用接口
 * @ApiWeigh (100)
 */
class Common extends Api{

    public $no_need_login = ['send_email_code','check_email_code','send_mobile_code'];

    /**
     * @ApiTitle    (文件上传)
     * @ApiSummary  (文件上传，兼容阿里云OSS、七牛云KODO和本地上传，需安装对应插件)
     * @ApiMethod   (POST)
     * @ApiRoute    (/api/common/upload)
     * @ApiHeaders  (name="token", type="string", required="true", description="用户登录后得到的Token")
     * @ApiParams   (name="file", type="file", required="true", description="文件")
     * @ApiParams   (name="upload_dir", type="string", required="false", description="上传目录，允许为空", sample="avatar")
     * @ApiReturnParams   (name="err_code", type="integer", description="错误码.0=没有错误，表示操作成功；1=常规错误码，客户端仅需提示msg；其他错误码与具体业务相关，其他错误码举例：10401。前端需要跳转至登录界面。")
     * @ApiReturnParams   (name="msg", type="string", description="返回描述")
     * @ApiReturnParams   (name="time", type="integer", description="请求时间，Unix时间戳，单位秒")
     * @ApiReturnParams   (name="data", type="null", description="null")
     * @ApiReturn
({
    'err_code':0,
    'msg':'上传成功',
    'time':'15632654875',
    'data':null
})
     */
    public function upload(){
        try{
            $qiniu_upload_radio = Config::get('addons.qiniu_kodo.open_status');
            if(!$qiniu_upload_radio){
                $qiniu_upload_radio = 'close';
            }
            $aliyun_oss_upload_radio = Config::get('addons.aliyun_oss.open_status');
            if(!$aliyun_oss_upload_radio){
                $aliyun_oss_upload_radio = 'close';
            }
            $local_upload_radio = Config::get('laytp.upload.radio');
            if($qiniu_upload_radio == 'close' && $aliyun_oss_upload_radio == 'close' && $local_upload_radio == 'close'){
                $this->error('未开启上传方式');
            }

            $file = $this->request->file('file'); // 获取上传的文件
            if(!$file){
                $this->error('上传失败,请选择需要上传的文件');
            }
            $info       = $file->getInfo();
            $path_info  = pathinfo($info['name']);
            $file_ext   = strtolower($path_info['extension']);
            $save_name  = date("Ymd") . "/" . md5(uniqid(mt_rand())) . ".{$file_ext}";
            $upload_dir = $this->request->param('upload_dir');
            $upload_dir = $upload_dir ? $upload_dir . '/' : '';

            $object     = $upload_dir . $save_name;//上传至阿里云或者七牛云的文件名

            $upload = Config::get('laytp.upload');
            preg_match('/(\d+)(\w+)/', $upload['maxsize'], $matches);
            $type = strtolower($matches[2]);
            $typeDict = ['b' => 0, 'k' => 1, 'kb' => 1, 'm' => 2, 'mb' => 2, 'gb' => 3, 'g' => 3];
            $size = (int)$upload['maxsize'] * pow(1024, isset($typeDict[$type]) ? $typeDict[$type] : 0);
            if ($info['size'] > (int) $size) {
                $this->error('上传失败,文件大小超过'.$upload['maxsize']);
                return false;
            }

            $suffix = strtolower(pathinfo($info['name'], PATHINFO_EXTENSION));
            $suffix = $suffix && preg_match("/^[a-zA-Z0-9]+$/", $suffix) ? $suffix : 'file';

            $mimetypeArr = explode(',', strtolower($upload['mimetype']));
            $typeArr = explode('/', $info['type']);

            //禁止上传PHP和HTML文件
            if (in_array($info['type'], ['text/x-php', 'text/html']) || in_array($suffix, ['php', 'html', 'htm'])) {
                $this->error('上传失败,文件类型被禁止上传');
            }
            //验证文件后缀
            if ($upload['mimetype'] !== '*' &&
                (
                    !in_array($suffix, $mimetypeArr)
                    || (stripos($typeArr[0] . '/', $upload['mimetype']) !== false && (!in_array($info['type'], $mimetypeArr) && !in_array($typeArr[0] . '/*', $mimetypeArr)))
                )
            ) {
                $this->error('上传失败,文件类型被禁止上传');
            }

            $file_url = '';
            $local_file_url = '';
            //上传至七牛云
            if($qiniu_upload_radio == 'open'){
                $qiniu_yun = Kodo::instance();
                $qiniu_yun->upload($info['tmp_name'],$object);
                $file_url = Config::get('addons.qiniu_kodo.domain') . '/' . $object;

                $add['file_type'] = $this->request->param('accept');
                $add['file_path'] = $file_url;
                Attachment::create($add);
            }

            //上传至阿里云
            if($aliyun_oss_upload_radio == 'open'){
                $oss = Oss::instance();
                $file_url = $oss->upload($info['tmp_name'], $object);

                $add['file_type'] = $this->request->param('accept');
                $add['file_path'] = $file_url;
                Attachment::create($add);
            }

            //本地上传
            if($local_upload_radio == 2){
                $move_info = $file->move('uploads'); // 移动文件到指定目录 没有则创建
                $save_name = str_replace('\\','/',$move_info->getSaveName());
                $local_file_url = Config::get('laytp.upload.domain').'/uploads/'.$save_name;

                $add['file_type'] = $this->request->param('accept');
                $add['file_path'] = $local_file_url;
                Attachment::create($add);
            }

            $this->success('上传成功',$file_url ? $file_url : $local_file_url);
        }catch (Exception $e){
            $this->error('上传失败,' . $e->getMessage());
        }
    }

    /**
     * @ApiTitle    (发送邮箱验证码)
     * @ApiSummary  (发送邮箱验证码)
     * @ApiMethod   (POST)
     * @ApiRoute    (/api/common/send_email_code)
     * @ApiParams   (name="email", type="string", required="true", description="邮箱")
     * @ApiParams   (name="event", type="string", required="true", sample="register",description="事件名称")
     * @ApiReturnParams   (name="err_code", type="integer", description="错误码.0=没有错误，表示操作成功；1=常规错误码，客户端仅需提示msg；其他错误码与具体业务相关，其他错误码举例：10401。前端需要跳转至登录界面。")
     * @ApiReturnParams   (name="msg", type="string", description="返回描述")
     * @ApiReturnParams   (name="time", type="integer", description="请求时间，Unix时间戳，单位秒")
     * @ApiReturnParams   (name="data", type="null", description="null")
     * @ApiReturn
({
    'err_code':0,
    'msg':'发送成功',
    'time':'15632654875',
    'data':null
})
     */
    public function send_email_code(){
        $addons_service = new Addons();
        $addon = $addons_service->_info->getAddonInfo('email');
        if(!$addon){
            $this->error('请先安装Email插件');
        }
        if(!$addon['state']){
            $this->error('Email插件已关闭');
        }

        $params['email'] = $this->request->param('email');
        $params['event'] = $this->request->param('event');

        $validate = new Send();
        if($validate->check($params)){
            $email_service = new Email();
            if($email_service->send($params['email'],$params['event'],['code'=>Random::numeric()])){
                $this->success('发送成功');
            }else{
                $this->error($email_service->getError());
            }
        }else{
            $this->error('发送失败,'.$validate->getError());
        }
    }

    /**
     * @ApiTitle    (验证邮箱验证码)
     * @ApiSummary  (验证邮箱验证码)
     * @ApiMethod   (POST)
     * @ApiRoute    (/api/common/check_email_code)
     * @ApiParams   (name="email", type="string", required="true", description="邮箱")
     * @ApiParams   (name="event", type="string", required="true", sample="register",description="事件名称")
     * @ApiParams   (name="code", type="string", required="true", sample="register",description="邮箱验证码")
     * @ApiReturnParams   (name="err_code", type="integer", description="错误码.0=没有错误，表示操作成功；1=常规错误码，客户端仅需提示msg；其他错误码与具体业务相关，其他错误码举例：10401。前端需要跳转至登录界面。")
     * @ApiReturnParams   (name="msg", type="string", description="返回描述")
     * @ApiReturnParams   (name="time", type="integer", description="请求时间，Unix时间戳，单位秒")
     * @ApiReturnParams   (name="data", type="null", description="null")
     * @ApiReturn
({
    'err_code':0,
    'msg':'发送成功',
    'time':'15632654875',
    'data':null
})
     */
    public function check_email_code(){
        $addons_service = new Addons();
        $addon = $addons_service->_info->getAddonInfo('email');
        if(!$addon){
            $this->error('请先安装Email插件');
        }
        if(!$addon['state']){
            $this->error('Email插件已关闭');
        }

        $params['email'] = $this->request->param('email');
        $params['event'] = $this->request->param('event');
        $params['code'] = $this->request->param('code');

        $email_service = new Email();
        if($email_service->checkCode($params['email'],$params['event'],$params['code'])){
            $this->success('验证成功');
        }else{
            $this->error('验证失败,'.$email_service->getError());
        }
    }

    /**
     * @ApiTitle    (发送手机验证码)
     * @ApiSummary  (发送手机验证码)
     * @ApiMethod   (POST)
     * @ApiRoute    (/api/common/send_mobile_code)
     * @ApiHeaders  (name="token", type="string", required="true", description="用户登录后得到的Token")
     * @ApiParams   (name="mobile", type="string", required="true", description="手机号码")
     * @ApiParams   (name="event", type="string", required="true", sample="reg_login",description="事件名称，reg_login=使用手机号+验证码的方式进行注册或登录")
     * @ApiReturnParams   (name="code", type="integer", required="true", sample="0")
     * @ApiReturnParams   (name="msg", type="string", required="true", sample="返回成功")
     * @ApiReturnParams   (name="time", type="integer", description="请求时间，Unix时间戳，单位秒")
     * @ApiReturnParams   (name="data", type="null", description="只会返回null")
     * @ApiReturn
({
    "err_code": 1,
    "msg": "发送失败,触发分钟级流控Permits:1",
    "time": 1584667483,
    "data": null
})
     */
    public function send_mobile_code(){
        $addons_service = new Addons();
        $addon = $addons_service->_info->getAddonInfo('aliyun_mobilemsg');
        if(!$addon){
            $this->error('请先安装阿里云手机短信插件');
        }
        if(!$addon['state']){
            $this->error('阿里云手机短信插件已关闭');
        }

        $params['mobile'] = $this->request->param('mobile');
        $params['event'] = $this->request->param('event');

        $validate = new \addons\aliyun_mobilemsg\validate\Send();
        if($validate->check($params)){
            $mobile_service = new Mobile();
            if($mobile_service->send($params['mobile'],$params['event'],['code'=>Random::numeric()])){
                $this->success('发送成功');
            }else{
                $this->error('发送失败,'.$mobile_service->getError());
            }
        }else{
            $this->error('发送失败,'.$validate->getError());
        }
    }
    /**
     * 短信发送
     * @param $to    接收人
     * @param $code   短信验证码
     * @return json
     */
    public function sendSms($to, $code){
        require_once '../extend/aliSms/vendor/autoload.php';
        AliConfig::load();
        $accessKeyId = Config::get('laytp.aliyun_sms.access_id');
        $accessKeySecret = Config::get('laytp.aliyun_sms.access_secret');
        $templateParam['code'] = $code;
        $templateCode = Config::get('laytp.aliyun_sms.template_code');
        //短信签名
        $signName = Config::get('laytp.aliyun_sms.sign');

        //短信API产品名（短信产品名固定，无需修改）
        $product = "Dysmsapi";
        //短信API产品域名（接口地址固定，无需修改）
        $domain = "dysmsapi.aliyuncs.com";
        //暂时不支持多Region（目前仅支持cn-hangzhou请勿修改）
        $region = "cn-hangzhou";
        // 初始化用户Profile实例
        $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);
        // 增加服务结点
        DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", $product, $domain);
        // 初始化AcsClient用于发起请求
        $acsClient= new DefaultAcsClient($profile);
        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendSmsRequest();
        // 必填，设置雉短信接收号码
        $request->setPhoneNumbers($to);
        // 必填，设置签名名称
        $request->setSignName($signName);
        // 必填，设置模板CODE
        $request->setTemplateCode($templateCode);
        // 可选，设置模板参数
        if($templateParam) {
            $request->setTemplateParam(json_encode($templateParam));
        }
        //发起访问请求
        $acsResponse = $acsClient->getAcsResponse($request);
        //返回请求结果
        $result = json_decode(json_encode($acsResponse),true);
        if($result['Code'] == 'OK'){
            model('SmsCode')->isUpdate(false)->save(['mobile'=>$to,'code'=>$code,'exceed_time'=>time()+300]);
        }
        // 具体返回值参考文档：https://help.aliyun.com/document_detail/55451.html?spm=a2c4g.11186623.6.563.YSe8FK
        return $result;
    }


}