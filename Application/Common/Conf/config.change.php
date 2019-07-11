<?php

$varCtrl = 'c';
$varAct = 'a';
if (defined('BIND_MODULE') && strtolower(BIND_MODULE) === 'api') {
    $varCtrl = 'act';
    $varAct = 'op';
}
return array(
    // ==========================================    通用设置    ========================================================
    /* 默认设定 */
    'VAR_CONTROLLER' => $varCtrl,    // 默认控制器获取变量
    'VAR_ACTION' => $varAct,    // 默认操作获取变量
);

