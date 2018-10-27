<?php
/**
 * FecShop file.
 *
 * @link http://www.fecshop.com/
 * @copyright Copyright (c) 2016 FecShop Software LLC
 * @license http://www.fecshop.com/license/
 */

namespace fecshop\app\appserver\modules\Store\controllers;

use fecshop\app\appserver\modules\AppserverController;
use Yii;
use yii\mongodb\Query;
 
/**
 * @author Terry Zhao <2358269014@qq.com>
 * @since 1.0
 */
class StoreController extends AppserverController
{
    
    // 当前分类对象
    protected $_category;
    // 页面标题
    protected $_title;
    // 当前分类主键对应的值
    protected $_primaryVal;
    // 默认的排序字段
    protected $_defautOrder;
    // 默认的排序方向，升序还是降序
    protected $_defautOrderDirection = SORT_DESC;
    // 当前的where条件
    protected $_where;
    // url的参数，每页产品个数
    protected $_numPerPage = 'numPerPage';
    // url的参数，排序方向
    protected $_direction = 'dir';
    // url的参数，排序字段
    protected $_sort = 'sortColumn';
    // url的参数，页数
    protected $_page = 'p';
    // url的参数，价格
    protected $_filterPrice = 'price';
    // url的参数，价格
    protected $_filterPriceAttr = 'price';
    // 产品总数
    protected $_productCount;
    protected $_filter_attr;
    protected $_numPerPageVal;
    protected $_page_count;
    protected $category_name;
    protected $sp = '---';
    
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        //$primaryKey = Yii::$service->category->getPrimaryKey();
        $category_id = Yii::$app->request->get('categoryId');
        $cacheName = 'category';
        if (Yii::$service->cache->isEnable($cacheName)) {
            $timeout = Yii::$service->cache->timeout($cacheName);
            $disableUrlParam = Yii::$service->cache->timeout($cacheName);
            $cacheUrlParam = Yii::$service->cache->cacheUrlParam($cacheName);
            $get_str = '';
            $get = Yii::$app->request->get();
            // 存在无缓存参数，则关闭缓存
            if (isset($get[$disableUrlParam])) {
                $behaviors[] =  [
                    'enabled' => false,
                    'class' => 'yii\filters\PageCache',
                    'only' => ['index'],
                ];
            }
            if (is_array($get) && !empty($get) && is_array($cacheUrlParam)) {
                foreach ($get as $k=>$v) {
                    if (in_array($k, $cacheUrlParam)) {
                        if ($k != 'p' || $v != 1) {
                            $get_str .= $k.'_'.$v.'_';
                        }
                    }
                }
            }
            $store = Yii::$service->store->currentStore;
            $currency = Yii::$service->page->currency->getCurrentCurrency();
            $behaviors[] =  [
                'enabled' => true,
                'class' => 'yii\filters\PageCache',
                'only' => ['index'],
                'duration' => $timeout,
                'variations' => [
                    $store, $currency, $get_str, $category_id,
                ],
                //'dependency' => [
                //	'class' => 'yii\caching\DbDependency',
                //	'sql' => 'SELECT COUNT(*) FROM post',
                //],
            ];
        }

        return $behaviors;
    }
    // 获取店铺信息详情
		 
		public function actionGetshopinfo(){
			if(Yii::$app->request->getMethod() === 'OPTIONS'){
					return [];
			}
			
			// 获取店铺信息
			$shop = Yii::$app->db->createCommand("select * from shop where shop_id=$_GET[id] ")->queryOne();
			
		

			// 获取城市信息
			$data['province'] = Yii::$app->db->createCommand("select * from sys_province where sys_province.province_id='$shop[province_id]' ")->queryOne();
			$data['city'] = Yii::$app->db->createCommand("select * from sys_city where sys_city.city_id=$shop[city_id] ")->queryOne();

			$data['district'] = Yii::$app->db->createCommand("select * from sys_district where sys_district.district_id=$shop[district_id] ")->queryOne();
			
			$data['shop'] = $shop;
			return $data;

		}
		
		// 店铺管理首页 
    public function actionIndex(){
        
        if(Yii::$app->request->getMethod() === 'OPTIONS'){
            return [];
        }
        // 每页显示的产品个数，进行安全验证，如果个数不在预先设置的值内，则会报错。
        // 这样是为了防止恶意攻击，也就是发送很多不同的页面个数的链接，绕开缓存。

        // 获取数据
        $query = new Query;
        
        
        // 获取店铺数据
        $reponseData['store'] = Yii::$app->db->createCommand("select * from shop where shop_id=$_GET[id]")->queryOne();

        // 获取店铺商品评论
        // 需要后期修改
        // 结合商家shop_id 进行处理 未添加分也功能呢
        
        $reponseData['review_count']['zong']=$query->from('review')->where(['shop_id'=>$_GET['id']])->count();
        $reponseData['review_count']['hao']=$query->from('review')->where(['shop_id'=>$_GET['id'],'rate_star'=>['$gte'=>'4']])->count();
        $reponseData['review_count']['cha']=$query->from('review')->where(['shop_id'=>$_GET['id'],'rate_star'=>'1'])->count();
        $reponseData['review_count']['zhong']=$query->from('review')->where(['shop_id'=>$_GET['id'],'rate_star'=>['$in'=>['3','2']]])->count();
				
				// 计算商家接单量、成交量、好评率
				// 总订单量
				$tot = Yii::$app->db->createCommand("select count(*) tot from sales_flat_order orders,sales_flat_order_item items where orders.shop_id=$_GET[id] and orders.order_id = items.order_id")->queryOne();
				
				// 成交量
				$tots = Yii::$app->db->createCommand("select count(*) tot from sales_flat_order orders,sales_flat_order_item items where orders.shop_id=$_GET[id] and orders.order_id = items.order_id and orders.order_status>=1")->queryOne();


				// 好评率
				$where= [
					'shop_id'=>$_GET['id']
				];
				$hao=$query->from('review')->where($where)->count();
				
				// 查询所有的订单量
				
				// 查询所有的好评订单
				$arr["chengjiao"] = $tot['tot'];
				
				if($tot['tot']){
					$chengjiaolv = $tot['tot']==0?$tots['tot']/$tot['tot']:0;
					$arr['chengjiaolv'] = round(($chengjiaolv)*100);				
					$arr['haopinlv'] = round(($hao['tot']/$tot['tot'])*100)>=100?100:round(($hao['tot']/$tot['tot'])*100);
				}else{
					$chengjiaolv =0;
					$arr['chengjiaolv'] = 0;		
					$arr['haopinlv'] = 0;
				}
				
				
				$reponseData['shop_count']=$arr;
        return $reponseData;            
    }
		
		// 查看商城商品信息
		
		
		public function actionGetgoods(){
			
			if(Yii::$app->request->getMethod() === 'OPTIONS'){
					return [];
			}
			$query = new Query;

			// 判断请求类型
			$req = Yii::$app->request;

			if(!empty($req->get())){
				// 判断条件
				$where['shop_id']=$_GET['id'];

				$page = isset($_GET['page'])?$_GET['page']:0;
				$start = $page*10;
				// 获取店铺全部商品数据
				$res = $query->from('product_flat')->where($where)->offset($start)->limit(10)->all();
				if($res){
					foreach($res as &$value){
						//好评
						$praises = $query->from("review")->where(["rate_star"=>"4","rate_star"=>"5","product_id"=>$value["_id"]])->count();
						//所有
						$all = $query->from("review")->where(["product_id"=>$value["_id"]])->count();
						if($all>0){
							$value["praise"] = floor(($praises/$all)*100); 
						}else{
							$value["praise"] = -1;
						}					
						// 销量
						$value['volume']=$value['volume']?$value['volume']:0;
					}
				}
				$data = [
					'code' => 200,
					'info' => '请求成功',
					'data' => $res,
				];
			}else{
				$data = [
					'code' => 400,
					'info' => '非法数据请求',
				];
			}
			
			return $data;
		}
		
		// 查看商城的促销信息
		
		public function actionGetcuxiao(){
			
			if(Yii::$app->request->getMethod() === 'OPTIONS'){
					return [];
			}
			$req = Yii::$app->request;
      $query = new Query;
			// 判断请求类型
			
			if(!empty($req->get())){
				// 判断条件
				$where['shop_id']="$_GET[id]";
				$page = isset($_GET['page'])?$_GET['page']:0;
				$start = $page*10;
				// 获取店铺全部商品数据
				$res = $query->from('product_flat')->where($where)->offset($start)->limit(10)->all();
				
			
				if($res){
					foreach($res as &$value){
						//好评
						$praises = $query->from("review")->where(["rate_star"=>"4","rate_star"=>"5","product_id"=>$value["_id"]])->count();
						//所有
						$all = $query->from("review")->where(["product_id"=>$value["_id"]])->count();
						if($all>0){
							$value["praise"] = floor(($praises/$all)*100); 
						}else{
							$value["praise"] = -1;
						}					
						// 销量
						$value['volume']=$value['volume']?$value['volume']:0;
					}
				}
				$data = [
					'code' => 200,
					'info' => '请求成功',
					'data' => $res,
				];
			}else{
				$data = [
					'code' => 400,
					'info' => '非法数据请求',
				];
			}
			
			return $data;
		}
		
		// 查看商城评论
    public function actionGetreview(){
        // 获取好评、中评、差评和全部数据  
        // 需要结合商家的ID 未完成
        // 获取数据
        $query = new Query;
				$page = isset($_GET['page'])?$_GET['page']:0;
				$start = $page*10;
				$shop_id = $_GET['id'];
        
        switch ($_GET['review_type']) {
            case 'hao':
                $review=$query->from('review')->where(['shop_id'=> "$shop_id" ,'rate_star'=>['$gte'=>'4']])->offset($start)->limit(10)->all();
                break;
            case 'zhong':
                $review=$query->from('review')->where(['shop_id'=> "$shop_id" ,'rate_star'=>['$in'=>['3','2']]])->offset($start)->limit(10)->all();
                break;
            case 'cha':
                $review=$query->from('review')->where(['shop_id'=> "$shop_id" ,'rate_star'=>'1'])->offset($start)->limit(10)->all();
                break;
            
            default:
                $review=$query->from('review')->where(['shop_id'=> "$shop_id" ])->offset($start)->limit(10)->all();
                break;
        }
				
        // 对评论的数据进行处理
        foreach ($review as $key => &$value) {

            // $value['review_date']=date("Y-m-d H:i:s",$value['review_date']);
            $value['review_date1']=date("Y-m-d H:i:s",$value['review_date']);

            // 查询用户信息

            $value['user']=Yii::$app->db->createCommand("select firstname,headImg from customer where id = $value[user_id]")->queryOne();            			
						$value['goods']=Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$value['product_id']]);
        }
        $reponseData['review'] = $review;

        return $reponseData;

    }
    // 查看商城推荐
		public function actionRecommend(){
			// 判断数据请求类型
				if(Yii::$app->request->getMethod() === 'OPTIONS'){
						return [];
				}
			
			// 查看所有分类数据
			$query = new Query;
			
			// 获取推荐数据
			$data['recommend'] = $query->from('product_flat')->limit(4)->all();
			
			// 顶级分类数据
			$data['category'] = $query->from('category')->orderBy("sort desc")->where(['parent_id'=>'0'])->all();
			
			foreach($data['category'] as &$value){
				
				$value['goods'] = $query->from('product_flat')->where(['category'=>[0=>"$value[_id]"]])->limit(4)->all();
			}
			
			// 查看banner数据
			$data['banner'] = Yii::$app->db->createCommand('SELECT * FROM banner where type=2 order by sort desc')->queryAll();
			

			return $data;
		}
    public function actionProduct()
    {
        if(Yii::$app->request->getMethod() === 'OPTIONS'){
            return [];
        }
        // 每页显示的产品个数，进行安全验证，如果个数不在预先设置的值内，则会报错。
        // 这样是为了防止恶意攻击，也就是发送很多不同的页面个数的链接，绕开缓存。
        $this->getNumPerPage();
        if(!$this->initCategory()){
            $code = Yii::$service->helper->appserver->category_not_exist;
            $data = [];
            $reponseData = Yii::$service->helper->appserver->getReponseData($code, $data);
            
            return $reponseData;
        }
        $productCollInfo = $this->getCategoryProductColl();
        $products = $productCollInfo['coll'];
        $code = Yii::$service->helper->appserver->status_success;
        $data = [
            'products' => $products
        ];
        $reponseData = Yii::$service->helper->appserver->getReponseData($code, $data);
        
        return $reponseData;
        
    }
    /**
     * 得到子分类，如果子分类不存在，则返回同级分类。
     */
    protected function getFilterCategory()
    {
        $arr = [];
        $category_id = $this->_primaryVal;
        $parent_id = $this->_category['parent_id'];
        $filter_category = Yii::$service->category->getFilterCategory($category_id, $parent_id);
        return $this->getAppServerFilterCategory($filter_category);
    }
    
    protected function getAppServerFilterCategory($filter_category){
        if((is_array($filter_category) || is_object($filter_category)) && !empty($filter_category)){
            foreach($filter_category as $category_id => $v){
                $filter_category[$category_id]['name'] = Yii::$service->store->getStoreAttrVal($v['name'],'name');
                if($filter_category[$category_id]['name'] == $this->category_name){
                    $filter_category[$category_id]['current'] = true;
                }else{
                    $filter_category[$category_id]['current'] = false;
                }
                $filter_category[$category_id]['url'] = 'catalog/category/'.$category_id;
                if(isset($v['child'])){
                    $filter_category[$category_id]['child'] = $this->getAppServerFilterCategory($v['child']);
                }
            }
        }
        return $filter_category;
    }
    
    
    /**
     * 得到产品页面的toolbar部分
     * 也就是分类页面的分页工具条部分。
     */
    protected function getProductPageCount()
    {
        $productNumPerPage  = $this->getNumPerPage();
        $productCount       = $this->_productCount;
        $pageNum            = $this->getPageNum();
        return $this->_page_count = ceil($productCount / $productNumPerPage);
    }
    /**
     * 分类页面toolbar部分：
     * 产品排序，产品每页的产品个数等，为这些部分提供数据。
     */
    protected function getQueryItem()
    {
        $category_query  = Yii::$app->controller->module->params['category_query'];
        $numPerPage      = $category_query['numPerPage'];
        $sort            = $category_query['sort'];
        $current_sort    = Yii::$app->request->get($this->_sort);
        $frontNumPerPage = [];
        
        $frontSort = [];
        if (is_array($sort) && !empty($sort)) {
            $attrUrlStr = $this->_sort;
            $dirUrlStr  = $this->_direction;
            foreach ($sort as $np=>$info) {
                $label      = $info['label'];
                $direction  = $info['direction'];
                
                if($current_sort == $np){
                    $selected = true;
                }else{
                    $selected = false;
                }
                $label = Yii::$service->page->translate->__($label);
                $frontSort[] = [
                    'label'     => $label,
                    'value'     => $np,
                    'selected'  => $selected,
                ];
            }
        }
        $data = [
            'frontNumPerPage' => $frontNumPerPage,
            'frontSort'       => $frontSort,
        ];

        return $data;
    }
    /**
     * @return Array
     * 得到当前分类，侧栏用于过滤的属性数组，由三部分计算得出
     * 1.全局默认属性过滤（catalog module 配置文件中配置 category_filter_attr），
     * 2.当前分类属性过滤，也就是分类表的 filter_product_attr_selected 字段
     * 3.当前分类去除的属性过滤，也就是分类表的 filter_product_attr_unselected
     * 最终出来一个当前分类，用于过滤的属性数组。
     */
    protected function getFilterAttr()
    {
        if (!$this->_filter_attr) {
            $filter_default               = Yii::$app->controller->module->params['category_filter_attr'];
            $current_fileter_select       = $this->_category['filter_product_attr_selected'];
            $current_fileter_unselect     = $this->_category['filter_product_attr_unselected'];
            $current_fileter_select_arr   = $this->getFilterArr($current_fileter_select);
            $current_fileter_unselect_arr = $this->getFilterArr($current_fileter_unselect);
            //var_dump($current_fileter_select_arr);
            $filter_attrs                 = array_merge($filter_default, $current_fileter_select_arr);
            $filter_attrs                 = array_diff($filter_attrs, $current_fileter_unselect_arr);
            $filter_attrs                 = array_unique($filter_attrs);
            $this->_filter_attr           = $filter_attrs;
        }

        return $this->_filter_attr;
    }
    /**
     * 得到分类侧栏用于属性过滤的部分数据
     */
    protected function getRefineByInfo()
    {
        $refineInfo     = [];
        $chosenAttrs = Yii::$app->request->get('filterAttrs');
        $chosenAttrArr = json_decode($chosenAttrs,true);
        if(!empty($chosenAttrArr)){
            foreach ($chosenAttrArr as $attr=>$val) {
                $refineInfo[] = [
                    'attr' =>  $attr,
                    'val'  =>  Yii::$service->page->translate->__($val),
                ];
            }
        }
        $currenctPriceFilter = Yii::$app->request->get('filterPrice'); 
        if($currenctPriceFilter){
            $refineInfo[] = [
                'attr' =>  $this->_filterPrice,
                'val'  =>  $currenctPriceFilter,
            ];
        }
        
        if (!empty($refineInfo)) {
            $arr[] = [
                'attr'   => 'clearAll',
                'val'    => Yii::$service->page->translate->__('clear all'),
            ];
            $refineInfo = array_merge($arr, $refineInfo);
        }

        return $refineInfo;
    }
    /**
     * 侧栏除价格外的其他属性过滤部分
     */
    protected function getFilterInfo()
    {
        $filter_info  = [];
        $filter_attrs = $this->getFilterAttr();
        $chosenAttrs = Yii::$app->request->get('filterAttrs');
        $chosenAttrArr = json_decode($chosenAttrs,true);
        foreach ($filter_attrs as $attr) {
            if ($attr != 'price') {
                $label = preg_replace_callback('/([-_]+([a-z]{1}))/i',function($matches){
                    return ' '.strtoupper($matches[2]);
                },$attr);
                $items = Yii::$service->product->getFrontCategoryFilter($attr, $this->_where);
                if(is_array($items) && !empty($items)){
                    foreach($items as $k=>$one){
                        if(isset($chosenAttrArr[$attr]) && $chosenAttrArr[$attr] == $one['_id']){
                            $items[$k]['selected'] = true;
                        } else {
                            $items[$k]['selected'] = false;
                        }
                        if (isset($items[$k]['_id'])) {
                            $items[$k]['label'] = Yii::$service->page->translate->__($items[$k]['_id']);
                        }
                    }
                }
                $label = Yii::$service->page->translate->__($label);
                $filter_info[$attr] = [
                    'label' => $label,
                    'items' => $items,
                ];
            }
        }

        return $filter_info;
    }
    /**
     * 侧栏价格过滤部分
     */
    protected function getFilterPrice()
    {
        $symbol = Yii::$service->page->currency->getCurrentSymbol();
        
        $currenctPriceFilter = Yii::$app->request->get('filterPrice');
        $filter = [];
        $priceInfo = Yii::$app->controller->module->params['category_query'];
        if (isset($priceInfo['price_range']) && !empty($priceInfo['price_range']) && is_array($priceInfo['price_range'])) {
            foreach ($priceInfo['price_range'] as $price_item) {
                list($b_price,$e_price) = explode('-',$price_item);
                $b_price = $b_price ? $symbol.$b_price : '';
                $e_price = $e_price ? $symbol.$e_price : '';
                $label = $b_price.$this->sp.$e_price;
                if($currenctPriceFilter && ($currenctPriceFilter == $price_item)){
                    $selected = true;
                }else{
                    $selected = false;
                }
                $info = [
                    'selected'  => $selected,
                    'label'     => $label,
                    'val'       => $price_item
                ];
                
                $filter[$this->_filterPrice][] = $info;
            }
        }

        return $filter;
    }
    /**
     * 格式化价格格式，侧栏价格过滤部分
     */
    protected function getFormatFilterPrice($price_item)
    {
        list($f_price, $l_price) = explode('-', $price_item);
        $str = '';
        if ($f_price == '0' || $f_price) {
            $f_price = Yii::$service->product->price->formatPrice($f_price);
            $str .= $f_price['symbol'].$f_price['value'].'---';
        }
        if ($l_price) {
            $l_price = Yii::$service->product->price->formatPrice($l_price);
            $str .= $l_price['symbol'].$l_price['value'];
        }

        return $str;
    }
    /**
     * @property $str | String
     * 字符串转换成数组。
     */
    protected function getFilterArr($str)
    {
        $arr = [];
        if ($str) {
            $str = str_replace('，', ',', $str);
            $str_arr = explode(',', $str);
            foreach ($str_arr as $a) {
                $a = trim($a);
                if ($a) {
                    $arr[] = trim($a);
                }
            }
        }

        return $arr;
    }
    /**
     * 用于搜索条件的排序部分
     */
    protected function getOrderBy()
    {
        $primaryKey = Yii::$service->category->getPrimaryKey();
        $sort       = Yii::$app->request->get($this->_sort);
        $direction  = Yii::$app->request->get($this->_direction);

        $category_query_config = Yii::$app->controller->module->params['category_query'];
        if (isset($category_query_config['sort'])) {
            $sortConfig = $category_query_config['sort'];
            if (is_array($sortConfig)) {
                
                //return $category_query_config['numPerPage'][0];
                if ($sort && isset($sortConfig[$sort])) {
                    $orderInfo = $sortConfig[$sort];
                    //var_dump($orderInfo);
                    if (!$direction) {
                        $direction = $orderInfo['direction'];
                    }
                } else {
                    foreach ($sortConfig as $k => $v) {
                        $orderInfo = $v;
                        if (!$direction) {
                            $direction = $v['direction'];
                        }
                        break;
                    }
                }
                
                $db_columns = $orderInfo['db_columns'];
                if ($direction == 'desc') {
                    $direction = -1;
                } else {
                    $direction = 1;
                }
                //var_dump([$db_columns => $direction]);
                //exit;
                return [$db_columns => $direction];
            }
        }
    }
    /**
     * 分类页面的产品，每页显示的产品个数。
     * 对于前端传递的个数参数，在后台验证一下是否是合法的个数（配置里面有一个分类产品个数列表）
     * 如果不合法，则报异常
     * 这个功能是为了防止分页攻击，伪造大量的不同个数的url，绕过缓存。
     */
    protected function getNumPerPage()
    {
        if (!$this->_numPerPageVal) {
            $numPerPage = Yii::$app->request->get($this->_numPerPage);
            $category_query_config = Yii::$app->getModule('catalog')->params['category_query'];
            if (!$numPerPage) {
                if (isset($category_query_config['numPerPage'])) {
                    if (is_array($category_query_config['numPerPage'])) {
                        $this->_numPerPageVal = $category_query_config['numPerPage'][0];
                    }
                }
            } elseif (!$this->_numPerPageVal) {
                if (isset($category_query_config['numPerPage']) && is_array($category_query_config['numPerPage'])) {
                    $numPerPageArr = $category_query_config['numPerPage'];
                    if (in_array((int) $numPerPage, $numPerPageArr)) {
                        $this->_numPerPageVal = $numPerPage;
                    } else {
                        throw new InvalidValueException('Incorrect numPerPage value:'.$numPerPage);
                    }
                }
            }
        }

        return $this->_numPerPageVal;
    }
    /**
     * 得到当前第几页
     */
    protected function getPageNum()
    {
        $numPerPage = Yii::$app->request->get($this->_page);

        return $numPerPage ? (int) $numPerPage : 1;
    }
    /**
     * 得到当前分类的产品
     */
    protected function getCategoryProductColl()
    {

        $select = [
                'sku', 'spu', 'name', 'image',
                'price', 'special_price',
                'special_from', 'special_to',
                'url_key', 'score',
            ];
        $category_query = Yii::$app->getModule('catalog')->params['category_query'];
        if (is_array($category_query['sort'])) {
            foreach ($category_query['sort'] as $sort_item) {
                $select[] = $sort_item['db_columns'];
            }
        }
        $filter = [
            'pageNum'      => $this->getPageNum(),
            'numPerPage'  => $this->getNumPerPage(),
            'orderBy'      => $this->getOrderBy(),
            'where'          => $this->_where,
            'select'      => $select,
        ];
        
        $productList = Yii::$service->category->product->getFrontList($filter);


        // 数据格式化处理
        foreach ($productList[coll] as $key => &$value) {

            // 生成商品图片
            $value['image']="http://img.chengzhanghao.com/media/catalog/product/{$value[image]}";
            # code...

            // 获取商品描述
            $datas=Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$value['_id']]);

            $value['description']=$datas['meta_description']['meta_description_zh'];
            $value['shop_id']=$datas['shop_id'];
            if ($value['shop_id']) {
                # code...
                // 获取商家信息

                $value['shop']= Yii::$app->db->createCommand("select * from shop where shop_id=$value[shop_id]")->queryOne();

            }

        }



        // 查询数据总条数

        // 进行数据查询
            
        
        // $i = 1;
        // $product_return = [];
        // $products = $productList['coll'];
        // if(is_array($products) && !empty($products)){
            
        //     foreach($products as $k=>$v){
        //         $i++;
        //         $products[$k]['url'] = '/catalog/product/'.$v['_id']; 
        //         $products[$k]['image'] = Yii::$service->product->image->getResize($v['image'],296,false);
        //         $priceInfo = Yii::$service->product->price->getCurrentCurrencyProductPriceInfo($v['price'], $v['special_price'],$v['special_from'],$v['special_to']);
        //         $products[$k]['price'] = isset($priceInfo['price']) ? $priceInfo['price'] : '';
        //         $products[$k]['special_price'] = isset($priceInfo['special_price']) ? $priceInfo['special_price'] : '';
        //         if (isset($products[$k]['special_price']['value'])) {
        //             $products[$k]['special_price']['value'] = Yii::$service->helper->format->number_format($products[$k]['special_price']['value']);
        //         }
        //         if (isset($products[$k]['price']['value'])) {
        //             $products[$k]['price']['value'] = Yii::$service->helper->format->number_format($products[$k]['price']['value']);
        //         }
        //         if($i%2 === 0){
        //             $arr = $products[$k];
        //         }else{
        //             $product_return[] = [
        //                 'one' => $arr,
        //                 'two' => $products[$k],
        //             ];
        //         }
        //     }
        //     if($i%2 === 0){
        //         $product_return[] = [
        //             'one' => $arr,
        //             'two' => [],
        //         ];
        //     }
        // }
        // $productList['coll'] = $product_return;
        return $productList;
    }
    /**
     * 得到用于查询的where数组。
     */
    protected function initWhere()
    {
        
        $chosenAttrs = Yii::$app->request->get('filterAttrs');
        $chosenAttrArr = json_decode($chosenAttrs,true);
        //var_dump($chosenAttrArr);
        
        if(is_array($chosenAttrArr) && !empty($chosenAttrArr)){
            $filterAttr = $this->getFilterAttr();
            //var_dump($filterAttr);
            foreach ($filterAttr as $attr) {
                if(isset($chosenAttrArr[$attr]) && $chosenAttrArr[$attr]){
                    $where[$attr] = $chosenAttrArr[$attr];
                }
            }
        }
        $filter_price = Yii::$app->request->get('filterPrice');
        //echo $filter_price;
        list($f_price, $l_price) = explode('-', $filter_price);
        if ($f_price == '0' || $f_price) {
            $where[$this->_filterPriceAttr]['$gte'] = (float) $f_price;
        }
        if ($l_price) {
            $where[$this->_filterPriceAttr]['$lte'] = (float) $l_price;
        }
        $where['category'] = $this->_primaryVal;
        //var_dump($where);exit;
        return $where;
    }
    /**
     * 分类部分的初始化
     * 对一些属性进行赋值。
     */
    protected function initCategory()
    {
        //$primaryKey = 'category_id';
        $primaryVal = Yii::$app->request->get('categoryId');
        $this->_primaryVal = $primaryVal;
        $category = Yii::$service->category->getByPrimaryKey($primaryVal);
        if ($category) {
            $enableStatus = Yii::$service->category->getCategoryEnableStatus();
            if ($category['status'] != $enableStatus){
                
                return false;
            }
        } else {
            
            return false;
        }
        $this->_category = $category;
        
        $this->_where = $this->initWhere();
        return true;
    }

    
    
    
   
    
}