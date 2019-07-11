<?php

namespace Common\Util;

abstract class Base
{

    public function error($msg = '')
    {
        if (IS_AJAX) {
            exit(getReturn(CODE_ERROR, $msg));
        } else {
            exit('<h4>' . $msg . '</h4>');
        }
    }
}