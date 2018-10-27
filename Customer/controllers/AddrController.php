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
		// 获城市
		public function actionGetcitys(){
			// 获取城市
			$posts = Yii::$app->db->createCommand('SELECT * FROM sys_city where province_id = 4')->queryAll();
			
			// 获取县
			
			foreach($posts as &$value){
				// 获取县
				$value['zi'] = Yii::$app->db->createCommand("SELECT * FROM sys_district where city_id = $value[city_id]")->queryAll();
			}
			return $posts;
		}
		
		// csrf 
    public function actionGetcsrf(){
        return  Yii::$app->request->csrfToken;
    }
		
		// 添加地址 
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
						$state="山西省";
            $city=$req->post('city');
            $street1=$req->post('street1');
						$street2=$req->post('street2');



            $arr=[
                "first_name"=>$first_name,
                "telephone"=>$telephone,
                "email"=>$email,
                "customer_id"=>$customer_id,
                "is_default"=>$is_default=="true"?'1':'0',
								"state"=>$state,
								"city"=>$city,
								"street1"=>$street1,
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
		// 获取默认地址
		
		public function actionGetdefaultaddr(){
			if(Yii::$app->request->getMethod() === 'OPTIONS'){
					return [];
			}
			
			//获取数据
			$req = Yii::$app->request;

			
			
			if($req->get("id")){
				$id = $req->get("id");
				
				// 查询对应数据
				
				$sql="select * from customer_address where customer_id = '$id' and  is_default = 1";
				// 获取数据
				
				$res=Yii::$app->db->createCommand($sql)->queryOne();
				
				$data = [
					"code" => 200,
					"info" => "获取成功",
					"data" => $res
				];
			}else{
				$data = [
					"code" => 400,
					"info" => "非法请求"
				];
			}


			 return $data;
		}
		// 查找收获地址 

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
		// 修改数据
		
		public function actionAddrsave(){
			if(Yii::$app->request->getMethod() === 'OPTIONS'){
					return [];
			}
			
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
				
					$state="山西省";
					$city=$req->post('city');
					$street1=$req->post('street1');
					$street2=$req->post('street2');
					$id = $req->post("address_id");
					$time = time();

					// 判断设置默认地址
					if ($is_default=="true") {
							
							Yii::$app->db->createCommand("update customer_address set is_default=0 where customer_id=$customer_id")->execute();
							
					}
					$is_default=$is_default=="true"?'1':'0';

					// 修改数据
					$res = Yii::$app->db->createCommand("update customer_address set updated_at='$time',first_name = '$first_name',telephone='$telephone',email='$email',customer_id='$customer_id',is_default='$is_default',state='$state',city='$city',street1='$street1',street2='$street2' where address_id = $id")->execute();


					// 判断数据是否插入成功

					if ($res) {

							// 添加成功
							$data['status']=200;
							$data['info']="修改成功";
					
					}else{

							// 添加失败
							$data['status']=400;
							$data['info']="修改失败";
					}
			}else{

					$data['status']=400;
					$data['info']="请求不合法";
			}

			return $data;
		}
		
		// 修改默认地址
		 
		public function actionAddrsavedefault(){
			// 判断数据请求
			if($_GET['id']){
				// 获取数据
				$id = $_GET['id'];
				$is_default = $_GET['is_default'];
				$customer_id = $_GET['cid'];
				$time = time();
				
				if($is_default == 0){
					// 修改数据 
					$res = Yii::$app->db->createCommand("update customer_address set updated_at='$time',is_default='$is_default' where address_id = $id")->execute();
				}else{
					Yii::$app->db->createCommand("update customer_address set is_default=0 where customer_id=$customer_id")->execute();
					$res = Yii::$app->db->createCommand("update customer_address set updated_at='$time',is_default='$is_default' where address_id = $id")->execute();
				}
				
				if($res){
					// 数据请求失败
					$data['status']=200;
					$data['info']="修改成功";
				}else{
					// 数据请求失败
					$data['status']=400;
					$data['info']="修改失败";
				}
				
			}else{
				// 数据请求失败
				$data['status']=400;
				$data['info']="请求不合法";
			}
			
			return $data;
			
		}
		// 删除收获地址
		
		public function actionAddrdel(){
			if(Yii::$app->request->getMethod() === 'OPTIONS'){
					return [];
			}
			
			// 判断是否请求数据
			 
			if($_GET['id']){
				
				// 进行数据删除
				$sql ="delete from customer_address where address_id = $_GET[id] && customer_id = $_GET[cid]";
				
				// 判断是否删除成功
				$res = Yii::$app->db->createCommand($sql)->execute();
				if($res){
					$data = [
						"code" => 200,
						"info" => "删除成功"
					];
				}else{
					$data = [
						"code" => 400,
						"info" => "删除失败"
					];
				}
			}else{
				$data = [
					"code" => 400,
					"info" => "数据请求不合法",
				];
			}
			
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