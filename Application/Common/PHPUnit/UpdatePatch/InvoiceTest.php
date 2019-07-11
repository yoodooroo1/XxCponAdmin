<?php

namespace Common\PHPUnit;

class InvoiceTest extends BaseTest
{
    public function testOrderInvoice()
    {
        $model = D('Order');
        $where = [];
        $where['invoice_id'] = ['gt', 0];
        $list = $model->field('order_id,invoice_id,invoice_title,invoice_type')->where($where)->select();
        $invoiceModel = D('Invoice');
        $invoice = [];
        $saveData = [];
        $version = $model->max('version');
        foreach ($list as $order) {
            if (!isset($invoice[$order['invoice_id']])) {
                $invoice[$order['invoice_id']] = $invoiceModel->field('invoice_id,invoice_title,invoice_type,invoice_code')->find($order['invoice_id']);
            }
            if ($invoice[$order['invoice_id']]['invoice_id'] > 0) {
                $data = [];
                $data['order_id'] = $order['order_id'];
                $data['version'] = ++$version;
                if (empty($order['invoice_title'])) {
                    $data['invoice_title'] = $invoice[$order['invoice_id']]['invoice_title'];
                    $data['invoice_type'] = $invoice[$order['invoice_id']]['invoice_type'];
                }
                $data['invoice_code'] = $invoice[$order['invoice_id']]['invoice_code'];
                $saveData[] = $data;
                $model->save($data);
            }
        }
        F('saveData', $saveData);
    }
}