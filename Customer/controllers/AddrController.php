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

/**
 * @author Terry Zhao <2358269014@qq.com>
 * @since 1.0
 */
class AddrController extends AppserverController
{
    public $enableCsrfValidation = false ;
    /**
     * 添加地址
     */

    public function actionGetcsrf(){
        return  Yii::$app->request->csrfToken;
    }
    public function actionAddaddr(){
        if(Yii::$app->request->getMethod() === 'OPTIONS'){
            return [];
        }

        // 查看请求是否合法

        //获取数据
        $req = Yii::$app->request;
        // 判断数据是个请求正确
        if(!empty($req->post())){

            // 添加地址
            // 姓名、电话、邮箱、地址、默认

            $first_name=$req->post('first_name');
            $telephone=$req->post('telephone');
            $email=$req->post('email');
            $customer_id=$req->post('customer_id');
            $is_default=$req->post('is_default');
            $street2=$req->post('street2');


            $arr=[
                "first_name"=>$first_name,
                "telephone"=>$telephone,
                "email"=>$email,
                "customer_id"=>$customer_id,
                "is_default"=>$is_default=="true"?'1':'0',
                "street2"=>$street2,
                "created_at"=>time(),
            ];

            // 判断设置默认地址
            if ($is_default=="true") {
                
                Yii::$app->db->createCommand("update customer_address set is_default=0 where customer_id=$customer_id")->execute();
                
            }

            // 插入数据

            $res=Yii::$app->db->createCommand()->insert('customer_address', $arr)->execute();


            // 判断数据是否插入成功

            if ($res) {

                // 添加成功
                $data['status']=1;
                $data['info']="添加成功";
            
            }else{

                // 添加失败
                $data['status']=0;
                $data['info']="添加失败";
            }
        }else{

            $data['status']=0;
            $data['info']="请求不合法";
        }

        return $data;
    
        
    }


    public function actionAddrfind(){
        if(Yii::$app->request->getMethod() === 'OPTIONS'){
            return [];
        }

        // 查看请求是否合法
        //获取数据
        $req = Yii::$app->request;

        $id = $req->get("id");

        // 获取数据

        $data=Yii::$app->db->createCommand("select * from customer_address where address_id = '$id'")->queryOne();

        return $data;

    }
    // 查看地址

    public function actionAddrlist(){

        if(Yii::$app->request->getMethod() === 'OPTIONS'){
            return [];
        }

        // 查看请求是否合法
        //获取数据
        $req = Yii::$app->request;
        // 判断数据是个请求正确
        if(!empty($req->get())){

            // 查看数据的id
            $customer_id=$req->get('customer_id');

            // 查看所有地址数据

            $addrList=Yii::$app->db->createCommand("select * from customer_address where customer_id = '$customer_id'")->queryAll();

            // 插入数据

            // 判断数据是否插入成功

            // 查看所有订单数据
            $data['status']=1;
            $data['info']=$addrList;
           
        }else{

            $data['status']=0;
            $data['info']="请求不合法";
        }

        return $data;
        
    }
    
    
}