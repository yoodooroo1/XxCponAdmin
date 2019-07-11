<?php

namespace Common\PHPUnit;

class IndexPageTplModelTest extends BaseTest
{
    public function testGetDefaultTplData()
    {
        $data = D('IndexPageTpl')->getDefaultTpl(564);
    }
}