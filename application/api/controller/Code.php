<?php
namespace app\api\controller;

use PHPMailer;

class Code extends Common
{
    public function get_code()
    {
        //echo 'get_code';die;
        $username = input('username');
        //echo $username;die;
        $exist = input('is_exist');
        //echo $exist;die;
        $username_type = $this->check_username($username);

        switch ($username_type) {
            case 'phone':
                $this->get_code_by_username($username, 'phone', $exist);
                break;

            case 'email':
                $this->get_code_by_username($username, 'email', $exist);
                break;
        }
    }

    /**
     * 通过手机或邮箱获取验证码
     * @param  [string] $username [手机号/邮箱]
     * @param  [string] $type     [用户名类别]
     * @param  [int] $exist    [用户名是否存在于数据库中 1：是 0：否]
     * @return [json]           [api返回的json数据]
     */
    public function get_code_by_username($username, $type, $exist)
    {
        if ($type == 'phone') {
            $type_name = '手机';
        } else {
            $type_name = '邮箱';
        }
        /***** 检测手机号/邮箱是否存在 *****/
        $this->check_exist($username, $type, $exist);
        /***** 检查验证码请求频率 30秒一次 *****/
        if (session('?' . $username . '_last_send_time')) {
            if (time() - session($username . '_last_send_time') < 30) {
                $this->return_msg(400, $type_name . '验证码，每30秒只能发送一次！');
            }
        }
        /***** 生成验证码 *****/
        $code = $this->make_code(6);
        /***** 使用session存储验证码，方便比对，md5加密 *****/
        $md5_code = md5($username . '-' . md5($code));
        session($username . '_code', $md5_code);
        /***** 使用session存储验证码的发送时间 *****/
        session($username . '_last_send_time', time());
        /***** 发送验证码 *****/
        if ($type == 'phone') {
            $this->send_code_to_phone($username, $code);
        } else {
            $this->send_code_to_email($username, $code);
        }
    }

    /**
     * 生成验证码
     * @param  [int] $num [验证码的位数]
     * @return [int]      [生成的验证码]
     */
    public function make_code($num)
    {
        $max = pow(10, $num) - 1;
        $min = pow(10, $num - 1);
        return rand($min, $max);
    }

    /**
     * 调用API向手机号发送验证码
     * @param  [string] $phone [手机号]
     * @param  [int] $code  [验证码]
     * @return [josn]        [返回的json数据]
     */
    public function send_code_to_phone($phone, $code)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.mysubmail.com/message/xsend');
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);

        $data = [
            'appid' => '18409', // APPID
            'to' => $phone, // 发给谁
            'project' => 'v4L3K', // 模板号
            'vars' => '{"code":' . $code . ',"time":"60"}', // 模板变量json格式
            'signature' => '8855f82e9e39418ce5421ee50dd1421e', //APPKEY
        ];

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $res = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($res);
        var_dump($res);

        if ($res->status != 'success') {
            $this->return_msg(400, $res->msg);
        } else {
            $this->return_msg(200, '手机验证码已发送，请在一分钟内验证');
        }
        //dump($res);die;
    }

    /**
     * 向邮箱发送验证码
     * @param  [string] $email [目标email]
     * @param  [int] $code  [验证码]
     * @return [json]        [返回的json数据]
     */
    public function send_code_to_email($email, $code)
    {
        $toemail = $email;
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->CharSet = 'utf8';
        $mail->Host = 'stmp.126.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'gegewv@126.com';
        $mail->Password = 'Wgj0220'; // 授权码
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 994;
        $mail->setFrom('gegewv@126.com', '接口测试');
        $mail->addAddress($email, 'test');
        $mail->addReplyTo('gegewv@126.com', 'Reply');
        $mail->Subject = "您有新的验证码！";
        $mail->Body = "这是一个测试邮件，您的验证码是$code 。验证码的有效期为一分钟，本邮件请勿回复！";

        if (!$mail->send()) {
            $this->return_msg(400, $mail->ErrorInfo);
        } else {
            $this->return_msg(200, '验证码已经发送成功，请注意查收！');
        }
    }
}
