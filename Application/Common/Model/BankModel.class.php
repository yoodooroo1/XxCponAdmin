<?php

namespace Common\Model;

use Think\Model;

class BankModel extends Model
{
    protected $tableName = 'mb_bank';

    public function getBankList()
    {
        return $this->field('id bank_id,bank_name')->select();
    }
}