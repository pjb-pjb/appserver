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
                $dataDataZi = Yii::$app->db->createCommand("select * from sales_flat_cart_item where cart_id='$carData[cart_id]' order by updated_at desc")->queryAll();


               // 查询购物车子订单对应商品和订单数据

               // 获取数据
               $query = new Query;

               foreach ($dataDataZi as $key => &$value) {
                   
                   // 查询shop表基本信息 
                   $shop = Yii::$app->db->createCommand("select * from shop where shop_id=$value[shop_id]")->queryOne();
                   $value['shop_name']=$shop['shop_name'];

                   // 查询商品信息

                   $product = Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$value['product_id']]);

                   // 改变
                   $value['product'] = $product;


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


   // 生成地址

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
           $goods_type=1;

           // 查看产品信息
           $product=Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$product_id]);
               
           // 查看地址信息
           $address=Yii::$app->db->createCommand("select * from customer_address where address_id = '$address_id'")->queryOne();


           // 格式化数据

            $brr=[
                "order_status"      =>0,
                "shop_id"           =>$shop_id,
                "created_at"        =>time(),
                "customer_id"       =>$customer_id,
                "goods_type"        =>$goods_type,
                "increment_id"      =>date("YmdHis").rand().$customer_id,
                "subtotal"          =>$product['special_price']*1,
                "items_count"       =>1,
                "customer_telephone"       =>$address['telephone'],
                "customer_address_state"   =>$address['state'],
                "customer_address_city"    =>$address['city'],
                "customer_address_street1" =>$address['street2'],
                "customer_firstname"       =>$address['first_name'],
                "customer_email"           =>$address['email'],

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
               $msgSuc['status']=1;
               $msgSuc['info']="生成订单成功";
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
    
}