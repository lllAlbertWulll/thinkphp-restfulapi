<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// api.restfuldemo.php ===> www.restfuldemo.com/index.php/api
Route::domain('api', 'api');

/*********** Code ***********/
// 获取验证码
Route::get('code/:time/:token/:username/:is_exist', 'code/get_code');

/*********** User  ***********/
Route::get('user', 'user/index');
// 用户登录 post api.tp5.com/user/login  --->  user.php login()
Route::post('user/login', 'user/login');
// 用户注册
Route::post('user/register', 'user/register');
// 用户上传你头像
Route::post('user/icon', 'user/upload_head_img');
// 用户修改密码
Route::post('user/change_pwd', 'user/change_pwd');
// 用户找回密码
Route::post('user/find_pwd', 'user/find_pwd');
// 用户绑定手机号
//Route::post('user/bind_phone', 'user/bind_phone');
// 用户绑定邮箱
//Route::post('user/bind_email', 'user/bind_email');
// 用户绑定邮箱/手机(二合一)
Route::post('user/bind_username', 'user/bind_username');
// 用户修改昵称
Route::post('user/nickname', 'user/set_nickname');

/*********** Article  ***********/
// 新增文章
Route::post('article', 'article/add_article');
// 查看文章列表
Route::get('articles/:time/:token/:user_id/[:num]/[:page]', 'article/article_list');
// 获取单个文章信息
Route::get('article/:time/:token/:article_id', 'article/article_detail');
// 修改/更新文章
Route::put('article', 'article/update_article');
// 删除文章
Route::delete('article/:time/:token/:article_id', 'article/del_article');
