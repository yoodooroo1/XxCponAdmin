<?php

namespace Common\Model;
class DockStoreModel extends BaseModel
{
   protected $tableName = 'mb_hp_store';

   public function addStoreData($value, $fmch_id){
      $data = array();
      $data['nid'] = $value['nid'];
      $data['fcode'] = $value['fcode'];
       $data['fname'] = $value['fname'];
       $data['faddress'] = $value['faddress'];
       $data['fsummary'] = $value['fsummary'];
       $data['fmch_id'] = $fmch_id;
       $tag =  $this->add($data);
       if ($tag !== false){
           return true;
       }
       return false;
   }

    public function saveStoreData($value){
        $data = array();
        $data['fcode'] = $value['fcode'];
        $data['fname'] = $value['fname'];
        $data['faddress'] = $value['faddress'];
        $data['fsummary'] = $value['fsummary'];
        $tag =  $this->where(array('nid' => $value['nid']))->save($data);
        if ($tag !== false){
            return true;
        }
        return false;
    }
}