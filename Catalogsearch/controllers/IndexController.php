<?php
/**
 * FecShop file.
 *
 * @link http://www.fecshop.com/
 * @copyright Copyright (c) 2016 FecShop Software LLC
 * @license http://www.fecshop.com/license/
 */

namespace fecshop\app\appserver\modules\Catalogsearch\controllers;

use fecshop\app\appserver\modules\AppserverController;
use Yii;
use yii\mongodb\Query;
use yii\data\Pagination;
 
/**
 * @author Terry Zhao <2358269014@qq.com>
 * @since 1.0
 */
class IndexController extends AppserverController
{
    
    // 当前的搜索词
    protected $_searchText;
    // 当前页面的title
    protected $_title;
    // where 条件，用于查询
    protected $_where;
    // url中的参数，每页的产品个数
    protected $_numPerPage = 'numPerPage';
    // url中的参数，排序方向
    protected $_direction = 'dir';
    // url中的参数，排序属性
    protected $_sort = 'sortColumn';
    // url中的参数，页数
    protected $_page = 'p';
    // url中的参数，产品价格
    protected $_filterPrice = 'price';
    protected $_filterPriceAttr = 'price';
    protected $_productCount;
    protected $_filter_attr;
    protected $_numPerPageVal;
    protected $sp = '---';
		
		// 搜索相关的方法 
    public function actionIndex()
    {
        if(Yii::$app->request->getMethod() === 'OPTIONS'){
            return [];
        }

        $query = new Query;

        
        
        $rows=$query->select(["_id","price","special_price",'name.name_zh',"image.main","deposit",'type'])->from('product_flat')->where(['name.name_zh'=>['$regex'=>"$_GET[q]"]])->offset($_GET[page]*10)->limit(10)->all();


        foreach ($rows as $key => &$value) {

            // 生成商品图片
            // $value['image']="http://img.chengzhanghao.com:81/media/catalog/product/{$value[image][main]}";
            # code...

            // 获取商品描述
            $datas=Yii::$app->mongodb->getCollection('product_flat')->findOne(['_id'=>$value['_id']]);
						
						//好评
						$wu = $query->from("review")->where(["rate_star"=>"5","product_id"=>$value["_id"]])->count();

						$si = $query->from("review")->where(["rate_star"=>"4","product_id"=>$value["_id"]])->count();
						
						$san = $query->from("review")->where(["rate_star"=>"3","product_id"=>$value["_id"]])->count();

						$er = $query->from("review")->where(["rate_star"=>"2","product_id"=>$value["_id"]])->count();
						
						$yi = $query->from("review")->where(["rate_star"=>"1","product_id"=>$value["_id"]])->count();
						
						// 计算总分
						
						$zong = $wu*5+$si*4+$san*3+$er*2+$yi*1;

						//所有的评论个数
						$all = $query->from("review")->where(["product_id"=>$value["_id"]])->count();
						// 所以的评论
						$value['all']=$all;
						
						if($all>0){
							$value["praise"] = number_format($zong/$all,2);
						}else{
							$value["praise"] = -1;
						}
            $value['description']=$datas['meta_description']['meta_description_zh'];
            $value['shop_id']=$datas['shop_id'];
						
						//本月时间戳
						$beginThismonth=mktime(0,0,0,date('m'),1,date('Y'));
						$endThismonth=mktime(23,59,59,date('m'),date('t'),date('Y'));
						
						//月销售连量
						$volume = Yii::$app->db->createCommand("select sum(qty) nums from sales_flat_order_item where product_id='$value[_id]['$oid']' and updated_at>=$beginThismonth and updated_at<$endThismonth")->queryAll();
						
						$value[volume] = $volume[nums];
						
            if ($value['shop_id']) {

                $value['shop']= Yii::$app->db->createCommand("select shop_id,shop_name,shop_logo from shop where shop_id=$value[shop_id]")->queryOne();

            }

        }


        return $rows;

    }
    
    
    public function actionProduct()
    {
        if(Yii::$app->request->getMethod() === 'OPTIONS'){
            return [];
        }
        // 每页显示的产品个数，进行安全验证，如果个数不在预先设置的值内，则会报错。
        // 这样是为了防止恶意攻击，也就是发送很多不同的页面个数的链接，绕开缓存。
        $this->getNumPerPage();
        if(!$this->initSearch()){
            $data = [
                'content' => 'disable',
            ];
            $code = Yii::$service->helper->appserver->status_attack;
            $reponseData = Yii::$service->helper->appserver->getReponseData($code, $data);
            
            return $reponseData;
            
        }
        $productCollInfo = $this->getSearchProductColl();
        $products = $productCollInfo['coll'];
        $this->_productCount = $productCollInfo['count'];
        $data = [
            'products' => $products
        ];
        $code = Yii::$service->helper->appserver->status_success;
        $reponseData = Yii::$service->helper->appserver->getReponseData($code, $data);
        
        return $reponseData;
        
    }
    
    
    /**
     * 得到侧栏属性过滤属性
     */
    protected function getFilterAttr()
    {
        if (!$this->_filter_attr) {
            $this->_filter_attr = $filterAttr = Yii::$service->search->filterAttr;
        }

        return $this->_filter_attr;
    }
    /**
     * 得到已经选择了的过滤属性，譬如对color属性，点击了blue，进行了选择，就会出现在这里
     * 方便用户通过点击的方式取消掉属性过滤
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
                    'val'  =>  $val,
                ];
            }
        }
        $currenctPriceFilter = Yii::$app->request->get($this->_filterPrice); 
        if($currenctPriceFilter){
            $refineInfo[] = [
                'attr' =>  $this->_filterPrice,
                'val'  =>  $currenctPriceFilter,
            ];
        }
        
        if (!empty($refineInfo)) {
            $arr[] = [
                'attr'   => 'clearAll',
                'val'    => 'clear all',
            ];
            $refineInfo = array_merge($arr, $refineInfo);
        }

        return $refineInfo;
    }
    /**
     * 得到搜索页面进行过滤的属性
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
                $items = Yii::$service->search->getFrontSearchFilter($attr, $this->_where);
                if(is_array($items) && !empty($items)){
                    foreach($items as $k=>$one){
                        if(isset($chosenAttrArr[$attr]) && $chosenAttrArr[$attr] == $one['_id']){
                            $items[$k]['selected'] = true;
                        } else {
                            $items[$k]['selected'] = false;
                        }
                        
                    }
                }
                
                $filter_info[$attr] = [
                    'label' => $label,
                    'items' => $items,
                ];
            }
        }

        return $filter_info;
    }
    /**
     * 得到分类页面价格过滤部分
     */
    protected function getFilterPrice()
    {
        $symbol = Yii::$service->page->currency->getCurrentSymbol();
        
        $currenctPriceFilter = Yii::$app->request->get($this->_filterPrice);
        $filter = [];
        $priceInfo = Yii::$app->controller->module->params['search_query'];
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
     * 产品价格显示格式处理
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
     * 得到每页显示的产品的个数。
     */
    protected function getNumPerPage()
    {
        if (!$this->_numPerPageVal) {
            $numPerPage = Yii::$app->request->get($this->_numPerPage);
            $category_query_config = Yii::$app->controller->module->params['search_query'];
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
     * 得到第几页
     */
    protected function getPageNum()
    {
        $numPerPage = Yii::$app->request->get($this->_page);

        return $numPerPage ? (int) $numPerPage : 1;
    }
    /**
     * 得到搜索的产品collection
     */
    protected function getSearchProductColl()
    {
       /////////////////************
        $select = [
            'product_id','sku', 'spu', 'name', 'image',
            'price', 'special_price',
            'special_from', 'special_to',
            'url_key', 'score',
        ];
        $where = $this->_where;
        $search_text = Yii::$app->controller->module->params['search_query'];
        $pageNum = $this->getPageNum();
        $numPerPage = $this->getNumPerPage();
        
        $product_search_max_count = Yii::$app->controller->module->params['product_search_max_count'];
        $filterAttr = $this->getFilterAttr();
        $productList = Yii::$service->search->getSearchProductColl($select, $where, $pageNum, $numPerPage, $product_search_max_count, $filterAttr);
    
        $i = 1;
        $product_return = [];
        $products = $productList['coll'];
        if(is_array($products) && !empty($products)){
            foreach($products as $k=>$v){
                if($v['sku']){
                    $i++;
                    $products[$k]['url'] = '/catalog/product/'.$v['product_id']; 
                    $products[$k]['image'] = Yii::$service->product->image->getResize($v['image'],296,false);
                    $priceInfo = Yii::$service->product->price->getCurrentCurrencyProductPriceInfo($v['price'], $v['special_price'],$v['special_from'],$v['special_to']);
                    $products[$k]['price'] = isset($priceInfo['price']) ? $priceInfo['price'] : '';
                    $products[$k]['special_price'] = isset($priceInfo['special_price']) ? $priceInfo['special_price'] : '';
                    if (isset($products[$k]['special_price']['value'])) {
                        $products[$k]['special_price']['value'] = Yii::$service->helper->format->number_format($products[$k]['special_price']['value']);
                    }
                    if (isset($products[$k]['price']['value'])) {
                        $products[$k]['price']['value'] = Yii::$service->helper->format->number_format($products[$k]['price']['value']);
                    }
                    if($i%2 === 0){
                        $arr = $products[$k];
                    }else{
                        $product_return[] = [
                            'one' => $arr,
                            'two' => $products[$k],
                        ];
                    }
                }
            }
            if($i%2 === 0){
                $product_return[] = [
                    'one' => $arr,
                    'two' => [],
                ];
            }
        }
        $productList['coll'] = $product_return;
        return $productList;
    
    
    
    }
    /**
     * 初始化where
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
        $filter_price = Yii::$app->request->get($this->_filterPrice);
        //echo $filter_price;
        list($f_price, $l_price) = explode('-', $filter_price);
        if ($f_price == '0' || $f_price) {
            $where[$this->_filterPriceAttr]['$gte'] = (float) $f_price;
        }
        if ($l_price) {
            $where[$this->_filterPriceAttr]['$lte'] = (float) $l_price;
        }
        $where['$text'] = ['$search' => $this->_searchText];
        //$where['status'] = 1;
        //$where['is_in_stock'] = 1;
        $this->_where = $where;

        return $where;
    }
    /**
     * 初始化部分
     */
    protected function initSearch()
    {
        //$primaryKey = Yii::$service->category->getPrimaryKey();
        //$primaryVal = Yii::$app->request->get($primaryKey);
        //$this->_primaryVal = $primaryVal;
        //$category 	= Yii::$service->category->getByPrimaryKey($primaryVal);
        //$this->_category = $category ;
        $searchText = Yii::$app->request->get('q');
        $searchText = \Yii::$service->helper->htmlEncode($searchText);
        $this->_searchText = $searchText;
        $this->_where = $this->initWhere();
        return true;
    }

    
    
}