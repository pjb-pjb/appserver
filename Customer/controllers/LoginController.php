<?php
/**
 * FecShop file.
 *
 * @link http://www.fecshop.com/
 * @copyright Copyright (c) 2016 FecShop Software LLC
 * @license http://www.fecshop.com/license/
 */

namespace fecshop\app\appserver\modules\Customer\controllers;

use fecshop\app\appserver\modules\AppserverController;
use Yii;
use \Firebase\JWT\JWT;

/**
 * @author Terry Zhao <2358269014@qq.com>
 * @since 1.0
 */
class LoginController extends AppserverController
{
    public $enableCsrfValidation = false ;
    /**
     * 登录用户的部分
     */
    public function actionAccount(){
        if(Yii::$app->request->getMethod() === 'OPTIONS'){
            return [];
        }
        $identity = Yii::$service->customer->loginByAccessToken(get_class($this));
        if($identity){
            // 用户已经登录
            
            $code = Yii::$service->helper->appserver->account_is_logined;
            $data = [];
            $reponseData = Yii::$service->helper->appserver->getReponseData($code, $data);
            
            return $reponseData;
        }
        $email       = Yii::$app->request->post('email');
        $password    = Yii::$app->request->post('password');
        $loginParam  = \Yii::$app->getModule('customer')->params['login'];
        $loginCaptchaActive = isset($loginParam['loginPageCaptcha']) ? $loginParam['loginPageCaptcha'] : false;
        if($loginCaptchaActive){
            $captcha    = Yii::$app->request->post('captcha');
            if(!Yii::$service->helper->captcha->validateCaptcha($captcha)){
                $code = Yii::$service->helper->appserver->status_invalid_captcha;
                $data = [];
                $reponseData = Yii::$service->helper->appserver->getReponseData($code, $data);
                
                return $reponseData;
            }
        }
        $accessToken = Yii::$service->customer->loginAndGetAccessToken($email,$password);
        if($accessToken){
            $code = Yii::$service->helper->appserver->status_success;
            $data = [];
            $reponseData = Yii::$service->helper->appserver->getReponseData($code, $data);
            
            return $reponseData;
        }else{
            
            $code = Yii::$service->helper->appserver->account_login_invalid_email_or_password;
            $data = [];
            $reponseData = Yii::$service->helper->appserver->getReponseData($code, $data);
            
            return $reponseData;
        }
        
    }
    
    /**
     * 登录页面
     *
     */
    public function actionIndex(){
        if(Yii::$app->request->getMethod() === 'OPTIONS'){
            return [];
        }
        $identity = Yii::$service->customer->loginByAccessToken(get_class($this));
        if($identity){
            // 用户已经登录
            $code = Yii::$service->helper->appserver->account_is_logined;
            $data = [ ];
            $reponseData = Yii::$service->helper->appserver->getReponseData($code, $data);
            
            return $reponseData;
        }
        $loginParam = \Yii::$app->getModule('customer')->params['login'];
        $loginCaptchaActive = isset($loginParam['loginPageCaptcha']) ? $loginParam['loginPageCaptcha'] : false;
        $googleRedirectUrl   = Yii::$app->request->get('googleRedirectUrl');
        $facebookRedirectUrl = Yii::$app->request->get('facebookRedirectUrl');
        
        $code = Yii::$service->helper->appserver->status_success;
        $data = [ 
            'loginCaptchaActive'=> $loginCaptchaActive,
            'googleLoginUrl'    => Yii::$service->customer->google->getLoginUrl($googleRedirectUrl,true),
            'facebookLoginUrl'  => Yii::$service->customer->facebook->getLoginUrl($facebookRedirectUrl,true),
        ];
        $reponseData = Yii::$service->helper->appserver->getReponseData($code, $data);
        
        return $reponseData;
        
    }

    // 完成用户的登录操作

    public function actionChecklogin(){

        if(Yii::$app->request->getMethod() === 'OPTIONS'){
            return [];
        }

        // 接收所有用户提交的数据
        $tel = Yii::$app->request->post('tel');
        $pass = Yii::$app->request->post('password');


        // 查询用户表对应数据

        $sql = "select customer.* from customer where customer.tel='$tel' and customer.customer_type=0";
        $res = Yii::$app->db->createCommand($sql)->queryOne();

        // 判断res是否存在
        if ($res) {
            // 判断密码是否正确
            if(password_verify($pass,$res["password_hash"])){
                // 保存用户基本信息
                $_SESSION["login"] = "yes";
                $_SESSION["uid"] = $res["id"];
                $_SESSION["admin_name"] = $firstname;
                $_SESSION["time"] = time();     

                $data['info']="登录成功";
                $data['status']="200";
                $data['id']=$res["id"];
                $data['firstname']=$res['firstname'];

            }else{
                $data['info']="密码错误";
                $data['status']="0";
            }
        }else{
            $data['info']="用户不存在";
            $data['status']="0";
        }
        
        // 返回数据

        return $data;
    }

    // 验证手机是否注册

    public function actionCheckphone(){
        //获取数据
        $req = Yii::$app->request;
        // 接收手机号
        $tel = $req->post(telphone);

        //判断用户名是否存在
        $arr = Yii::$app->db->createCommand("select count(*) as num from customer where tel='$tel'")->queryOne();
        // 判断是否输入手机号和密码
        if ($arr['num']!=0 && $tel) {
            $msgSuc['status']=0;
            $msgSuc['info']='该手机号已注册';
        }else{
            $msgSuc['status']=1;
            $msgSuc['info']='可以使用';
        }


        return $msgSuc;

    }

    // 注册接口
    // 注册功能

    public function actionRegedit(){

        //获取数据
        $req = Yii::$app->request;
        if(!empty($req->post())){
            $password_hash = password_hash($req->post(password),PASSWORD_DEFAULT);
            $tel = $req->post(telphone);
            $code = $req->post(code);
            $password = $req->post(password);
            $repassword = $req->post(repassword);

            // 检测短信验证码

                // if(($code!=$_SESSION['dxyzm']) || ($firstname!=$_SESSION['tel'])){
                //     $msgSuc['err']=0;
                //     $msgSuc['info']='验证码不正确';
                //     echo json_encode($msgSuc);
                //     return false;   
                // }

            // 判断密码是否一致
            if ($password==$repassword && $repassword) {
                //判断用户名是否存在
                $arr = Yii::$app->db->createCommand("select count(*) as num from customer where tel='$tel'")->queryOne();
                // 判断是否输入手机号和密码
                if($password==""||$tel==""){

                    $msgSuc['status']=0;
                    $msgSuc['info']='手机号或者密码不能为空';

                // 判断是否已经注册
                } else if($arr["num"] != 0){

                    $msgSuc['status']=0;
                    $msgSuc['info']='该手机号已注册';

                }else{
                    
                    $time = time();
                    $res = Yii::$app->db->createCommand("insert into customer (password_hash,firstname,created_at,tel,customer_type) values ('$password_hash','$tel','$time','$tel',0)")->execute();
                    $uid = Yii::$app->db->getLastInsertID();
                    $sql = "insert into userinfo (userNum,userPhone,userName,relevancy,userId) values ('$tel','$tel','$tel',$uid,$uid)";
                    Yii::$app->db->createCommand($sql)->execute();
                    if($res == 1){
                        // 注册成功
                        $msgSuc['status']=1;
                        $msgSuc['info']='注册成功';
                    }else{
                        // 注册失败
                        $msgSuc['status']=0;
                        $msgSuc['info']='注册失败';
                    }
                }
            }else{
                $msgSuc['status']=0;
                $msgSuc['info']='两次输入密码不一致';
            }
        }else{
            
            $msgSuc['status']=0;
            $msgSuc['info']='请求有误';
        }

        return $msgSuc;
    }
    
}