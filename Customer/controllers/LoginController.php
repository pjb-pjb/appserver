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

    // 完成用户的登录操作=========================================================================================================

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
	
	// 验证手机号格式
	
	public function actionChecktel(){
		// 验证手机号
		//获取数据
		$req = Yii::$app->request;
		
		// 判断用户请求
		if(!empty($req->get())){
			$tel = $req->get('tel');
			// 检测输入的手机号是否合法
			
			if(preg_match('/^1([358][0-9]|4[579]|66|7[0135678]|9[89])[0-9]{8}$/',$tel)){
				// 判断手机号是否注册
				$sql = "select customer.* from customer where customer.tel='$tel'";
				$res = Yii::$app->db->createCommand($sql)->queryOne();
				
				if($res){
					
					
					$data = [
						"code" => 200,
						"info" => "手机号无误",
					];
				}else{
					$data = [
						"code" => 402,
						"info" => "未注册",
					];
				}
			}else{
				$data = [
					"code" => 401,
					"info" => "手机号格式有误",
				];
			}
		
		}else{
			$data = [
				"code" => 400,
				"info" => "非法数据请求",
			];
		}
		
		return $data;
	}
	
	// 发送验证码操作
	
	public function actionFasong(){
		//获取数据
		$req = Yii::$app->request;
		
		// 判断用户请求
		if(!empty($req->get())){
			
			$tel = $req->get('tel');
			// 检测输入的手机号是否合法
			
			if(preg_match('/^1([358][0-9]|4[579]|66|7[0135678]|9[89])[0-9]{8}$/',$tel)){
				// 判断
				
			
					// 发送验证吗
					
					//初始化必填
					$options['accountsid']='442c51c4d2a95aededa6f12a23b26fe4'; //填写自己的
					$options['token']='887b72f88fc33eacb1b3ad92daa2fd4f'; //填写自己的
					//初始化 $options必填
					$ucpass =new \Ucpaas($options);
				   
					//随机生成6位验证码
					srand((double)microtime()*1000000);//create a random number feed.
					// $ychar="0,1,2,3,4,5,6,7,8,9,A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z";
					$ychar="0,1,2,3,4,5,6,7,8,9";
					$list=explode(",",$ychar);
					for($i=0;$i<6;$i++){
						$randnum=rand(0,9); // 10+26;
						$authnum.=$list[$randnum];
					}
					//短信验证码（模板短信）,默认以65个汉字（同65个英文）为一条（可容纳字数受您应用名称占用字符影响），超过长度短信平台将会自动分割为多条发送。分割后的多条短信将按照具体占用条数计费。
					$appId = "e771411626af47b381a704c419e23b16";  //填写自己的
					$to = $tel;
					$templateId = "191761";
					$param=$authnum;
			
					$arr=$ucpass->templateSMS($appId,$to,$templateId,$param);
					if (substr($arr,21,6) == 000000) {
						$data=[
							"code" => 200,
							"info" => "短信验证码已发送成功，请注意查收短信",
							"param" => $param,
						];
					}else{
						$data=[
							"code" => 400,
							"info" => "短信验证码发送失败，请联系客服",
						];
					}
					
			}else{
				$data = [
					"code" => 401,
					"info" => "手机号格式有误",
				];
			}
			
		}else{
			
			$data = [
				"code" => 400,
				"info" => "非法数据请求",
			];
		}
		
		return $data;
	}
	
	// 通过手机号获取用户信息
	public function actionGetuserinfo(){
		//获取数据
		$req = Yii::$app->request;
		
		$tel = $req->get('tel');

		// 判断用户请求
		if(!empty($req->get())){
			// 判断手机号是否注册
				$sql = "select firstname,headImg,id from customer where customer.tel='$tel'";
				$res = Yii::$app->db->createCommand($sql)->queryOne();
			
			// 判断数据
				
				if($res){
					$data=[
						"code" => 200,
						"info" => "获取数据成功",
						"data" => $res,
					];
				}else{
					$data=[
						"code" => 400,
						"info" => "请求失败",
					];
				}
		}else{
			$data = [
				"code" => 400,
				"info" => "非法数据请求",
			];
		}
		
		return $data;
	}
 

    // 注册接口===============================================================================================================
	
	
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
    // 注册功能

    public function actionRegedit(){

        //获取数据
        $req = Yii::$app->request;
        if(!empty($req->post())){
            $password_hash = password_hash($req->post(password),PASSWORD_DEFAULT);
            $tel = $req->post(tel);
            $code = $req->post(code);
            $password = $req->post(password);
            $repassword = $req->post(repassword);

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