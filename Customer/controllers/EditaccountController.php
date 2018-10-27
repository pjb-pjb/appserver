<?php
/**
 * FecShop file.
 *
 * @link http://www.fecshop.com/
 * @copyright Copyright (c) 2016 FecShop Software LLC
 * @license http://www.fecshop.com/license/
 */

namespace fecshop\app\appserver\modules\Customer\controllers;

use fecshop\app\appserver\modules\AppserverTokenController;
use fecshop\app\appserver\modules\AppserverController;
use Yii;
use \Firebase\JWT\JWT;

/**
 * @author Terry Zhao <2358269014@qq.com>
 * @since 1.0
 */
class EditaccountController extends AppserverController
{
    public $enableCsrfValidation = false ;
    /**
     * 登录用户的部分
     */
    public function actionIndex(){
        if(Yii::$app->request->getMethod() === 'OPTIONS'){
            return [];
        }
		// 判断是否请求UID 
		if($_GET['uid']){
			// 获取用户的基本信息 
			$res['data'] = Yii::$app->db->createCommand("select c.firstname,c.money,c.coin,c.headImg,c.level from customer c where id = $_GET[uid]")->queryOne();
			// 获取优惠券的数量 
			$res['coupon'] = Yii::$app->db->createCommand("select count(*) tot from sales_coupon_usage coupon_use , sales_coupon coupon  where coupon.coupon_id = coupon_use.coupon_id and  coupon_use.customer_id = $_GET[uid]")->queryOne();

			
		}else{
			$res=[
					"code"=>400,
			];
		}
		
		return $res;	
    }
		
	// 查看优惠券的了列表
	 
	public function actionCouponlist(){
		if(Yii::$app->request->getMethod() === 'OPTIONS'){
			return [];
		}
		
		// 判断是否请求UID
		
		if($_GET['uid']){
			// 获取优惠券 

			// 分页数据
			$page = $_GET['page']?$_GET['page']:0;
			$start = ($page)*10;
			// 请求类型
			$status = $_GET['type']?$_GET['type']:0;
			
			// 查询数据
			$res= Yii::$app->db->createCommand("select coupon.*,coupon_use.*,shop.shop_name,shop.shop_logo  from sales_coupon_usage coupon_use , sales_coupon coupon ,shop  where coupon.coupon_id = coupon_use.coupon_id and  coupon_use.customer_id = $_GET[uid] and coupon_use.status = $status and coupon.shop_id = shop.shop_id order by coupon_use.id desc limit $start,10")->queryAll();
			
			// 格式化数据 
			foreach($res as $key=> &$value){
				$value['expiration_date']= date("Y-m-d H:i:s",$value['expiration_date']);
			}
			
			$data=[
				"code"=> 200,
				"info"=>"请求成功",
				"data"=>$res,
			];	
			
			// 为发送get请求 
		}else{
			$data=[
				"code"=>400,
				"info"=>"请求数据不合法",
			];	
		}
		
		return $data;
	}
    
	// 查看帮助数据
	 
	public function actionHelplist(){
		if(Yii::$app->request->getMethod() === 'OPTIONS'){
			return [];
		}
		
		// 获取帮助列表
		
		$help=Yii::$app->db->createCommand("select id,title from article order by sort desc")->queryAll();
		
		return $help;
		
	}
	// 查看帮助数据详情
	 
	public function actionHelps(){
		// 获取请求类型 
		if(Yii::$app->request->getMethod() === 'OPTIONS'){
			return [];
		}
		// 判断数据是否存在 
		if($_GET['id']){
			// 获取帮助列表
			
			$help=Yii::$app->db->createCommand("select * from article where id = $_GET[id]")->queryOne();
			
			$data=[
				"code" => 200,
				"info" => "获取数据成功",
				"data" => $help,
			];
		}else{
			$data = [
				"code" => 400,
				"info" => "非法请求",
				
			];
		}

		
		return $data;
	}
	// 查看充值信息
	public function actionRecharge(){
		// 防止提交 
		if(Yii::$app->request->getMethod() === 'OPTIONS'){
			return [];
		}
		
		// 获取充值信息
		$data=Yii::$app->db->createCommand("select * from recharge order by price asc")->queryAll();
		
		return $data;
	}
	
	// 获取VIP的基本信息
	
	public function actionGetvip(){
		if(Yii::$app->request->getMethod() === 'OPTIONS'){
			return [];
		}
		
		// 判断请求是否合法
		 
		if($_GET['uid']){
			
			// 获取用户信息
			
			$user = Yii::$app->db->createCommand("select firstname,level,headImg from customer where id = $_GET[uid]")->queryOne();
			// 获取VIP信息
			
			
			$rule = Yii::$app->db->createCommand("select * from member_rule ")->queryAll();
			
			foreach($rule as &$value){
				// 获取所有的规则
				$rules = $value['rule'];
				
				// 将json字符串转成数组 
				$arr = json_decode($rules,true);
				
				
				$value['money'] =$arr['money'];
				
				// 获取对应的权限
				 
				$value['privilege'] = Yii::$app->db->createCommand("select * from privilege where id in($arr[pid]) ")->queryAll();
				
				
				
			}
			// 格式化数据
			
			$data = [
				"code" => 200,
				"info" => "数据请求成功",
				"user" => $user,
				"rule" => $rule
			];
		}else{
			$data = [
				"code" => 400,
				"info" => "非法数据请求",
			];
			
		}

		
		return $data;
	}
	
	public function actionGetvip2(){
		if(Yii::$app->request->getMethod() === 'OPTIONS'){
			return [];
		}
		
	
		
			// 获取VIP信息
			
		
		$rule = Yii::$app->db->createCommand("select * from member_rule ")->queryAll();
		
		foreach($rule as &$value){
			// 获取所有的规则
			$rules = $value['rule'];
			
			// 将json字符串转成数组 
			$arr = json_decode($rules,true);
			
			
			$value['money'] =$arr['money'];
			
			// 获取对应的权限
			 
			$value['privilege'] = Yii::$app->db->createCommand("select * from privilege where id in($arr[pid]) ")->queryAll();
			
			
			
		}
		// 格式化数据
		
		$data = [
			"code" => 200,
			"info" => "数据请求成功",
			"user" => $user,
			"rule" => $rule
		];
		
		return $data;
	}
    public function actionUpdate(){
        if(Yii::$app->request->getMethod() === 'OPTIONS'){
            return [];
        }
        $firstname = Yii::$app->request->post('firstname');
        $lastname  = Yii::$app->request->post('lastname');
        $current_password       = Yii::$app->request->post('current_password');
        $new_password           = Yii::$app->request->post('new_password');
        $confirm_new_password   = Yii::$app->request->post('confirm_new_password');
        $identity = Yii::$app->user->identity;
        $errorInfo = $this->validateParams($identity ,$firstname,$lastname,$current_password,$new_password,$confirm_new_password);
        
        if($errorInfo !== true){
            $code = Yii::$service->helper->appserver->account_edit_invalid_data;
            $data = [
                'error' => $errorInfo,
            ];
            $reponseData = Yii::$service->helper->appserver->getReponseData($code, $data);
            return $reponseData;
        }
        $identity->firstname = $firstname;
        $identity->lastname = $lastname;
        if($current_password){
            $identity->setPassword($new_password);
        }
        if ($identity->validate()) {
            $identity->save();
            
            $code = Yii::$service->helper->appserver->status_success;
            $data = [];
            $reponseData = Yii::$service->helper->appserver->getReponseData($code, $data);
            return $reponseData;
        }else{
            $errors = Yii::$service->helper->errors->getModelErrorsStrFormat($identity->errors);
            if($errors){
                $code = Yii::$service->helper->appserver->account_edit_invalid_data;
                $data = [
                    'error' => $errors,
                ];
                $reponseData = Yii::$service->helper->appserver->getReponseData($code, $data);
                return $reponseData;
            }
        }
    }
    
    public function validateParams($identity ,$firstname,$lastname,$current_password,$new_password,$confirm_new_password){
        $minNameLength = Yii::$service->customer->getRegisterNameMinLength();
        $maxNameLength = Yii::$service->customer->getRegisterNameMaxLength();
        $minPassLength = Yii::$service->customer->getRegisterPassMinLength();
        $maxPassLength = Yii::$service->customer->getRegisterPassMaxLength();
        $errorArr = [];
        if(!$identity){
            $errorArr[] = 'current user is not exist';
        }
        if ($current_password && !$new_password) {
            $errorArr[] = 'new password can not empty';
        } elseif ($current_password && ($new_password != $confirm_new_password)) {
            $errorArr[] = 'Password and confirmation password must be consistent';
            
        } elseif ($current_password && (strlen($new_password) < $minPassLength || strlen($new_password) > $maxPassLength)) {
            $errorArr[] = 'password must >= '.$minPassLength.' and <= '.$maxPassLength;
           
        } elseif (strlen($firstname) < $minNameLength || strlen($firstname) > $maxNameLength) {
            $errorArr[] = 'firstname must >= '.$minPassLength.' and <= '.$maxPassLength;
            
        } elseif (strlen($lastname) < $minNameLength || strlen($lastname) > $maxNameLength) {
            $errorArr[] = 'lastname must >= '.$minPassLength.' and <= '.$maxPassLength;
        }
        
        if($current_password && !$identity->validatePassword($current_password)){
            $errorArr[] = 'current password is not correct';
        }
        if(!empty($errorArr)){
            return implode(',',$errorArr);
        }else{
            return true;
        }
    }
    
}