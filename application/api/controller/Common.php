<?php
namespace app\api\controller;

use think\Controller;
use think\facade\Validate;
use think\Request;

//use think\Validate;

/**
 * 统一处理参数过滤
 */
class Common extends Controller
{
    protected $request; // 用来处理参数
    protected $validater; // 用来验证数据/参数
    protected $params; // 过滤后符合要求的参数
    protected $rules = array(
        'User' => array(
            'login' => array(
                'user_name' => 'require',
                'user_pwd' => 'require|length:32',
            ),
            'register' => array(
                'user_name' => 'require',
                'user_pwd' => 'require|length:32',
                'code' => 'require|number|length:6',
            ),
            'upload_head_img' => array(
                'user_id' => 'require|number',
                'user_icon' => 'require|image|fileSize:2000000000|fileExt:jpg,png,bmp,jpeg',
            ),
            'change_pwd' => array(
                'user_name' => 'require',
                'user_ini_pwd' => 'require|length:32',
                'user_pwd' => 'require|length:32',
            ),
            'find_pwd' => array(
                'user_name' => 'require',
                'user_pwd' => 'require|length:32',
                'code' => 'require|number|length:6',
            ),
            'bind_phone' => array(
                'user_id' => 'require|number',
                'phone' => ['require', 'regex' => '/^1[34578]\d{9}$/'],
                'code' => 'require|number|length:6',
            ),
            'bind_email' => array(
                'user_id' => 'require|number',
                'email' => 'require|email',
                'code' => 'require|number|length:6',
            ),
            'bind_username' => array(
                'user_id' => 'require|number',
                'user_name' => 'require',
                'code' => 'require|number|length:6',
            ),
            'set_nickname' => array(
                'user_id' => 'require|number',
                'user_nickname' => 'require|chsDash',
            ),
        ),
        'Article' => array(
            'add_article' => array(
                'article_uid' => 'require|number',
                'article_title' => 'require|chsDash',
            ),
            'article_list' => array(
                'user_id' => 'require|number',
                'num' => 'number',
                'page' => 'number',
            ),
            'article_detail' => array(
                'article_id' => 'require|number',
            ),
            'update_article' => array(
                'article_id' => 'require|number',
                'article_title' => 'chsDash',
            ),
            'del_article' => array(
                'article_id' => 'require|number',
            ),
        ),
        'Code' => array(
            'get_code' => array(
                'username' => 'require',
                'is_exist' => 'require|number|length:1',
            ),
        ),
    );

    protected function _initialize()
    {
        parent::_initialize();
        $this->request = new Request();
        //$this->check_time($this->request->only(['time']));
        //$this->check_token($this->request->param());
        $this->params = $this->check_params($this->request->except(['time', 'token']));
        $this->params = $this->check_params($this->request->param(true));
    }

    /**
     * 验证请求是否超时
     * @param  [array] $arr [包含时间戳的参数数组]
     * @return [json]      [检测结果]
     */
    public function check_time($arr)
    {
        if (!isset($arr['time']) || intval($arr['time']) <= 1) {
            $this->return_msg(400, '时间戳不存在');
        }
        if (time() - intval($arr['time']) > 60) {
            $this->return_msg(400, '请求超时！');
        }
    }

    /**
     * api 数据返回
     * @param  [int] $code [结果码 200：正常; 4**：数据问题; 5**：服务器问题]
     * @param  [string] $msg  [接口要返回的提示信息]
     * @param  [array]  $data [接口要返回的数据]
     * @return [string]       [最终的json数据]
     */
    public function return_msg($code, $msg = '', $data = [])
    {
        /***** 组合数据 *****/
        $return_data['code'] = $code;
        $return_data['msg'] = $msg;
        $return_data['data'] = $data;
        /***** 返回信息并终止脚本 *****/
        echo json_encode($return_data);
        die;
    }

    /**
     * 验证token(防止篡改数据)
     * @param  [array] $arr [全部请求数据]
     * @return [json]      [token验证结果]
     */
    public function check_token($arr)
    {
        /***** api传过来的token *****/
        if (!isset($arr['token']) || empty($arr['token'])) {
            $this->return_msg(400, 'token不能为空！');
        }
        $app_token = $arr['token']; // api传过来的token

        /***** 服务器生成token *****/
        unset($arr['token']);
        $service_token = '';
        foreach ($$arr as $key => $value) {
            $service_token .= md5($value);
        }
        $service_token = md5('api_' . $service_token . '_api'); // 服务器即时生成的token

        /***** 对比token，返回结果 *****/
        if ($app_token !== $service_token) {
            $this->return_msg(400, 'token值不正确！');
        }
    }

    /**
     * 验证参数 参数过滤
     * @param  [array] $arr [除time和token外的所有参数]
     * @return [return]      [合格的参数数组]
     */
    public function check_params($arr)
    {
        /***** 获取参数的验证规则 *****/
        $rules = $this->rules[$this->request->controller()][$this->request->action()];

        /***** 验证参数并返回错误 *****/
        $this->validater = new Validate($rules);
        if (!$this->validater->check($arr)) {
            $this->return_msg(400, $this->validater->getError());
        }

        /***** 如果正常，通过验证 *****/
        return $arr;
    }

    /**
     * 检测用户名并返回用户名类别
     * @param  [string] $username [用户名，可能是邮箱，也可能是手机号]
     * @return [string]           [检测结果]
     */
    public function check_username($username)
    {
        /***** 判断是否为邮箱 *****/
        $is_email = Validate::isEmail($username) ? 1 : 0;
        //echo $is_email;die;
        /***** 判断是否为手机号 *****/
        //$is_phone = preg_match('/^1[34578]\d{9}$/', $username) ? 4 : 2;
        $is_phone = Validate::regex($username, '/^1[34578]\d{9}$/') ? 4 : 2;
        //echo $is_phone;die;
        /***** 最终结果 *****/
        $flag = $is_email + $is_phone;

        switch ($flag) {
            case 2:
                /***** not phone not email *****/
                $this->return_msg(400, '邮箱或手机号不正确!');
                break;
            case 3:
                /***** is email not phone *****/
                return 'email';
                break;
            case 4:
                /***** is phone not email *****/
                return 'phone';
                break;
        }
    }

    /**
     * 判断手机号/邮箱是否存在
     * @param  [string] $value [手机/邮箱]
     * @param  [string] $type  [用户名类别]
     * @param  [int] $exist [用户名是否存在于数据库中 1：是 0：否]
     * @return [json]        [返回json数据]
     */
    public function check_exist($value, $type, $exist)
    {
        $type_num = $type == 'phone' ? 2 : 4;
        $flag = $type_num + $exist;
        $phone_res = db('user')->where('user_phone', $value)->find();
        $email_res = db('user')->where('user_email', $value)->find();

        switch ($flag) {
            /***** 2 + 0 phone need no exist *****/
            case 2:
                if ($phone_res) {
                    $this->return_msg(400, '此手机号已被占用！');
                }
                break;

            /***** 2 + 1 phone need exist *****/
            case 3:
                if (!$phone_res) {
                    $this->return_msg(400, '此手机号不存在！');
                }
                break;
            /***** 4 + 0 email need no exist *****/
            case 4:
                if ($email_res) {
                    $this->return_msg(400, '此邮箱已被占用！');
                }
                break;
            /***** 4 + 1 email need no exist *****/
            case 5:
                if (!$email_res) {
                    $this->return_msg(400, '此邮箱不存在！');
                }
                break;
        }
    }

    public function check_code($user_name, $code)
    {
        /***** 检测是否超时 *****/
        $last_time = session($user_name . '_last_send_time');
        dump($last_time);
        if (time() - $last_time > 60) {
            $this->return_msg(400, '验证超时，请在一分钟内验证！');
        }
        /***** 检测验证码是否正确 *****/
        $md5_code = md5($user_name . '_' . md5($code));
        if (session($user_name . '_code') !== $md5_code) {
            $this->return_msg(400, '验证码不正确！');
        }
        /***** 不管正确与否， 每个验证码只能验证一次 *****/
        session($user_name . '_code', null);
    }

    /**
     * [upload_file description]
     * @param  [type] $file [description]
     * @param  string $type [description]
     * @return [type]       [description]
     */
    public function upload_file($file, $type = '')
    {
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
            $path = '/uploads/' . $info->getSaveName();
            /*********** 裁剪图片  ***********/
            if (!empty($type)) {
                $this->image_edit($path, $type);
            }
            return str_replace('\\', '/', $path);
        } else {
            $this->return_msg(400, $file->getError());
        }
    }

    public function image_edit($path, $type)
    {
        $image = Image::open(ROOT_PATH . 'public' . $path);

        switch ($type) {
            case 'head_img':
                $image->thumb(200, 200, Image::THUMB_CENTER)->save(ROOT_PATH . 'public' . $path);
                break;
        }
    }
}
