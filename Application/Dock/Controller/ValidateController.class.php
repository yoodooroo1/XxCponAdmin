<?php
/**
 * Created by PhpStorm.
 * User: Ydr
 * Date: 2019/8/15
 * Time: 22:03
 */

namespace Dock\Controller;


class ValidateController extends BaseController
{
    public function checkGoodsBean($new_value,$old_value){
        $rs_data = true;
        if($new_value['fcode']!==$old_value['fcode']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fname']!==$old_value['fname']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fproductno']!==$old_value['fproductno']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fcolorlist']!==$old_value['fcolorlist']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fbrandid']!==$old_value['fbrandid']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['flsj']!==$old_value['flsj']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fpfj']!==$old_value['fpfj']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fhyj']!==$old_value['fhyj']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fyhj']!==$old_value['fyhj']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['ftj']!==$old_value['ftj']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fhyj']!==$old_value['fhyj']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fdpj']!==$old_value['fdpj']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fseason']!==$old_value['fseason']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fyear']!==$old_value['fyear']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fsn']!==$old_value['fsn']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fjg1']!==$old_value['fjg1']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fjg2']!==$old_value['fjg2']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fjg3']!==$old_value['fjg3']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fjg4']!==$old_value['fjg4']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fjg5']!==$old_value['fjg5']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fstyleid']!==$old_value['fstyleid']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fput']!==$old_value['fput']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fsummary']!==$old_value['fsummary']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fjg6']!==$old_value['fjg6']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fclassid']!==$old_value['fclassid']){
            $rs_data = false;
            return $rs_data;
        }
        if(json_encode($new_value['prodetail'])!==json_encode($old_value['$old_value'])){
            $rs_data = false;
            return $rs_data;
        }
    }

    public function checkGoodsStockBean($new_value,$old_value){
        $rs_data = true;
        if($new_value['fshopcode']!==$old_value['fshopcode']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fshopname']!==$old_value['fshopname']){
            $rs_data = false;
            return $rs_data;
        }
        if($new_value['fprodid']!==$old_value['fprodid']){
            $rs_data = false;
            return $rs_data;
        }
        if(json_encode($new_value['prodetail'])!==json_encode($old_value['prodetail'])){
            $rs_data = false;
            return $rs_data;
        }
    }
}