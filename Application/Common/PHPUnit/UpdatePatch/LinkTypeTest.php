<?php

namespace Common\PHPUnit;

class LinkTypeTest extends BaseTest
{
    public function testSignInChange()
    {
        $model = D('NavItem');
        $list = $model->selectList();
        foreach ($list as $item) {
            $navs = jsonDecodeToArr($item['item_list']);
            foreach ($navs as $key => $nav) {
                if ($nav['action'] === 'sign_in' && $nav['title'] === '签到') {
                    $nav['title'] = '每日任务';
                    $navs[$key] = $nav;
                    $where = [];
                    $where['id'] = $item['id'];
                    $data = [];
                    $data['item_list'] = jsonEncode($nav);
                    $data = $model->where($where)->save($data);
                }
            }
        }
    }
}