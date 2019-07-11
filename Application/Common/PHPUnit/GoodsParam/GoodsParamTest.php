<?php

namespace Common\PHPUnit;


class GoodsParamTest extends BaseTest
{
    public function testValidateTplParams()
    {
        $model = D('GoodsParamTpl');
        $data = [
            [
                'param_name' => ''
            ],
            [
                'param_name' => '测试2'
            ],
        ];
        $result = $model->validateTplParams($data);
    }
}