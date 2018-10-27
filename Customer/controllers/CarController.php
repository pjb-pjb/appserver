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
use yii\mongodb\Query;

/**
 * @author Terry Zhao <2358269014@qq.com>
 * @since 1.0
 */
class CarController extends AppserverController
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
    
    // 加入购物车相关操作


    public function actionAddcar(){

        //获取数据
        $req = Yii::$app->request;
        // 判断数据是个请求正确
        if(!empty($req->get())){

            // 接收数据
            $shop_id=$req->get('shop_id');
            $customer_id=$req->get("customer_id");
            $product_id=$req->get("product_id");
            $product_num=$req->get("num");

            // 判断总订单表是否有数据

            $carData = Yii::$app->db->createCommand("select * from sales_flat_cart where customer_id=$customer_id")->queryOne();

            // 如果有数据直接插入购物车列表数据中
            // 如果没有数据想插入数据库表,然后再插入数据库列表中
            if ($carData['cart_id']) {

                // 更新总购物车表数据

                // 查询购物车子表中是否有数据

                $dataCarZi=Yii::$app->db->createCommand("select * from sales_flat_cart_item where cart_id='$carData[cart_id]' and product_id = '$product_id'")->queryOne();

                // 如果子表中数据存在

                if ($dataCarZi) {

                    $product_num=$dataCarZi['qty']+1;
                    $time=time();

                    $sql ="update sales_flat_cart_item set updated_at = '$time', qty = '{$product_num}' where item_id = {$dataCarZi['item_id']}";
                    $res = Yii::$app->db->createCommand($sql)->execute();

                    // 判断数据是否插入成功
                    if ($res) {

                        // 加入购物车成功
                        $msgSuc['status']=1;
                        $msgSuc['info']="加入购物车成功";
                    
                    }else{

                        // 加入购物车失败
                        $msgSuc['status']=0;
                        $msgSuc['info']="加入购物车失败";
                    }

                }else{
                    // 子表中不存在数据
                    $brr=[
                        "shop_id"   => $shop_id,
                        "cart_id"   => $carData['cart_id'],
                        "created_at"=> time(),
                        "product_id"=> $product_id,
                        "qty"       => $product_num,
                    ];

                    // 执行插入数据

                    $res=Yii::$app->db->createCommand()->insert('sales_flat_cart_item', $brr)->execute();
                    
                    // 判断数据是否插入成功
                    if ($res) {

                        // 加入购物车成功
                        $msgSuc['status']=1;
                        $msgSuc['info']="加入购物车成功";
                    
                    }else{

                        // 加入购物车失败
                        $msgSuc['status']=0;
                        $msgSuc['info']="加入购物车失败";
                    }
                }


            }else{

                // 总购物车表插入数据
                $arr = [
                    "customer_id" => $customer_id,
                    "created_at"  => time(),
                    "items_count" => 1,
                ];

                // 执行插入数据
                Yii::$app->db->createCommand()->insert('sales_flat_cart', $arr)->execute();
                $cart_id = Yii::$app->db->getLastInsertID();

                // 只购物车表插入数据

                $brr=[
                    "shop_id"   => $shop_id,
                    "cart_id"   => $cart_id,
                    "created_at"=> time(),
                    "product_id"=> $product_id,
                    "qty"       => $product_num,
                ];

                // 执行插入数据

                $res=Yii::$app->db->createCommand()->insert('sales_flat_cart_item', $brr)->execute();

                if ($res) {
                    // 加入购物车成功
                    $msgSuc['status']=1;
                    $msgSuc['info']="加入购物车成功";
                
                }else{

                    // 加入购物车失败
                    $msgSuc['status']=0;
                    $msgSuc['info']="加入购物车失败";
                }

            }
          
        }else{
            $msgSuc['status']=0;
            $msgSuc['info']="非法数据请求";
        }

        return $msgSuc;

    }


    // 查看购物车列表

    public function actionCarlist(){

       //获取数据
       $req = Yii::$app->request;
       // 判断数据是个请求正确
       if(!empty($req->get())){

           // 接受数据
           // 获取用户的id

           $customer_id=$req->get("customer_id");

           // 通过用户ID查找购物车ID

           $carData = Yii::$app->db->createCommand("select * from sales_flat_cart where customer_id=$customer_id")->queryOne();
						
						
					
           // 判断购物ID是否存在

           if ($carData) {
               
               // 通过用户的ID获取购物车列表
                // $dataDataZi = Yii::$app->db->createCommand("select * from sales_flat_cart_item where cart_id='$carData[cart_id]' order by updated_at desc")->queryAll();
							$dataDataZi = Yii::$app->db->createCommand("select * from sales_flat_cart_item where cart_id='$carData[cart_id]' group by shop_id")->queryAll();

               // 查询购物车子订单对应商品和订单数据

               // 获取数据
               $query = new Query;
							 
								foreach ($dataDataZi as $key => &$value) {
									// 查询shop表基本信息 
									$shop = Yii::$app->db->createCommand("select * from shop where shop_id=$value[shop_id]")->queryOne();
									$value['shop_name']=$shop['shop_name'];
										
									// 查询shop表基本信息 
									$value['item'] = Yii::$app->db->createCommand("select * from sales_flat_cart_item where shop_id=$value[shop_id]")->queryAll();
										
										foreach($value['item'] as $k=>&$v){
											// 查询商品信息

											$product = Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$v['product_id']]);

											// 改变
											$v['product'] = $product;
										}

								}


               $msgSuc['status']=2;
               $msgSuc['car']=$dataDataZi;


           }else{
               $msgSuc['status']=1;
               $msgSuc['info']="购物车数据为空";
           }

       }else{
           $msgSuc['status']=0;
           $msgSuc['info']="非法数据请求";
       }

       return $msgSuc;
		}
		// 购物车数量加减
		
		public function actionCarnum(){
			//获取数据
			$req = Yii::$app->request;
			// 判断数据请求类型是否正确
			if(!empty($req->get())){
				// 获取请求数据
				$type = $req->get("type");
				$item_id = $req->get("item_id");
				
				// 获取对应数量
				$item = Yii::$app->db->createCommand("select * from sales_flat_cart_item where item_id = $item_id")->queryOne();
				

				// 判断类型
				if($type=="jia"){
					// 数量加 
					$item['qty']=++$item['qty'];
					
					$sql="update sales_flat_cart_item set qty=$item[qty] where item_id=$item_id";
				}else if($type=="jian"){
					// 数量减
					$item['qty']=--$item['qty'];
					
					// 判断如果数量为0 删除数据
					if($item['qty']<=0){
						$sql="delete from sales_flat_cart_item where item_id = $item_id";
					}else{
						$sql="update sales_flat_cart_item set qty=$item[qty] where item_id = $item_id";
					}
				}
				
				$res = Yii::$app->db->createCommand($sql)->execute();

				// 执行sql语句
				if($res){
					$data = [
						"status" => 200,
						"info" 	 => "修改成功",
					];
				}else{
					$data = [
						"status" => 400,
						"info" 	 => "修改失败",
					];
				}
			}else{
				$data = [
					"status" => 400,
					"info" 	 => "非法数据请求",
				];
			}
			
			return $data;
		}
		
		// 删除购物车数据
		public function actionCardel(){
			
			//获取数据
			$req = Yii::$app->request;
			// 判断数据是个请求正确
			if(!empty($req->get())){
				
				// 判断数据是否请求
				$cart_id = $req->get("cart_id");
				$shop_id = $req->get("shop_id");
				if($cart_id && $shop_id){
					// sql 语句
					$sql="delete from sales_flat_cart_item where cart_id = $cart_id and shop_id = $shop_id";
					// 发送sql语句删除数据
					$res = Yii::$app->db->createCommand($sql)->execute();
					// 删除数据
					if($res){
						// 删除成功
						$data = [
							"status" => 200,
							"info" => "删除成功",
						];
					}else{
						// 删除失败
						$data = [
							"status" => 400,
							"info" => "删除失败",
						];
					}
					
				}else{
					$data = [
						"status" => 400,
						"info" => "非法数据请求",
					];
				}
			}else{
				$data = [
					"status" => 400,
					"info" => "非法数据请求",
				];
			}
			
			return $data;
		}
		// 获取商品对应的优惠券信息
		
		public function actionGetcoupon(){
			//获取数据
			$req = Yii::$app->request;
			// 判断数据是个请求正确
			if(!empty($req->get())){
				// 获取数据
				$customer_id=$req->get("customer_id");
				$product_id=$req->get("product_id");
				$money=$req->get("money");
				$shop_id=$req->get("shop_id");


				$time = time();
				// sql 语句
				$sql="select * from sales_coupon_usage coupon_use, sales_coupon coupon where coupon_use.customer_id = $customer_id and coupon_use.coupon_id = coupon.coupon_id and expiration_date> $time and (coupon.goods=0 or coupon.goods like '%$product%') and coupon_use.status = 0 and (coupon.conditions = 0 or coupon.conditions<= $money) and coupon.shop_id = $shop_id and $money > discount";
				// 发送sql语句
				$res = Yii::$app->db->createCommand($sql)->queryAll();
				
				foreach($res as $key=> &$value){
					$value['expiration_date'] = date("Y-m-d H:i:s",$value['expiration_date']);
				}
				
				$data =[
					"code" => 200,
					"info" => "获取数据成功",
					"data" => $res
				];
				
			}else{
				$data['code'] = 400;
				$data['info'] = "非法数据请求";
			}
			
			return $data;
		}
		
		// 获取金币信息
		public function actionGetcoin(){
			//获取数据
			$req = Yii::$app->request;
			//判断数据请求是否正确
			if(!empty($req->get()) && $_GET['money'] && $_GET['customer_id']){
				
				// 判断此人是否有金币
				
				$sql="select * from customer where id = $_GET[customer_id]";
				
				$user = Yii::$app->db->createCommand($sql)->queryOne();
				
				if($user['coin']){
					// 获取金币表中
					$sql="select * from coin_set where `condition` <= $_GET[money]  order by `condition` desc";
					
					$coin = Yii::$app->db->createCommand($sql)->queryOne();
					
					// 判断此商品不能使用金币 
					
					if($coin){
						// 判断用户的金币是否大于优惠金币
						// 计算用户金币的使用量
						
						if($user['coin']>=$coin['coin_num']){
							// 如果用户金币多
							$data=[
								"code" => 200,
								"info" => "请求成功",
								"coin" => $coin['coin_num'],
							];
						}else{
							// 如果用户金币少
							$data=[
								"code" => 200,
								"info" => "请求成功",
								"coin" => $user['coin'],
							];
						}
					}else{
						$data =[
							"code" => 200,
							"info" => "此商品不能使用金币",
							"coin" => 0,
						];
					}
					
				}else{
					// 用户没有金币的情况
					$data =[
						"code" => 200,
						"info" => "此人没有金币",
						"coin" => 0,
					];
				}
			
			}else{
				// 非法请求数据的时候
				$data =[
					"code" => 400,
					"info" => "非法数据请求"
				];
			}
			
			return $data;

		}
		
		// 获取商品信息
		
		public function actionGetgoods(){
			//获取数据
			$req = Yii::$app->request;
			
			$product_id = $req->get("id");
			// 判断数据是个请求正确
			if(!empty($req->get())){
				$product=Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$product_id]);
				
				// 非法请求数据的时候
				$data =[
					"code" => 200,
					"info" => "数据获取成功",
					"data" => $product
				];
			}else{
				// 非法请求数据的时候
				$data =[
					"code" => 400,
					"info" => "非法数据请求"
				];
			}
			return $data;
		}

		// 立即下单生成订单
	public function actionCreateorder(){


		 //获取数据
		 $req = Yii::$app->request;
		 // 判断数据是个请求正确
		 if(!empty($req->post())){

				 // 接受数据

				 $shop_id=$req->post('shop_id');
				 $customer_id=$req->post("customer_id");
				 $product_id=$req->post("product_id");
				 $address_id=$req->post("address_id");
				 $coupon_id=$req->post("coupon_id");
				 $coin=$req->post("coin");
				 $order_remark = $req->post("order_remark");


				 $goods_type=1;

				 // 查看产品信息
				 $product=Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$product_id]);
						 
				 // 查看地址信息
				 $address=Yii::$app->db->createCommand("select * from customer_address where address_id = '$address_id'")->queryOne();
				
				 // 获取优惠券
					if($coupon_id){
						$coupon = Yii::$app->db->createCommand("select * from sales_coupon where coupon_id = '$coupon_id'")->queryOne();
					}

				 // 格式化数据

					$brr=[
							"order_status"      =>0,
							"shop_id"           =>$shop_id,
							"created_at"        =>time(),
							"customer_id"       =>$customer_id,
							"goods_type"        =>$goods_type,
							"increment_id"      =>date("YmdHis").rand().$customer_id,
							"fuwu_money"				=>0,
							"items_count"       =>1,
							"customer_telephone"       => $address['telephone'],
							"customer_address_state"   => $address['state'],
							"customer_address_city"    => $address['city'],
							"customer_address_street1" => $address['street1'],
							"customer_address_street2" => $address['street2'],
							"customer_firstname"       => $address['first_name'],
							"customer_email"           => $address['email'],               
							"order_remark"             => $order_remark,
							"coin_num"								 => $coin,
							"coupon_code"							 => $coupon_id,
							// 支付订单总额
							"subtotal"          			 => $product['special_price']*1,
							// 实际支付订单总额
							"grand_total"							 => $product['special_price']*1 - $coin*0.1 - $coupon['discount'],
							// 总优惠的金额 
							"subtotal_with_discount"   => $coin*0.1 + $coupon['discount'],
					];

				 // 插入数据

				 // 执行插入数据
				 Yii::$app->db->createCommand()->insert('sales_flat_order', $brr)->execute();
				 $order_id = Yii::$app->db->getLastInsertID();

				 // sales_flat_order_item
				 $arr=[

						 "order_id"            =>$order_id,
						 "customer_id"         =>$customer_id,
						 "created_at"          =>time(),
						 "product_id"          =>$product_id,
						 "sku"                 =>$product[sku],
						 "name"                =>$product['name']['name_zh'],
						 "image"               =>$product['image']['main']['image'],
						 "qty"                 =>1,
						 "price"               =>$product['special_price'],
						 "good_type"           =>$goods_type,

				 ];


				 $res=Yii::$app->db->createCommand()->insert('sales_flat_order_item', $arr)->execute();

				 if ($res) {
					 // 核销优惠券
						if($coupon_id){
						 $sql="update sales_coupon_usage set status = 1 where customer_id = $customer_id and coupon_id = $coupon_id";
						 Yii::$app->db->createCommand($sql)->execute();
						}
					 // 减去用户的金币
							// 1.获取用户金币总数
							 $sql = "select coin from customer where id = $customer_id";
							 $data = Yii::$app->db->createCommand($sql)->queryOne();
							 
							 $coins = $data['coin'] - $coin;
							// 2.修改用户的金币
							 $sql="update customer set coin = $coins  where id = $customer_id";
							 Yii::$app->db->createCommand($sql)->execute();
						// 更新用户的金币记录
							
							$arr=[
								"uid"=>$customer_id,
								"time"=>time(),
								"type"=>2,
								"coinNum"=>$coin,							
							];
							Yii::$app->db->createCommand()->insert('coin_record', $arr)->execute();
					 
					 $msgSuc['status']=1;
					 $msgSuc['info']="生成订单成功";
					 $msgSuc['order_id']=$order_id;
				 }else{
						 $msgSuc['status']=0;
						 $msgSuc['info']="生成订单失败";
				 }
		 }else{
				 $msgSuc['status']=0;
				 $msgSuc['info']="非法数据请求";
		 }

		 return $msgSuc;
   }
	 
	 	// 购车车下单生成订单
	 	public function actionCreateorder2(){
			
			// 获取get请求内容
			
			$customer_id = $_GET['customer_id'];
			$address_id  = $_GET['address_id'];	 
 
			// 获取get 请求额内哦让那个
			$arr = json_decode($_POST['info'],true);
			

	 		 //获取数据
	 		 $req = Yii::$app->request;
	 		 // 判断数据是个请求正确
	 		 if(!empty($req->post())){
					
					$orderArr=[];
					// 查看地址信息
					$address=Yii::$app->db->createCommand("select * from customer_address where address_id = '$address_id'")->queryOne();
					foreach($arr as $key => $value){
						
						// 获取优惠券
						if($value[coupon_id]){
							$coupon = Yii::$app->db->createCommand("select * from sales_coupon where coupon_id = '$value[coupon_id]'")->queryOne();
						}
						
						// 计算商品总价
						
						$money = 0;
						
						foreach($value['goods'] as $k => $v){
							
							// 获取商品信息
							$product = Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$v['product_id']]);
							
							//计算总价
							 
							$money += $product['special_price']*$v['num'];
						}
						
						// 格式化数据 
						$goods_type=1;
						$brr=[
								"order_status"      =>0,
								"shop_id"           =>$value['shop_id'],
								"created_at"        =>time(),
								"customer_id"       =>$customer_id,
								"goods_type"        =>$goods_type,
								"increment_id"      =>date("YmdHis").rand().$customer_id,
								"fuwu_money"				=>0,
								"items_count"       =>1,
								"customer_telephone"       => $address['telephone'],
								"customer_address_state"   => $address['state'],
								"customer_address_city"    => $address['city'],
								"customer_address_street1" => $address['street1'],
								"customer_address_street2" => $address['street2'],
								"customer_firstname"       => $address['first_name'],
								"customer_email"           => $address['email'],               
								"order_remark"             => $value[order_remark],
								"coin_num"								 => $value[coin],
								"coupon_code"							 => $value[coupon_id],
								// 支付订单总额
								"subtotal"          			 => $money,
								// 实际支付订单总额
								"grand_total"							 => $money - $value[coin]*0.1 - $coupon['discount'],
								// 总优惠的金额 
								"subtotal_with_discount"   => $value[coin]*0.1 + $coupon['discount'],
						];
		
						// 插入数据
		
						// 执行插入数据
						Yii::$app->db->createCommand()->insert('sales_flat_order', $brr)->execute();
						$order_id = Yii::$app->db->getLastInsertID();
						
						
						$orderArr[]=$order_id;
						
						foreach($value['goods'] as $k => $v){
							
							// 获取商品信息
							$product = Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$v['product_id']]);
							
							// sales_flat_order_item
							$arr=[
			
									"order_id"            =>$order_id,
									"customer_id"         =>$customer_id,
									"created_at"          =>time(),
									"product_id"          =>$v['product_id'],
									"sku"                 =>$product[sku],
									"name"                =>$product['name']['name_zh'],
									"image"               =>$product['image']['main']['image'],
									"qty"                 =>$v[num],
									"price"               =>$product['special_price'],
									"good_type"           =>$goods_type,
			
							];
							
							$res=Yii::$app->db->createCommand()->insert('sales_flat_order_item', $arr)->execute();
			
			
						}
						// 核销优惠券
						if($value[coupon_id]){
							$sql="update sales_coupon_usage set status = 1 where customer_id = $customer_id and coupon_id = $value[coupon_id]";
							Yii::$app->db->createCommand($sql)->execute();
						}
							
						
						// 减去用户的金币
							
						// 更新用户的金币记录
							
							$arr=[
								"uid"=>$customer_id,
								"time"=>time(),
								"type"=>2,
								"coinNum"=>$value[coin],							
							];
							Yii::$app->db->createCommand()->insert('coin_record', $arr)->execute();
						
						// 1.获取用户金币总数
							$sql = "select coin from customer where id = $customer_id";
							$data = Yii::$app->db->createCommand($sql)->queryOne();
							
							$coins = $data['coin'] - $value[coin];
						// 2.修改用户的金币
							$sql="update customer set coin = $coins  where id = $customer_id";
							Yii::$app->db->createCommand($sql)->execute();
							
							
						// 删除对应的购物车
						foreach($value['goods'] as $k => $v){
							
							$sql = "delete from sales_flat_cart_item where item_id = $v[item_id]";
							Yii::$app->db->createCommand($sql)->execute();
						}
						
						

					}
	 
					
					
	 					 
	 					 $msgSuc['status']=1;
	 					 $msgSuc['info']="生成订单成功";
						 $msgSuc['order']=join(',',$orderArr);

	 		 }else{
	 				 $msgSuc['status']=0;
	 				 $msgSuc['info']="非法数据请求";
	 		 }
	 
	 		 return $msgSuc;
	    }
			
	// 生成服务订单
	public function actionCreateorder3(){
	
	
			 //获取数据
			 $req = Yii::$app->request;
			 // 判断数据是个请求正确
			 if(!empty($req->post())){
	
					 // 接受数据
	
					 $shop_id=$req->post('shop_id');
					 $customer_id=$req->post("customer_id");
					 $product_id=$req->post("product_id");
					 $address_id=$req->post("address_id");
					 $coupon_id=$req->post("coupon_id");
					 $coin=$req->post("coin");
					 $order_remark = $req->post("order_remark");
	
	
					 $goods_type=2;
	
					 // 查看产品信息
					 $product=Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$product_id]);
							 
					 // 查看地址信息
					 $address=Yii::$app->db->createCommand("select * from customer_address where address_id = '$address_id'")->queryOne();
					
					 // 获取优惠券
						if($coupon_id){
							$coupon = Yii::$app->db->createCommand("select * from sales_coupon where coupon_id = '$coupon_id'")->queryOne();
						}
	
					 // 格式化数据
	
						$brr=[
								"order_status"      =>0,
								"shop_id"           =>$shop_id,
								"created_at"        =>time(),
								"customer_id"       =>$customer_id,
								"goods_type"        =>$goods_type,
								"fuwu_money"        =>$product['deposit'],
								"increment_id"      =>date("YmdHis").rand().$customer_id,
								"items_count"       =>1,
								"customer_telephone"       => $address['telephone'],
								"customer_address_state"   => $address['state'],
								"customer_address_city"    => $address['city'],
								"customer_address_street1" => $address['street1'],
								"customer_address_street2" => $address['street2'],
								"customer_firstname"       => $address['first_name'],
								"customer_email"           => $address['email'],               
								"order_remark"             => $order_remark,
								"coin_num"								 => $coin,
								"coupon_code"							 => $coupon_id,
								// 支付订单总额
								"subtotal"          			 => $product['deposit'],
								// 实际支付订单总额
								"grand_total"							 => $product['deposit'],
								// 总优惠的金额 
								"subtotal_with_discount"   => 0,
						];
	
					 // 插入数据
	
					 // 执行插入数据
					 Yii::$app->db->createCommand()->insert('sales_flat_order', $brr)->execute();
					 $order_id = Yii::$app->db->getLastInsertID();
	
					 // sales_flat_order_item
					 $arr=[
	
							 "order_id"            =>$order_id,
							 "customer_id"         =>$customer_id,
							 "created_at"          =>time(),
							 "product_id"          =>$product_id,
							 "sku"                 =>$product[sku],
							 "name"                =>$product['name']['name_zh'],
							 "image"               =>$product['image']['main']['image'],
							 "qty"                 =>1,
							 "price"               =>$product['deposit'],
							 "good_type"           =>$goods_type,
	
					 ];
	
	
					 $res=Yii::$app->db->createCommand()->insert('sales_flat_order_item', $arr)->execute();
	
					 if ($res) {
						 // 核销优惠券
							if($coupon_id){
							 $sql="update sales_coupon_usage set status = 1 where customer_id = $customer_id and coupon_id = $coupon_id";
							 Yii::$app->db->createCommand($sql)->execute();
							}
						 // 减去用户的金币
								// 1.获取用户金币总数
								 $sql = "select coin from customer where id = $customer_id";
								 $data = Yii::$app->db->createCommand($sql)->queryOne();
								 
								 $coins = $data['coin'] - $coin;
								// 2.修改用户的金币
								 $sql="update customer set coin = $coins  where id = $customer_id";
								 Yii::$app->db->createCommand($sql)->execute();
							// 更新用户的金币记录
								
								$arr=[
									"uid"=>$customer_id,
									"time"=>time(),
									"type"=>2,
									"coinNum"=>$coin,							
								];
								Yii::$app->db->createCommand()->insert('coin_record', $arr)->execute();
						 
						 $msgSuc['status']=1;
						 $msgSuc['info']="生成订单成功";
						 $msgSuc['order']=$order_id;

						 
					 }else{
							 $msgSuc['status']=0;
							 $msgSuc['info']="生成订单失败";
					 }
			 }else{
					 $msgSuc['status']=0;
					 $msgSuc['info']="非法数据请求";
			 }
	
			 return $msgSuc;
	   }
	 
	 public function actionGetcsrf(){
	 		return  Yii::$app->request->csrfToken;
	 }
	 
	// 获取服务关联商品
	 
	public function actionPayservicefees(){
		
		//获取数据
		$req = Yii::$app->request;
		// 判断数据是个请求正确
		if(!empty($req->get())){
			
			// 获取订单ID
			$order_id=$req->get("order_id");
			
			
			
			// $order_id = 263;
			
			// 获取订单相关信息
			// $sql="select * from sales_flat_order where order_id = $order_id";
			// $orderData = Yii::$app->db->createCommand($sql)->queryOne();
			
			// 查出订单对应的服务
			$sql="select * from sales_flat_order_item where order_id = $order_id and good_type=2";
			$itemData =  Yii::$app->db->createCommand($sql)->queryOne();
			
			$product_id = $itemData['product_id'];
			
			// 根据商品查询关联商品信息
			
			$product=Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$product_id]);
			
			$guanlian = $product['guanlian'];
			
			// 查询关联商品的基本信息
			
			$data=[];
			
			foreach($guanlian as $key=>$value){
				
				$data[]=Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$value]);
			}
			
			// 返回商品的关联数据
			
			$msgSuc['status']=200;
			$msgSuc['data']=$data;
			$msgSuc['order_id']=$order_id;
			
			// 返回数据包括关联商品和订单号
			
			
			
		}else{
			$msgSuc['status']=0;
			$msgSuc['info']="非法数据请求";
		}
		return $msgSuc;
	}
	
	
	// 完成服务的下单
	
	public function actionPayservicefees2(){
		//获取数据
		$req = Yii::$app->request;
		// 判断数据是个请求正确
		if(!empty($req->post())){
			
			// 获取订单ID
			$order_id=$req->post("order_id");
			
			
			$sql="select * from sales_flat_order where order_id = $order_id";
			$orderData = Yii::$app->db->createCommand($sql)->queryOne();
			
			// 获取get 请求额内哦让那个
			$arr = json_decode($_POST['info'],true);
				// 计算商品总价
			
				$money = 0;
					
					foreach($arr as $key => $value){
							
							// 获取商品信息
							$product = Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$value['id']]);
							
							//计算总价
							 
							$money += $product['special_price']*$value['numbers'];
					}
						
						// 格式化数据 
						
						$brr=[
								"order_id"								 =>$order_id,
								// 支付订单总额
								"subtotal"          			 => $money+$orderData['fuwu_money'],
								// 实际支付订单总额
								"grand_total"							 => $money+$orderData['fuwu_money'],
								// 总优惠的金额 
								"subtotal_with_discount"   => 0,
						];
		
						// 插入数据
						$sql="update sales_flat_order set subtotal = ".($money+$orderData['fuwu_money']).", grand_total = ".($money+$orderData['fuwu_money'])." where order_id = $order_id";
						Yii::$app->db->createCommand($sql)->execute();
						// 执行插入数据
	
						
						foreach($arr as $k => $v){
							
							// 获取商品信息
							$product = Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$v['id']]);
							
							// sales_flat_order_item
							$arr=[
			
									"order_id"            =>$order_id,
									"customer_id"         =>$orderData['customer_id'],
									"created_at"          =>time(),
									"product_id"          =>$product[product_id],
									"sku"                 =>$product[sku],
									"name"                =>$product['name']['name_zh'],
									"image"               =>$product['image']['main']['image'],
									"qty"                 =>$v[numbers],
									"price"               =>$product['special_price'],
									"good_type"           =>1,
			
							];
							
							$res=Yii::$app->db->createCommand()->insert('sales_flat_order_item', $arr)->execute();
			
			
						}
						
						 $msgSuc['status']=1;
						 $msgSuc['info']="服务订单完成";
			
			
		}else{
			$msgSuc['status']=0;
			$msgSuc['info']="非法数据请求";
		}
		return $msgSuc;
	}
	 
	 // 多商品下单
	 
	public function actionGetcargoods(){
		
		//获取数据
		$req = Yii::$app->request;
		
		$arr = json_decode($_POST['info'],true);
		// 判断此人是否有金币
		$customer_id = $_GET[customer_id];
	

		$time = time();
		
		$sql="select * from customer where id = $customer_id";
		
		$user = Yii::$app->db->createCommand($sql)->queryOne();
		
		
		$coinss = 0;
		//  根据数据处理
		$newArr=[];
		
		foreach($arr as $key => $value){
			
			// 获取商家信息
			$newArr[$key]["shop"] = Yii::$app->db->createCommand("select shop_name,shop_id from shop where shop_id=$value[id]")->queryOne();
			
			$money = 0;
			// 获取商品信息
			foreach($value['goods'] as $k => $v){
				$newArr[$key]['goods'][$k]=Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$v[id]]);
				$newArr[$key]['goods'][$k]['num']=$v[num];
				$newArr[$key]['goods'][$k]['item_id']=$v[item_id];

				
				$money += $v[num]*	$newArr[$key]['goods'][$k]['special_price'];
			}
			
			// 查看用户的金币
			
				// 获取金币表中
				$sql="select * from coin_set where `condition` <= $money  order by `condition` desc";
				$coin = Yii::$app->db->createCommand($sql)->queryOne();
	
			// 获取金币的使用情况 
				// 判断用户的金币是否大于优惠金币
				// 计算用户金币的使用量
			
				
				if($user['coin']>=$coin['coin_num']+$coinss){
					// 如果用户金币多
					$newArr[$key]["coin"] = $coin['coin_num'];
					$coinss += $coin['coin_num'];
				}else{
					// 如果用户金币少
					if($user['coin'] - $coinss >0){
						$aaa=$user['coin'] - $coinss;
					}else{
						$aaa=0;
					}
					$newArr[$key]["coin"] = $aaa;
					$coinss += $aaa;
				}
				
			
			// 获取优惠券信息
			
			// sql 语句
			$sql="select * from sales_coupon_usage coupon_use, sales_coupon coupon where coupon_use.customer_id = $customer_id and coupon_use.coupon_id = coupon.coupon_id and expiration_date> $time and coupon.goods=0 and coupon_use.status = 0 and (coupon.conditions = 0 or coupon.conditions<= $money) and coupon.shop_id = $value[id] and $money > discount";
			// 发送sql语句
			$newArr[$key]['coupon'] = Yii::$app->db->createCommand($sql)->queryAll();
			
			foreach($newArr[$key]['coupon'] as $a=> &$b){
				$b['expiration_date'] = date("Y-m-d H:i:s",$b['expiration_date']);
			}
		}
		return $newArr;
			
		
	}
	
	
	// 获取用户的余额
	public function actionGetyue(){
		
		//获取数据

		$req = Yii::$app->request;
		
		// 获取用户的ID
		$customer_id = $req->get("customer_id");
		
		// 获取用户的余额
		$sql="select * from customer where id = $customer_id";
		$data = Yii::$app->db->createCommand($sql)->queryOne();
		
		return $data;
	}
	
	
	// 立即下单余额支付
	
	public function actionYuepay(){
		
		//获取数据
		$req = Yii::$app->request;
		
		// 获取需要支付money
		$money = $req->get("money");
		
		// 获取订单号
		$order = $req->get("order_id");
		
		// 获取用户的ID
		$customer_id = $req->get("customer_id");
		
		// 判断用户余额是否够
		
		// 获取用户的余额
		$sql="select * from customer where id = $customer_id";
		$data = Yii::$app->db->createCommand($sql)->queryOne();
		
		if($data['money'] >= $money ){
			
			// 处理余额
			// 修改用户余额
			$yue = $data['money']-$money;	
			$sql = "update customer set money = $yue where id = $customer_id ";
			Yii::$app->db->createCommand($sql)->execute();
			
			// 修改用户余额消费记录
			
			$arr=[
				"uid"=>$customer_id,
				"time"=>time(),
				// 2 代表消费 1代表余额增加
				"type"=>2,
				"moneyNum"=>$money,							
			];
			Yii::$app->db->createCommand()->insert('money_record', $arr)->execute();
			
			$time = time();
			// 修改订单状态
			$sql = "update sales_flat_order set order_status = 1,is_pay=1,pay_time = $time,pay_type=1 where order_id  = $order ";
			
			Yii::$app->db->createCommand($sql)->execute();
			
			// 修改用户的金币
			$coins =  $data['coin']+floor($money/10);
			$sql="update customer set coin = $coins  where id = $customer_id";
			Yii::$app->db->createCommand($sql)->execute();
			// 更新用户的金币记录
				
			$arr=[
				"uid"=>$customer_id,
				"time"=>time(),
				"type"=>1,
				"coinNum"=> floor($money/10),							
			];
			Yii::$app->db->createCommand()->insert('coin_record', $arr)->execute();
		
			$dataItem = Yii::$app->db->createCommand("SELECT * FROM sales_flat_order_item where order_id = $order")->queryAll();
						 
			// 修改每一个商品的销量
			
			foreach($dataItem as $key=>$value){
				
				// 获取商品的id
				
				$product_id = $value['product_id'];
				
				// 获取商品的数量
				
				$num  = $value['qty'];
				
				// 修改对应的销量
				
				$goods = Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$product_id]);
				
				$volume = $goods['volume']+	$num;
				
				Yii::$app->mongodb->getCollection('product_flat')->update(['_id'=>$product_id],['$set'=>['volume'=>(int)$volume]]);

				
			 
			}
				
			// 提示支付成功
			$msg=[
				"code"=>200,
				"info"=>"支付成功",
				"coinNum"=>floor($money/10),
			];			
		}else{
			$msg=[
				"code"=>400,
				"info"=>"用户余额不足",
			];
		}
		
		return $msg;
		
	}
	
	
	// 购物车余额支付
	public function actionYuepay2(){
		
		//获取数据
		$req = Yii::$app->request;
		
		// 获取需要支付money
		$money = $req->get("money");
		
		// 获取订单号
		$order = $req->get("order_id");
		
		// 获取用户的ID
		$customer_id = $req->get("customer_id");
		
		// 判断用户余额是否够
		
		// 获取用户的余额
		$sql="select * from customer where id = $customer_id";
		$data = Yii::$app->db->createCommand($sql)->queryOne();
		
		if($data['money'] >= $money ){
			
			// 处理余额
			// 修改用户余额
			$yue = $data['money']-$money;	
			$sql = "update customer set money = $yue where id = $customer_id ";
			Yii::$app->db->createCommand($sql)->execute();
			
			// 修改用户余额消费记录
			
			$arr=[
				"uid"=>$customer_id,
				"time"=>time(),
				// 2 代表消费 1代表余额增加
				"type"=>2,
				"moneyNum"=>$money,							
			];
			Yii::$app->db->createCommand()->insert('money_record', $arr)->execute();
			
			
			
			// 修改用户的金币
			$coins =  $data['coin']+floor($money/10);
			$sql="update customer set coin = $coins  where id = $customer_id";
			Yii::$app->db->createCommand($sql)->execute();
			// 更新用户的金币记录
				
			$arr=[
				"uid"=>$customer_id,
				"time"=>time(),
				"type"=>1,
				"coinNum"=> floor($money/10),							
			];
			Yii::$app->db->createCommand()->insert('coin_record', $arr)->execute();
			
			$time = time();
			
	
			// 修改订单状态
			$sql = "update sales_flat_order set order_status = 1,is_pay=1,pay_time = $time,pay_type=1 where order_id  in( $order )";
			
			
			Yii::$app->db->createCommand($sql)->execute();
			
				$dataItem = Yii::$app->db->createCommand("SELECT * FROM sales_flat_order_item where order_id in($order)")->queryAll();
							 
				// 修改每一个商品的销量
				
				foreach($dataItem as $key=>$value){
					
					// 获取商品的id
					
					$product_id = $value['product_id'];
					
					// 获取商品的数量
					
					$num  = $value['qty'];
					
					// 修改对应的销量
					
					$goods = Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$product_id]);
					
					$volume = $goods['volume']+	$num;
					
					Yii::$app->mongodb->getCollection('product_flat')->update(['_id'=>$product_id],['$set'=>['volume'=>(int)$volume]]);
	
					
				 
				}
	
			// 提示支付成功
			$msg=[
				"code"=>200,
				"info"=>"支付成功",
				"coinNum"=>floor($money/10),
			];			
		}else{
			$msg=[
				"code"=>400,
				"info"=>"用户余额不足",
			];
		}
		
		return $msg;
		
	}
	
	
	// 服务订单余额支付
	public function actionYuepay3(){
		
		//获取数据
		$req = Yii::$app->request;
		
		// 获取需要支付money
		$money = $req->get("money");
		
		// 获取订单号
		$order = $req->get("order_id");
		
		// 获取用户的ID
		$customer_id = $req->get("customer_id");
		
		// 判断用户余额是否够
		
		// 获取用户的余额
		$sql="select * from customer where id = $customer_id";
		$data = Yii::$app->db->createCommand($sql)->queryOne();
		
		if($data['money'] >= $money ){
			
			// 处理余额
			// 修改用户余额
			$yue = $data['money']-$money;	
			$sql = "update customer set money = $yue where id = $customer_id ";
			Yii::$app->db->createCommand($sql)->execute();
			
			// 修改用户余额消费记录
			
			$arr=[
				"uid"=>$customer_id,
				"time"=>time(),
				// 2 代表消费 1代表余额增加
				"type"=>2,
				"moneyNum"=>$money,							
			];
			Yii::$app->db->createCommand()->insert('money_record', $arr)->execute();
			
			
			
			// 修改用户的金币
			$coins =  $data['coin']+floor($money/10);
			$sql="update customer set coin = $coins  where id = $customer_id";
			Yii::$app->db->createCommand($sql)->execute();
			// 更新用户的金币记录
				
			$arr=[
				"uid"=>$customer_id,
				"time"=>time(),
				"type"=>1,
				"coinNum"=> floor($money/10),							
			];
			Yii::$app->db->createCommand()->insert('coin_record', $arr)->execute();
			
			$time = time();
			
	
			// 修改订单状态
			$sql = "update sales_flat_order set order_status = 4,is_pay=1,pay_time = $time,pay_type=1,is_wei=1 where order_id =  $order";
			
			
			Yii::$app->db->createCommand($sql)->execute();
			
				$dataItem = Yii::$app->db->createCommand("SELECT * FROM sales_flat_order_item where order_id =$order")->queryAll();
							
				// 修改每一个商品的销量
				
				foreach($dataItem as $key=>$value){
					
					
					if($value['good_type']==1){
						
					
					
						// 获取商品的id
						
						$product_id = $value['product_id'];
						
						// 获取商品的数量
						
						$num  = $value['qty'];
						
						// 修改对应的销量
						
						$goods = Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$product_id]);
						
						$volume = $goods['volume']+	$num;
						
						Yii::$app->mongodb->getCollection('product_flat')->update(['_id'=>$product_id],['$set'=>['volume'=>(int)$volume]]);
				
					}
				
				}
	
			// 提示支付成功
			$msg=[
				"code"=>200,
				"info"=>"支付成功",
				"coinNum"=>floor($money/10),
			];			
		}else{
			$msg=[
				"code"=>400,
				"info"=>"用户余额不足",
			];
		}
		
		return $msg;
		
	}
	// 处理支付成功
	
	// 立即下单支付成功回掉
	public function actionPayok(){
		
	
		
		// 接收返回的xml数据
			$xmlData = file_get_contents('php://input');
		
		  // 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了  
			 
			//将服务器返回的XML数据转化为数组  
			$data = json_decode(json_encode(simplexml_load_string($xmlData,'SimpleXMLElement',LIBXML_NOCDATA)),true); 
			
			
		   // 判断签名是否正确  判断支付状态  
		   if (($data['return_code']=='SUCCESS') && ($data['result_code']=='SUCCESS') ) {  
			   $result = $data;  
			   // 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了  
				 
			   //获取服务器返回的数据  
			   $order_id = $data['out_trade_no'];  //订单单号  
			   $id = $data['attach'];        //附加参数,选择传递订单ID   
			   $openid = $data['openid'];          //付款人openID  
			   $transaction_id = $data['transaction_id'];    //付款金额  
			   $pay_time=time();
			   //更新数据库  
				
			   $time=time();
			   // pay_type=2 是微信支付
			   // is_pay = 1 是否支付
			   $sql = "update sales_flat_order set order_status = 1,is_pay=1,pay_time = $time,pay_type=2 where order_id  = $order_id ";
		
				 Yii::$app->db->createCommand($sql)->execute();

			   // 当前销量
				 
				 // 查询所有的子订单修改对应商品的销量
				 // 1、查看所有的子订单
				 
				$dataItem = Yii::$app->db->createCommand("SELECT * FROM sales_flat_order_item where order_id = $order_id")->queryAll();
				 
				// 修改每一个商品的销量
				
				foreach($dataItem as $key=>$value){
					
					// 获取商品的id
					
					$product_id = $value['product_id'];
					
					// 获取商品的数量
					
					$num  = $value['qty'];
					
					// 修改对应的销量
					
					$goods = Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$product_id]);
					
					$volume = $goods['volume']+	$num;
					
					Yii::$app->mongodb->getCollection('product_flat')->update(['_id'=>$product_id],['$set'=>['volume'=>(int)$volume]]);

					
				 
				}
				
				// 修改用户的金币
				
				
				// 获取订单详情
				// 通过订单获取用户ID
				$sql = "SELECT * FROM sales_flat_order where order_id  = $order_id ";
				$data = Yii::$app->db->createCommand($sql)->queryOne();
				
				
				// 查询用户相关信息
				
				$user = Yii::$app->db->createCommand("SELECT * FROM customer WHERE id = $data[customer_id]")->queryOne();
				
				
				$coins =  $user['coin']+floor($data['grand_total']/10);
				echo $sql="update customer set coin = $coins  where id = $data[customer_id]";
				Yii::$app->db->createCommand($sql)->execute();
				// 更新用户的金币记录
					
				$arr=[
					"uid"=>$data['customer_id'],
					"time"=>time(),
					"type"=>1,
					"coinNum"=> floor($data['grand_total']/10),							
				];
				Yii::$app->db->createCommand()->insert('coin_record', $arr)->execute();
				 
				 
			   // 未修改商品销量
		   }else{  
			   $result = false;  
		   }  
		   // 返回状态给微信服务器  
		   if ($result) {  
			   $str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';  
		   }else{  
			   $str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';  
		   }  
		   echo $str;  
	}
	
	
	
	// 购物车微信支付下单回掉
		public function actionPayok2(){
			
		
			
			// 接收返回的xml数据
				$xmlData = file_get_contents('php://input');
			
			  // 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了  
				 
				//将服务器返回的XML数据转化为数组  
				$data = json_decode(json_encode(simplexml_load_string($xmlData,'SimpleXMLElement',LIBXML_NOCDATA)),true); 
				
				

				
				
			   // 判断签名是否正确  判断支付状态  
			   if (($data['return_code']=='SUCCESS') && ($data['result_code']=='SUCCESS') ) {  
				   $result = $data;  
				   // 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了  
					 
				   //获取服务器返回的数据  
				   $order_id = $data['attach'];  //订单单号  
				   // $id = $data['attach'];        //附加参数,选择传递订单ID   
				   $openid = $data['openid'];          //付款人openID  
				   $transaction_id = $data['transaction_id'];    //付款金额  
				   $pay_time=time();
				   //更新数据库  
					
				   $time=time();
				   // pay_type=2 是微信支付
				   // is_pay = 1 是否支付
				   $sql = "update sales_flat_order set order_status = 1,is_pay=1,pay_time = $time,pay_type=2 where order_id in($order_id)";
			
					 Yii::$app->db->createCommand($sql)->execute();
	
				   // 当前销量
					 
					 // 查询所有的子订单修改对应商品的销量
					 
					 $order = explode(',',$order_id);
					 
					 
					 foreach( $order as $k=>$v){
						 
					
						 // 1、查看所有的子订单
						 
						$dataItem = Yii::$app->db->createCommand("SELECT * FROM sales_flat_order_item where order_id = $v")->queryAll();
						 
						// 修改每一个商品的销量
						
						foreach($dataItem as $key=>$value){
							
							// 获取商品的id
							
							$product_id = $value['product_id'];
							
							// 获取商品的数量
							
							$num  = $value['qty'];
							
							// 修改对应的销量
							
							$goods = Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$product_id]);
							
							$volume = $goods['volume']+	$num;
							
							Yii::$app->mongodb->getCollection('product_flat')->update(['_id'=>$product_id],['$set'=>['volume'=>(int)$volume]]);
		
							
						 
						}
					
					}
					
					
					$sql = "SELECT * FROM sales_flat_order where order_id  in($order_id) ";
					$data = Yii::$app->db->createCommand($sql)->queryAll();
					
					$customer_id=$data[0]['customer_id'];
					// 查询用户相关信息
					
					$user = Yii::$app->db->createCommand("SELECT * FROM customer WHERE id = $customer_id")->queryOne();
					
					// 计算总额
					
					$money=0;
					
					foreach($data as $key => $value){
						
						$money +=$value['grand_total'];
					}
					
					
					$coins =  $user['coin']+floor($money/10);
					echo $sql="update customer set coin = $coins  where id = $customer_id";
					Yii::$app->db->createCommand($sql)->execute();
					// 更新用户的金币记录
						
					$arr=[
						"uid"=>$customer_id,
						"time"=>time(),
						"type"=>1,
						"coinNum"=> floor($money/10),							
					];
					Yii::$app->db->createCommand()->insert('coin_record', $arr)->execute();

					
			   }else{  
				   $result = false;  
			   }  
			   // 返回状态给微信服务器  
			   if ($result) {  
				   $str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';  
			   }else{  
				   $str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';  
			   }  
			   echo $str;  
		}
		
		
	
		// 服务支付定金完成
		public function actionPayok3(){
			
		
			
			// 接收返回的xml数据
				$xmlData = file_get_contents('php://input');
			
			  // 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了  
				 
				//将服务器返回的XML数据转化为数组  
				$data = json_decode(json_encode(simplexml_load_string($xmlData,'SimpleXMLElement',LIBXML_NOCDATA)),true); 
				
				
			   // 判断签名是否正确  判断支付状态  
			   if (($data['return_code']=='SUCCESS') && ($data['result_code']=='SUCCESS') ) {  
				   $result = $data;  
				   // 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了  
					 
				   //获取服务器返回的数据  
				   $order_id = $data['out_trade_no'];  //订单单号  
				   $id = $data['attach'];        //附加参数,选择传递订单ID   
				   $openid = $data['openid'];          //付款人openID  
				   $transaction_id = $data['transaction_id'];    //付款金额  
				   $pay_time=time();
				   //更新数据库  
					
				   $time=time();
				   // pay_type=2 是微信支付
				   // is_pay = 1 是否支付
				   $sql = "update sales_flat_order set order_status = 1,is_pay=1,pay_time = $time,pay_type=2 where order_id  = $order_id ";
			
					 Yii::$app->db->createCommand($sql)->execute();
	
				   // 当前销量
					 
					 // 查询所有的子订单修改对应商品的销量
					 // 1、查看所有的子订单
					 
					$dataItem = Yii::$app->db->createCommand("SELECT * FROM sales_flat_order_item where order_id = $order_id")->queryAll();
					 
					// 修改每一个商品的销量
					
					foreach($dataItem as $key=>$value){
						
						// 获取商品的id
						
						$product_id = $value['product_id'];
						
						// 获取商品的数量
						
						$num  = $value['qty'];
						
						// 修改对应的销量
						
						$goods = Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$product_id]);
						
						$volume = $goods['volume']+	$num;
						
						Yii::$app->mongodb->getCollection('product_flat')->update(['_id'=>$product_id],['$set'=>['volume'=>(int)$volume]]);
	
						
					 
					}
					
					// 修改用户的金币
					
					
					// 获取订单详情
					// 通过订单获取用户ID
					$sql = "SELECT * FROM sales_flat_order where order_id  = $order_id ";
					$data = Yii::$app->db->createCommand($sql)->queryOne();
					
					
					// 查询用户相关信息
					
					$user = Yii::$app->db->createCommand("SELECT * FROM customer WHERE id = $data[customer_id]")->queryOne();
					
					
					$coins =  $user['coin']+floor($data['grand_total']/10);
					echo $sql="update customer set coin = $coins  where id = $data[customer_id]";
					Yii::$app->db->createCommand($sql)->execute();
					// 更新用户的金币记录
						
					$arr=[
						"uid"=>$data['customer_id'],
						"time"=>time(),
						"type"=>1,
						"coinNum"=> floor($data['grand_total']/10),							
					];
					Yii::$app->db->createCommand()->insert('coin_record', $arr)->execute();
					 
					 
				   // 未修改商品销量
			   }else{  
				   $result = false;  
			   }  
			   // 返回状态给微信服务器  
			   if ($result) {  
				   $str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';  
			   }else{  
				   $str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';  
			   }  
			   echo $str;  
		}
		
		// 支付尾款
		
	// 服务支付定金完成
	public function actionPayok4(){



	// 接收返回的xml数据
		$xmlData = file_get_contents('php://input');

	// 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了  
		
		//将服务器返回的XML数据转化为数组  
		$data = json_decode(json_encode(simplexml_load_string($xmlData,'SimpleXMLElement',LIBXML_NOCDATA)),true); 
		
		
	// 判断签名是否正确  判断支付状态  
	if (($data['return_code']=='SUCCESS') && ($data['result_code']=='SUCCESS') ) {  
		$result = $data;  
		// 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了  
			
		//获取服务器返回的数据  
		$order_id = $data['attach'];  //订单单号  
		// $id = $data['attach'];        //附加参数,选择传递订单ID   
		$openid = $data['openid'];          //付款人openID  
		$transaction_id = $data['transaction_id'];    //付款金额  
		$pay_time=time();
		//更新数据库  
			
		$time=time();
		// pay_type=2 是微信支付
		// is_pay = 1 是否支付
		$sql = "update sales_flat_order set order_status = 4,is_pay=1,pay_time = $time,pay_type=2,is_wei=1 where order_id  = $order_id ";

		Yii::$app->db->createCommand($sql)->execute();

		// 当前销量
			
			
		$dataItem = Yii::$app->db->createCommand("SELECT * FROM sales_flat_order_item where order_id =$order")->queryAll();
					
		// 修改每一个商品的销量
		
		foreach($dataItem as $key=>$value){
			
			
			if($value['good_type']==1){
				
			
			
				// 获取商品的id
				
				$product_id = $value['product_id'];
				
				// 获取商品的数量
				
				$num  = $value['qty'];
				
				// 修改对应的销量
				
				$goods = Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$product_id]);
				
				$volume = $goods['volume']+	$num;
				
				Yii::$app->mongodb->getCollection('product_flat')->update(['_id'=>$product_id],['$set'=>['volume'=>(int)$volume]]);
		
			}
		
		}
			
		// 修改用户的金币
		
		
		// 获取订单详情
		// 通过订单获取用户ID
		$sql = "SELECT * FROM sales_flat_order where order_id  = $order_id ";
		$data = Yii::$app->db->createCommand($sql)->queryOne();
		
		
		// 查询用户相关信息
		
		$user = Yii::$app->db->createCommand("SELECT * FROM customer WHERE id = $data[customer_id]")->queryOne();
		
		
		$coins =  $user['coin']+floor($data['grand_total']-$data['fuwu_money']/10);
		echo $sql="update customer set coin = $coins  where id = $data[customer_id]";
		Yii::$app->db->createCommand($sql)->execute();
		// 更新用户的金币记录
			
		$arr=[
			"uid"=>$data['customer_id'],
			"time"=>time(),
			"type"=>1,
			"coinNum"=> floor($data['grand_total']-$data['fuwu_money']/10),							
		];
		Yii::$app->db->createCommand()->insert('coin_record', $arr)->execute();
		
			
		// 未修改商品销量
	}else{  
		$result = false;  
	}  
	// 返回状态给微信服务器  
	if ($result) {  
		$str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';  
	}else{  
		$str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';  
	}  
	echo $str;  
	}
	
	
	
			// 立即下单生成订单
	public function actionCreateordermoney(){
	

		 //获取数据
		 $req = Yii::$app->request;
		 // 判断数据是个请求正确
		 if(!empty($req->post())){

				 // 接受数据

				 $customer_id=$req->post("customer_id");
				 $money = $req->post("price");
				 $pay_money = $req->post("payment");

				 // 格式化数据

					$brr=[
							"code"							=>time().rand(),
							"create_time"       =>time(),
							"uid"								=>$customer_id,
							"order_status"		  =>0,	
							"money"		  				=>$money,	
							"pay_money"		  		=>$pay_money,	
							"pay_time"					=>0,

					];

				 // 插入数据

				 // 执行插入数据
				 $res = Yii::$app->db->createCommand()->insert('recharge_order', $brr)->execute();
				 $order_id = Yii::$app->db->getLastInsertID();

				 if ($res) {
					 $msgSuc['status']=1;
					 $msgSuc['info']="生成订单成功";
					 $msgSuc['order_id']=$order_id;
				 }else{
						 $msgSuc['status']=0;
						 $msgSuc['info']="生成订单失败";
				 }
		 }else{
				 $msgSuc['status']=0;
				 $msgSuc['info']="非法数据请求";
		 }
		 return $msgSuc;
	}
	
	
		// 服务支付定金完成
		public function actionPayok5(){
	
	
	
		// 接收返回的xml数据
			$xmlData = file_get_contents('php://input');
	
		// 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了  
			
			//将服务器返回的XML数据转化为数组  
			$data = json_decode(json_encode(simplexml_load_string($xmlData,'SimpleXMLElement',LIBXML_NOCDATA)),true); 
				
				
			// 判断签名是否正确  判断支付状态  
			if (($data['return_code']=='SUCCESS') && ($data['result_code']=='SUCCESS') ) {  
				$result = $data;  
				// 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了  
					
				//获取服务器返回的数据  
				$order_id = $data['attach'];  //订单单号  
				
				// $id = $data['attach'];        //附加参数,选择传递订单ID   
				// $openid = $data['openid'];          //付款人openID  
				// $transaction_id = $data['transaction_id'];    //付款金额  
				$pay_time=time();
				//更新数据库  
					
				// pay_type=2 是微信支付
				// is_pay = 1 是否支付
				$sql = "update recharge_order set order_status = 2,pay_time = $pay_time where id  = $order_id ";
		
				Yii::$app->db->createCommand($sql)->execute();
		
				
					
				// 修改用户的金币
				
				
				// 获取订单详情
				// 通过订单获取用户ID
				$sql = "SELECT * FROM recharge_order where id  = $order_id ";
				$data = Yii::$app->db->createCommand($sql)->queryOne();
				
				
				// 查询用户相关信息
				
				$user = Yii::$app->db->createCommand("SELECT * FROM customer WHERE id = $data[uid]")->queryOne();
				
				
				$money = $user['money']+$data['money'];
				$sql="update customer set money = $money  where id = $data[uid]";
				Yii::$app->db->createCommand($sql)->execute();
				// 更新用户的金币记录
					
				$arr=[
					"uid"=>$data['uid'],
					"time"=>time(),
					"type"=>2,
					"moneyNum"=> 			$data['money']				
				];
				Yii::$app->db->createCommand()->insert('money_record', $arr)->execute();
				
					
				// 未修改商品销量
			}else{  
				$result = false;  
			}  
			// 返回状态给微信服务器  
			if ($result) {  
				$str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';  
			}else{  
				$str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';  
			}  
			echo $str;  
		}
		
		
		public function actionGetuserinfo(){
			$req = Yii::$app->request;
			
			// 获取用户ID
			$id = $req->get("customer_id");
			// 查询用户的数据
			
			
			$sql = "SELECT * FROM customer where id  = $id ";
			$data = Yii::$app->db->createCommand($sql)->queryOne();
			
			
			return $data;
			
		}
		
		
		public function actionCreateview(){
			
			$req = Yii::$app->request;
			
			// 获取用户ID
			$user_id = $req->get("customer_id");
			$name = $req->get("name");
			$review_content = $req->get("review_content");
			$product_id = $req->get("product_id");
			$rate_star = $req->get("selectStar");
			
			$goods=Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$product_id]);

			$arr=[];
			$arr=[
				"product_id"=>$product_id,
				"rate_star"=>$rate_star,
				"user_id"=>"$user_id",
				"product_spu"=>$goods['spu'],
				"shop_id"=>$goods['shop_id'],
				"review_date"=>time(),
				"review_content"=>$review_content,
				"name"=>$name,

			];



			$collection = Yii::$app->mongodb->getCollection('review');

			if($collection->insert($arr)){

	
				$brr=[
					"code"=>1,
					"shop_id"=>$goods['shop_id']
					
				];

			}else{
				$brr=[
					"code"=>0,
					
				];

			}
			
			return $brr;
		}
		

}