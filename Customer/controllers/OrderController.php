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
class OrderController extends AppserverController
{
    public $enableCsrfValidation = false ;
    protected $numPerPage = 10;
    protected $pageNum;
    protected $orderBy;
    protected $customer_id;
    protected $_page = 'p';
	
	public function actionIndex(){
		if(Yii::$app->request->getMethod() === 'OPTIONS'){
			return [];
		}
		
		// 判断请求是否合法
		 
		if($_GET['uid']){
			
			// 分页数据
			$page = isset($_GET['page'])?$_GET['page']:1;
			$start = ($page)*10;
			// 请求类型
			$status = $_GET['type']?$_GET['type']:0;
			
			// 查看所有数据 
			if($status == -1){
				$sql="select shop.shop_logo,orders.increment_id,orders.order_status,subtotal,customer.firstname,orders.order_id from sales_flat_order orders,customer,shop where shop.shop_id = orders.shop_id and orders.customer_id = customer.id and orders.customer_id = $_GET[uid] order by orders.order_id desc limit $start,10";
				
			}else{
			// 查看对应数据
				$sql="select shop.shop_logo,orders.increment_id,orders.order_status,orders.subtotal,customer.firstname,orders.order_id from sales_flat_order orders,customer,shop where shop.shop_id = orders.shop_id and orders.customer_id = customer.id and orders.customer_id = $_GET[uid] and orders.order_status = $status order by orders.order_id desc limit $start,10";
				
			}
			
			// $sql="select orders.*,items.product_id,items.name,items.image from sales_flat_order orders ,sales_flat_order_item items where orders.order_id = items.order_id and orders.customer_id = $_GET['uid'] order by orders.order_id desc";
			

			// 格式化数据 
			
			$res= Yii::$app->db->createCommand($sql)->queryAll();
			$data=[
				"code"=>200,
				"info"=>"数据请求成功",
				"data"=>$res
			];
		}else{
			// 格式化数据
			$data=[
				'code'=>400,
				"info"=>"数据请求不合法",
			];
		}
		
		return $data;
	}
    
	// 查看订单的详细信息
	public function actionOrderlist(){
		if(Yii::$app->request->getMethod() === 'OPTIONS'){
			return [];
		}
				
		// 判断请求是否合法
		 
		if($_GET['order_id']){
			
			// 查看订单
			$sql="select orders.* from sales_flat_order orders where orders.order_id = $_GET[order_id]";
			// 格式化数据 
			$res= Yii::$app->db->createCommand($sql)->queryOne();
			// 数据格式化
			$res['created_at'] = $res['created_at']?date("Y-m-d H:i:s",$res['created_at']):0;
			$res['updated_at'] = $res['updated_at']?date("Y-m-d H:i:s",$res['updated_at']):0;
			$res['receipt_at'] = $res['receipt_at']?date("Y-m-d H:i:s",$res['receipt_at']):0;
			$res['confirm_at'] = $res['confirm_at']?date("Y-m-d H:i:s",$res['confirm_at']):0;
			$res['evaluate_at'] = $res['evaluate_at']?date("Y-m-d H:i:s",$res['evaluate_at']):0;
			$res['paypal_order_datetime'] = $res['paypal_order_datetime']?date("Y-m-d H:i:s",$res['paypal_order_datetime']):0;			
			// 查看订单
			$sql="select orders.* from sales_flat_order_item orders where orders.order_id = $_GET[order_id]";
			// 格式化数据 
			$data= Yii::$app->db->createCommand($sql)->queryAll();
			// 查看订单下商品 
			$data=[
				"code"=>200,
				"info"=>"数据请求成功",
				"order"=> $res,
				"list" =>$data
			];
		}else{
			// 格式化数据
			$data=[
				'code' => 400,
				"info" => "数据请求不合法",
			];
		}
		
		return $data;
	}
    public function actionIndex1()
    {
        if(Yii::$app->request->getMethod() === 'OPTIONS'){
            return [];
        }
        $identity = Yii::$app->user->identity;
        $this->customer_id = $identity['id'];
        $this->pageNum = (int) Yii::$app->request->get('p');
        $this->pageNum = ($this->pageNum >= 1) ? $this->pageNum : 1;
        $this->orderBy = ['created_at' => SORT_DESC];
        $return_arr = [];
        if ($this->customer_id) {
            $filter = [
                'numPerPage'    => $this->numPerPage,
                'pageNum'        => $this->pageNum,
                'orderBy'        => $this->orderBy,
                'where'            => [
                    ['customer_id' => $this->customer_id],
                ],
                'asArray' => true,
            ];

            $customer_order_list = Yii::$service->order->coll($filter);
            $order_list = $customer_order_list['coll'];
            $count = $customer_order_list['count'];
            $orderArr = [];
            if(is_array($order_list)){
                foreach($order_list as $k=>$order){
                    $currencyCode = $order['order_currency_code'];
                    $order['currency_symbol'] = Yii::$service->page->currency->getSymbol($currencyCode);
                    $orderArr[] = $this->getOrderArr($order);
                }
            }
            $code = Yii::$service->helper->appserver->status_success;
            $data = [
                'orderList'     => $orderArr,
                'count'         => $count,
            ];
            $reponseData = Yii::$service->helper->appserver->getReponseData($code, $data);
            
            return $reponseData;
        }
    }
    
    public function getOrderArr($order){
        $orderInfo = [];
        $orderInfo['created_at'] = date('Y-m-d H:i:s',$order['created_at']);
        $orderInfo['updated_at'] = date('Y-m-d H:i:s',$order['updated_at']);
        $orderInfo['increment_id'] = $order['increment_id'];
        $orderInfo['order_id'] = $order['order_id'];
        $orderInfo['order_status'] = $order['order_status'];
        $orderInfo['items_count'] = $order['items_count'];
        $orderInfo['total_weight'] = $order['total_weight'];
        $orderInfo['order_currency_code'] = $order['order_currency_code'];
        $orderInfo['order_to_base_rate'] = $order['order_to_base_rate'];
        $orderInfo['grand_total'] = $order['grand_total'];
        $orderInfo['base_grand_total'] = $order['base_grand_total'];
        $orderInfo['subtotal'] = $order['subtotal'];
        $orderInfo['base_subtotal'] = $order['base_subtotal'];
        $orderInfo['subtotal_with_discount'] = $order['subtotal_with_discount'];
        $orderInfo['base_subtotal_with_discount'] = $order['base_subtotal_with_discount'];
        $orderInfo['checkout_method'] = $order['checkout_method'];
        $orderInfo['customer_id'] = $order['customer_id'];
        $orderInfo['customer_group'] = $order['customer_group'];
        $orderInfo['customer_email'] = $order['customer_email'];
        $orderInfo['customer_firstname'] = $order['customer_firstname'];
        $orderInfo['customer_lastname'] = $order['customer_lastname'];
        $orderInfo['customer_is_guest'] = $order['customer_is_guest'];
        $orderInfo['coupon_code'] = $order['coupon_code'];
        $orderInfo['payment_method'] = $order['payment_method'];
        $orderInfo['shipping_method'] = $order['shipping_method'];
        $orderInfo['tracking_number'] = $order['tracking_number'];
        
        $orderInfo['shipping_total'] = $order['shipping_total'];
        $orderInfo['base_shipping_total'] = $order['base_shipping_total'];
        $orderInfo['customer_telephone'] = $order['customer_telephone'];
        $orderInfo['customer_address_country'] = $order['customer_address_country'];
        $orderInfo['customer_address_state'] = $order['customer_address_state'];
        $orderInfo['customer_address_city'] = $order['customer_address_city'];
        $orderInfo['customer_address_zip'] = $order['customer_address_zip'];
        $orderInfo['customer_address_street1'] = $order['customer_address_street1'];
        $orderInfo['customer_address_street2'] = $order['customer_address_street2'];
        $orderInfo['customer_address_state_name'] = $order['customer_address_state_name'];
        $orderInfo['customer_address_country_name'] = $order['customer_address_country_name'];
        $orderInfo['currency_symbol'] = $order['currency_symbol'];
        $orderInfo['products'] = $order['products'];
        
        
        
        return $orderInfo; 
    }
    
    
    public function actionView(){
        if(Yii::$app->request->getMethod() === 'OPTIONS'){
            return [];
        }
        $order_id = Yii::$app->request->get('order_id');
        if ($order_id) {
            $order_info = Yii::$service->order->getOrderInfoById($order_id);
            if (isset($order_info['customer_id']) && !empty($order_info['customer_id'])) {
                $identity = Yii::$app->user->identity;
                $customer_id = $identity->id;
                if ($order_info['customer_id'] == $customer_id) {
                    $order_info = $this->getOrderArr($order_info);
                    $productArr = [];
                    //var_dump($order_info);exit;
                    if(is_array($order_info['products'])){
                        foreach($order_info['products'] as $product){
                            $productArr[] = [
                                'imgUrl' => Yii::$service->product->image->getResize($product['image'],[100,100],false),
                                'name' => $product['name'],
                                'sku' => $product['sku'],
                                'qty' => $product['qty'],
                                'row_total' => $product['row_total'],
                                'product_id' => $product['product_id'],
                                'custom_option_info' => $product['custom_option_info'],
                            ];
                            
                        }
                    }
                    $order_info['products'] = $productArr;
                    $code = Yii::$service->helper->appserver->status_success;
                    $data = [
                        'order'=> $order_info,
                    ];
                    $reponseData = Yii::$service->helper->appserver->getReponseData($code, $data);
                    
                    return $reponseData;
                    
                }
            }
        }
        
        
    }
    
    public function actionReorder()
    {
        $order_id = Yii::$app->request->get('order_id');
        $errorArr = [];
        if (!$order_id) {
            $errorArr[] = 'The order id is empty';
        }
        $order = Yii::$service->order->getByPrimaryKey($order_id);
        if (!$order['increment_id']) {
            $errorArr[] = 'The order is not exist';
        }
        $customer_id = Yii::$app->user->identity->id;
        if (!$order['customer_id'] || ($order['customer_id'] != $customer_id)) {
            $errorArr[] = 'The order does not belong to you';
        }
        if(!empty($errorArr)){
            $code = Yii::$service->helper->appserver->account_reorder_order_id_invalid;
            $data = [];
            $reponseData = Yii::$service->helper->appserver->getReponseData($code, $data);
            
            return $reponseData;
        }
        $this->addOrderProductToCart($order_id);

        $code = Yii::$service->helper->appserver->status_success;
        $data = [];
        $reponseData = Yii::$service->helper->appserver->getReponseData($code, $data);
        
        return $reponseData;
    }
    
    
    public function addOrderProductToCart($order_id)
    {
        $items = Yii::$service->order->item->getByOrderId($order_id);
        //var_dump($items);
        if (is_array($items) && !empty($items)) {
            foreach ($items as $one) {
                $item = [
                    'product_id'        => $one['product_id'],
                    'custom_option_sku' => $one['custom_option_sku'],
                    'qty'                => (int) $one['qty'],
                ];
                //var_dump($item);exit;
                Yii::$service->cart->addProductToCart($item);
            }
        }
    }



}