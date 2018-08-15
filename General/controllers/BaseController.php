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

/**
 * @author Terry Zhao <2358269014@qq.com>
 * @since 1.0 
 */
class BaseController extends AppserverController
{
    
    public function actionMenu()
    {
        $query = new Query();

        $arr = $query->from("category")->orderBy("sort desc")->where(["level"=>1,"menu_show"=>1])->all();

        echo json_encode($arr);
        exit();
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
    public function  actionSyscity(){
        $posts = Yii::$app->db->createCommand('SELECT * FROM sys_city where province_id = 4')->queryAll();
        return $posts;
    }
    
}