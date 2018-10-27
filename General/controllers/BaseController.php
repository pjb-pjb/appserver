<?php
/**
 * FecShop file.
 *
 * @link http://www.fecshop.com/
 * @copyright Copyright (c) 2016 FecShop Software LLC
 * @license http://www.fecshop.com/license/
 */

namespace fecshop\app\appserver\modules\General\controllers;

use fecshop\app\appserver\modules\AppserverController;
use Yii;
use yii\db\Query;
use yii\mongodb\Query as Query2;

/**
 * @author Terry Zhao <2358269014@qq.com>
 * @since 1.0 
 */
class BaseController extends AppserverController
{
    
    public function actionMenu()
    {
        if(Yii::$app->request->getMethod() === 'OPTIONS'){
            return [];
        }
//         $arr = [];
//         $displayHome = Yii::$service->page->menu->displayHome;
//         if($displayHome['enable']){
//             $home = $displayHome['display'] ? $displayHome['display'] : 'Home';
//             $home = Yii::$service->page->translate->__($home);
//             $arr['home'] = [
//                 '_id'   => 'home',
//                 'level' => 1,
//                 'name'  => $home,
//                 'url'   => '/'
//             ];
//         }
//         $currentLangCode = Yii::$service->store->currentLangCode;
//         $treeArr = Yii::$service->category->getTreeArr('',$currentLangCode,true);
//         if (is_array($treeArr)) {
//             foreach ($treeArr as $k=>$v) {
//                 $arr[$k] = $v ;
//             }
//         }
		
		$query = new Query2;
		
		$where['parent_id']="0";
		$where['type']="2";
		// 进行数据查询
		$arr = $query->from('category')
					 ->orderBy("sort desc")
					 ->where($where)
					 ->all();
		$brr = [];
		
		foreach($arr as $key => $value){
			
			$brr[$key]['_id'] = $value["_id"];

			$brr[$key]['name'] = $value["name"]['name_zh'];

		}
					 
        return $brr ;
    }
	
	
	public function actionMenu2(){
		
		if(Yii::$app->request->getMethod() === 'OPTIONS'){
			return [];
		}
//         $arr = [];
//         $displayHome = Yii::$service->page->menu->displayHome;
//         if($displayHome['enable']){
//             $home = $displayHome['display'] ? $displayHome['display'] : 'Home';
//             $home = Yii::$service->page->translate->__($home);
//             $arr['home'] = [
//                 '_id'   => 'home',
//                 'level' => 1,
//                 'name'  => $home,
//                 'url'   => '/'
//             ];
//         }
//         $currentLangCode = Yii::$service->store->currentLangCode;
//         $treeArr = Yii::$service->category->getTreeArr('',$currentLangCode,true);
//         if (is_array($treeArr)) {
//             foreach ($treeArr as $k=>$v) {
//                 $arr[$k] = $v ;
//             }
//         }
		
		$query = new Query2;
		
		$where['parent_id']="0";
		$where['type']="1";
		// 进行数据查询
		$arr = $query->from('category')
					 ->orderBy("sort desc")
					 ->where($where)
					 ->all();
		$brr = [];
		
		foreach($arr as $key => $value){
			
			$brr[$key]['_id'] = $value["_id"];

			$brr[$key]['name'] = $value["name"]['name_zh'];

		}
					 
		return $brr ;
	}
    // 语言
    public function actionLang()
    {
        if(Yii::$app->request->getMethod() === 'OPTIONS'){
            return [];
        }
        $langs = Yii::$service->store->serverLangs;
        $currentLangCode = Yii::$service->store->currentLangCode;
        
        return [
            'langList' => $langs,
            'currentLang' => $currentLangCode
        ];
    }
    
    public function actionCurrency()
    {
        if(Yii::$app->request->getMethod() === 'OPTIONS'){
            return [];
        }
        $currencys = Yii::$service->page->currency->getCurrencys();
        $currentCurrencyCode = Yii::$service->page->currency->getCurrentCurrency();
        
        return [
            'currencyList' => $currencys,
            'currentCurrency' => $currentCurrencyCode
        ];
    }

    // 获取城市
    public function  actionSyscityall(){
        $posts = Yii::$app->db->createCommand('SELECT * FROM sys_city where province_id = 4')->queryAll();
        return $posts;
    }
    
}