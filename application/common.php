<?php

use think\facade\Config;
use think\facade\Url;


/**
 * 生成插件url地址
 * @param $addon 插件名
 * @param string $url
 * @param string $vars
 * @param bool $suffix
 * @param bool $domain
 * @return string
 */
function addon_url($addon, $url = '', $vars = '', $suffix = true, $domain = false)
{
    $addon_url = "/addons/$addon/$url";
    return Url::build($addon_url, $vars, $suffix, $domain);
}

function getSelectMenuIds($menu_tree_obj, $id, $init=false){
    static $select_menu_ids;
    if($init){
        $select_menu_ids = [];
    }
    $tree = $menu_tree_obj->getTreeArray($id);
    if($tree){
        $select_menu_ids[] = $tree[0]['id'];
        if(count($tree[0]['children'])){
            getSelectMenuIds($menu_tree_obj,$tree[0]['id']);
        }
        return $select_menu_ids;
    }else{
        return [];
    }
}

/**
 * 分页数据格式化成layui_table能用的数据
 * @param $data
 * @return \think\response\Json
 */
function layui_table_page_data($data){
    if( array_key_exists('total', $data) ){
        $json['code'] = ($data['total'] > 0) ? 0 : 1;
    }else{
        $data['total'] = 0;
        $json['code'] = 1;
    }
    $json['msg'] = '暂无数据';
    $json['count'] = $data['total'];

    if( array_key_exists('data', $data) ){
        $json['data'] = $data['data'];
    }else{
        $json['data'] = [];
    }
    return json($json);
}

/**
 * 统一处理post数据
 * @param $post
 * @return mixed
 */
function filterPostData($post){
    if(!$post){
        return [];
    }
    //处理数组
    foreach($post as $k=>$v){
        if(is_array($v)){
            $post[$k] = implode(',',$v);
        }
    }
    return $post;
}

//获取插件js目录
function getAddonJSDir($addon,$module){
    return "/addons/$addon/$module/javascript";
}

//获取插件css目录
function getAddonCSSDir($addon,$module){
    return "/addons/$addon/static/$module/css";
}

//获取插件image目录
function getAddonImageDir($addon,$module){
    return "/addons/$addon/static/$module/image";
}

/**
 * 二维数组，外层索引替换成item内某个索引对应的值
 * @param $array
 * @param $field
 * @return array
 * @example
 *  $array = [
 *      0   =>  ['field'=>'id','comment'=>'ID'],
 *      1   =>  ['field'=>'name','comment'=>'标题']
 *  ];
 *  $result = arrToMap($array, 'field');
 *  $result结果：
 *  $result = [
 *      'id'    =>  ['field'=>'id','comment'=>'ID'],
 *      'name'  =>  ['field'=>'name','comment'=>'标题']
 *  ];
 */
function arr_to_map($array, $field){
    $result = [];
    foreach($array as $k=>$v){
        if(isset($v[$field])){
            $result[$v[$field]] = $v;
        }
    }
    return $result;
}

/**
 * 获取二维数组中某个key组成的数组
 * @param $array
 * @param $field
 * @return array
 */
function get_arr_by_key($array, $field){
    $result = [];
    foreach($array as $k=>$v){
        if(isset($v[$field])){
            $result[] = $v[$field];
        }
    }
    return $result;
}

/**
 * 执行命令行，使用此函数可以调用命令行程序，不需要application/command.php文件注册命令行
 * @param string $command_class_name 命令行完整类名
 * @param array $argv 命令行参数
 * @return string 返回的信息
 * @example
 * exec_command('app\admin\command\Curd',['--id=1','--name=test']);
 * 类似于执行:
 * php think curd --id=1 --name=test
 */
function exec_command($command_class_name, $argv=[]){
    $input = new \think\console\Input($argv);
    $command = app($command_class_name);
    $output = app('library\Output');
    try {
        $command->run($input, $output);
        $result['msg'] = implode("\n", $output->getMessage());
        $result['code'] = 1;
    } catch (Exception $e) {
        $result['msg'] = $e->getFile() . '<br />' . $e->getLine() . '<br />' . $e->getMessage() . '<br />' . $e->getCode();
        $result['code'] = 0;
    }
    return $result;
}

/**
 * 检测数组中的值是否都为true，启用数据库事务时能用到
 *      注意，更新操作返回影响数据库行数，当没有对数据进行更改时，正常操作数据库会返回0，表示影响了0行，
 *      由于0在PHP中表示false，但是在实际业务中，有可能影响0行也允许提交事务，
 *      此时需要根据业务需求，在controller层调用checkRes函数时，对入参$result进行判断，返回为0时设置为false还是true，
 *      以便用于判断最终执行回滚还是提交事务的操作
 * @param $result
 * @return bool
 */
function check_res($result){
    foreach($result as $v){
        if(!$v){
            return false;
        }
    }
    return true;
}

/**
 * @param $length
 * @return string
 * 随机字符串
 */
function GetRandStr($length){
    //字符组合
    $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $len = strlen($str)-1;
    $randstr = '';
    for ($i=0;$i<$length;$i++) {
        $num=mt_rand(0,$len);
        $randstr .= $str[$num];
    }
    return $randstr;
}

/**
 * @param $mobile
 * @return array
 * 根据手机号获取归属地
 */
function get_mobile_area($mobile){
    //根据淘宝的数据库调用返回值
    $url = "http://tcc.taobao.com/cc/json/mobile_tel_segment.htm?tel=".$mobile;
    $content = file_get_contents($url);
    $list= mb_convert_encoding($content, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');
    $res = trim(explode('=',$list)[1]);
    preg_match_all("/(\w+):'([^']+)/", $res, $m);

    $res = array_combine($m[1], $m[2]);
    if ($res && isset($res['province']) && $res['province'] != "") {
        return $res['province'];
    }else{
        return '';
    }

}


/**
 * 根据经纬度 获取地址
 * @param $address23.2322,12.15544
 * @return mixed
 */
function getAddress($address){

    $url="http://restapi.amap.com/v3/geocode/regeo?output=json&location=".$address."&key=".Config::get('laytp.gaode_get_address.key');
    if($result=file_get_contents($url))
    {
        $result = json_decode($result,true);
        if(!empty($result['status'])&&$result['status']==1){

             $str = conv2utf8($result['regeocode']['formatted_address']);
            return $str;

        }else{
            return false;
        }

    }

}
// 转换字符串编码为 UTF8
function conv2utf8($text){
    return mb_convert_encoding($text,'UTF-8',mb_internal_encoding());

}
function curl_get($url){

    $header = array(
        'Accept: application/json',
    );
    $curl = curl_init();
    //设置抓取的url
    curl_setopt($curl, CURLOPT_URL, $url);
    //设置头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_HEADER, 0);
    // 超时设置,以秒为单位
    curl_setopt($curl, CURLOPT_TIMEOUT, 1);

    // 超时设置，以毫秒为单位
    // curl_setopt($curl, CURLOPT_TIMEOUT_MS, 500);

    // 设置请求头
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    //执行命令
    $data = curl_exec($curl);

    // 显示错误信息
    if (curl_error($curl)) {
        print "Error: " . curl_error($curl);
    } else {
        // 打印返回的内容
        var_dump($data);
        curl_close($curl);
    }


}

function HttpRequest($url, $params, $method = 'GET', $header = array(), $bEncode = true)
{
    $opts = array(
        CURLOPT_TIMEOUT => 30,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => $header
    );
    /* 根据请求类型设置特定参数 */
    switch (strtoupper($method)) {
        case 'GET':
            $opts[CURLOPT_URL] = $url;
            break;
        case 'POST':
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = json_encode($params, JSON_UNESCAPED_UNICODE);
            break;
        default:
            throw new Exception('不支持的请求方式！');
    }
    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    echo $error;
    if ($error) throw new Exception('请求发生错误：' . $error);
    return $data;
}

