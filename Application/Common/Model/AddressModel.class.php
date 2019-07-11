<?php

namespace Common\Model;
class AddressModel extends BaseModel
{
   protected $tableName = 'mb_address';

   public function getAddressInfo($address_id){
      $addressData = M("mb_address")->where(array('address_id'=>$address_id,'isdelete'=>0))->find();
      if (empty($addressData)){
          return getReturn(200,'成功',array());
      }
      return getReturn(200,'成功',$addressData);
   }
}