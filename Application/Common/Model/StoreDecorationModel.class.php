<?php

namespace Common\Model;

use Common\Util\Decoration;

/**
 * 店铺装修类
 * Class StoreDecorationModel
 * @package Common\Model
 * User: hjun
 * Date: 2018-07-27 16:04:17
 * Update: 2018-07-27 16:04:17
 * Version: 1.00
 */
class StoreDecorationModel extends BaseModel
{
    protected $tableName = 'mb_decoration';
    protected $bottomButtons;
    protected $type = Decoration::TYPE_WX;

    const BOTTOM_ORIGINAL = 0;  // 原始样式
    const BOTTOM_CUSTOM = 1; // 自定义样式

    private $memberId = 0;
    private $label = '';
    private $cartNum = 0;


    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($name, $tablePrefix, $connection);
        $this->bottomButtons = [
            Decoration::SHOP_MODULE,
            Decoration::CLASSIFY_MODULE,
            Decoration::GUIDE_MODULE,
            Decoration::CUSTOMER_SERVICE_MODULE,
            Decoration::CART_MODULE,
            Decoration::USER_MODULE,
        ];
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        if (in_array($type, [Decoration::TYPE_WX, Decoration::TYPE_APP])) {
            $this->type = $type;
        } else {
            $this->type = Decoration::TYPE_WX;
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getMemberId()
    {
        return $this->memberId;
    }

    /**
     * @param int $memberId
     * @return $this
     */
    public function setMemberId($memberId)
    {
        $this->memberId = $memberId;
        return $this;
    }

    /**
     * @param array $bottomData
     * @return string
     */
    public function getLabel($bottomData = [])
    {
        // 如果有值了 直接返回
        if (!empty($this->label)) {
            return $this->label;
        }
        // 如果进入的是首页
        if ($this->isIndex()) {
            // 如果有按钮数据 则从按钮中 通过url找到首页的那个按钮
            if (!empty($bottomData)) {
                $btns = $bottomData['content']['diy_nav_list'];
                foreach ($btns as $btn) {
                    if ($btn['action'] != Decoration::ACTION_WEB_URL) {
                        continue;
                    }
                    // 分析url
                    $urlData = parse_url($btn['action_data']);
                    $query = $urlData['query'];
                    if (empty($query)) {
                        continue;
                    }
                    // 从query部分分析控制器和操作
                    $arr = explode('&', $query);
                    $params = [];
                    foreach ($arr as $value) {
                        $item = explode('=', $value);
                        $params[$item[0]] = $item[1];
                    }
                    $varController = C('VAR_CONTROLLER');
                    $varAction = C('VAR_ACTION');
                    $ctrl = strtolower($params[$varController]);
                    $act = strtolower($params[$varAction]);
                    if ($this->isIndex($ctrl, $act)) {
                        return $btn['label'];
                    }
                }
            }
            return Decoration::SHOP_MODULE;
        }
        if (empty($this->label)) {
            return Decoration::SHOP_MODULE;
        }
        return $this->label;
    }

    /**
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return int
     */
    public function getCartNum()
    {
        return $this->cartNum;
    }

    /**
     * @param int $cartNum
     * @return $this
     */
    public function setCartNum($cartNum)
    {
        $this->cartNum = $cartNum;
        return $this;
    }

    /**
     * 初始化底部数据
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-08-02 15:17:36
     * Update: 2018-08-02 15:17:36
     * Version: 1.00
     */
    public function initBottomData($storeId = 0)
    {
        $decorationUtil = new Decoration($storeId);
        $decorationUtil->setType($this->getType());
        $data = [
            'style' => self::BOTTOM_ORIGINAL,
            'text_color' => '#000000', // 文字颜色 默认黑色
            'background_color' => '#FFFFFF', // 背景颜色 默认白色
            'content' => [
                'original_nav_list' => $decorationUtil->getBottomButtons(),
                'diy_nav_list' => $decorationUtil->getBottomButtons(),
            ],
        ];
        return $data;
    }

    /**
     * 获取底部数据
     * @param int $storeId
     * @return array
     * User: hjun
     * Date: 2018-07-27 16:12:58
     * Update: 2018-07-27 16:12:58
     * Version: 1.00
     */
    public function getBottomData($storeId = 0)
    {
        $data = $this->getDecorationData($storeId);
        $bottomData = jsonDecodeToArr($data[$this->type . '_bottom_data']);
        if (empty($bottomData)) {
            $bottomData = $this->initBottomData($storeId);
        } else {
            $decorationUtil = new Decoration($storeId);
            $decorationUtil->setType($this->getType());
            $decorationUtil->setMemberId($this->getMemberId());
            $bottomData['content']['original_nav_list'] = $decorationUtil->getBottomButtons();
        }
        return $bottomData;
    }

    /**
     * 获取会员主页数据
     * @param int $storeId
     * @return array
     * User: hjun
     * Date: 2018-07-27 16:13:04
     * Update: 2018-07-27 16:13:04
     * Version: 1.00
     */
    public function getUserData($storeId = 0)
    {
        $data = $this->getDecorationData($storeId);
        $data = jsonDecodeToArr($data[$this->type . '_user_data']);
        if (empty($data)) {
            $data = [
                'page_name' => L('FOOT_MINE'),
                'background_url' => '',
                'show_member_name' => '1',
                'show_member_nickname' => '1',
                'show_member_level' => '1',
                'wait_pay' => L('ORDER_STATE_2'),
                'wait_confirm' => L('ORDER_STATE_3'),
                'wait_deliver' => L('ORDER_STATE_4'),
                'wait_rec' => L('ORDER_STATE_6'),
                'already_complete' => L('ORDER_STATE_10'),
            ];
        }
        // 下载菜单需要与store表的字段同步
        $storeGrade = D('Store')->getStoreGrantInfo($storeId)['data'];
        if ($storeGrade['app_state'] == 1) {
            $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
            $data['menu_app_download'] = $storeInfo['app_download_status'] == 1 ? '1' : '0';
        }
        // 其他菜单需要初始化
        $menuFields = [
            'menu_evaluation', 'menu_address', 'menu_coupons', 'menu_prize', 'menu_balance',
            'menu_collect', 'menu_notice', 'menu_form', 'menu_account',
        ];
        foreach ($menuFields as $field) {
            // 数据中没有的话,就是默认1
            if (!isset($data[$field])) {
                $data[$field] = 1;
                continue;
            }
            $data[$field] = $data[$field] == 1 ? 1 : 0;
        }
        return $data;
    }

    /**
     * 保存会员主页数据
     * @param int $storeId
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-09-27 16:55:46
     * Update: 2018-09-27 16:55:46
     * Version: 1.00
     */
    public function saveUserData($storeId = 0, $request = [])
    {
        $type = $this->getType();
        $where = [];
        $where['store_id'] = $storeId;
        $data = [];
        $data["{$type}_user_data"] = jsonEncode($request);

        $this->startTrans();
        $result = $this->where($where)->save($data);
        if (false === $result) {
            $this->rollback();
            return getReturn(CODE_ERROR);
        }
        if (isset($request['menu_app_download'])) {
            $storeModel = D('Store');
            $where = [];
            $where['store_id'] = $storeId;
            $data = [];
            $data['version'] = $storeModel->max('version');
            $data['app_download_status'] = $request['menu_app_download'] == 1 ? 1 : 0;
            $result = $storeModel->where($where)->save($data);
            if (false === $result) {
                $this->rollback();
                return getReturn(CODE_ERROR);
            }
        }
        $this->commit();
        return getReturn(CODE_SUCCESS, L('SAVE_SUCCESSFUL'));
    }

    /**
     * 获取全店风格数据
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-07-27 16:13:17
     * Update: 2018-07-27 16:13:17
     * Version: 1.00
     */
    public function getAllStyleData($storeId = 0)
    {
        $data = $this->getDecorationData($storeId);
        $styleData = jsonDecodeToArr($data[$this->type . '_all_style_data']);;
        if (empty($styleData)) {
            $styleData = [
                'selectedScheme' => 1
            ];
        }
        $styleData['class'] = "store-style-{$styleData['selectedScheme']}";
        return $styleData;
    }

    /**
     * 获取装修数据
     * @param int $storeId
     * @return array
     * User: hjun
     * Date: 2018-07-27 16:07:02
     * Update: 2018-07-27 16:07:02
     * Version: 1.00
     */
    public function getDecorationData($storeId = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $options = [];
        $options['where'] = $where;
        $info = $this->selectRow($options);
        if (empty($info)) {
            $data = [];
            $data['store_id'] = $storeId;
            $results[] = $this->add($data);
            if (isTransFail($results)) {
                return [];
            }
            $info = $this->selectRow($options);
        }
        return $info;
    }

    /**
     * 保存底部导航
     * @param int $storeId
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-08-08 12:00:12
     * Update: 2018-08-08 12:00:12
     * Version: 1.00
     */
    public function saveBottomData($storeId = 0, $request = [])
    {
        $this->startTrans();
        $where = [];
        $where['store_id'] = $storeId;

        // 如果是原始样式 还需要修改store表
        if ($request['style'] == 0) {
            $originNavs = $request['content']['original_nav_list'];
            $data = [];
            $data['version'] = M('store')->max('version') + 1;
            foreach ($originNavs as $nav) {
                // 有权限才修改
                if ($nav['ctrl'] == 1) {
                    $data[$nav['label']] = $nav['is_show'];
                }
            }
            $results[] = M('store')->where($where)->save($data);
        }

        $type = $this->getType();

        $data = [];
        $data["{$type}_bottom_data"] = jsonEncode($request);
        $results[] = $this->where($where)->save($data);
        if (isTransFail($results)) {
            return getReturn(CODE_ERROR);
        }
        $this->commit();
        return getReturn(CODE_SUCCESS, L('SAVE_SUCCESSFUL'));
    }

    /**
     * 保存全店风格数据
     * @param int $storeId
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-09-10 16:47:54
     * Update: 2018-09-10 16:47:54
     * Version: 1.00
     */
    public function saveAllStyleData($storeId = 0, $request = [])
    {
        $type = $this->getType();
        $where = [];
        $where['store_id'] = $storeId;
        $data = [];
        $data["{$type}_all_style_data"] = jsonEncode($request);
        $result = $this->where($where)->save($data);
        if (false === $result) {
            return getReturn(CODE_ERROR);
        }
        return getReturn(CODE_SUCCESS, L('SAVE_SUCCESSFUL'));
    }

    /**
     * 清除首页模板缓存
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-22 18:32:00
     * Update: 2018-11-22 18:32:00
     * Version: 1.00
     */
    public function clearTplCache($storeId = 0)
    {
        $key = "main_tpl:{$storeId}";
        S($key, null);
        return getReturn(CODE_SUCCESS, '清除成功');
    }

    /**
     * 获取底部导航的HTML
     * @param int $storeId
     * @return string
     * User: hjun
     * Date: 2018-09-25 10:26:22
     * Update: 2018-09-25 10:26:22
     * Version: 1.00
     */
    public function getBottomHtml($storeId = 0)
    {
        $bottomData = $this->getBottomData($storeId);
        $label = $this->getLabel($bottomData);
        $html = '';
        if ($bottomData['style'] == self::BOTTOM_CUSTOM) {
            $btns = $bottomData['content']['diy_nav_list'];
            $bottomHtml = getDefaultData('template/diy/bottom/bottom', 'html');
            $bottomHtml = str_replace('{$BG_COLOR}', $bottomData['background_color'], $bottomHtml);
            $btnHtml = getDefaultData('template/diy/bottom/btn', 'html');
            $btnsHtml = '';
            $num = 0;
            foreach ($btns as $btn) {
                if ($btn['ctrl'] == 1 && $btn['is_show'] == 1) {
                    $num++;
                }
            }
            $width = (int)(100 / $num) . '%';
            foreach ($btns as $btn) {
                if ($btn['ctrl'] == 1 && $btn['is_show'] == 1) {
                    $itemHtml = str_replace('{$WIDTH}', $width, $btnHtml);
                    if ($btn['label'] == $label) {
                        // 当前图标选中时
                        $itemHtml = str_replace('{$IMG}', $btn['selected_img'], $itemHtml);
                        $selectedTextColor = empty($bottomData['selected_text_color']) ? '#d83838' : $bottomData['selected_text_color'];
                        $itemHtml = str_replace('{$BTN_COLOR}', $selectedTextColor, $itemHtml);
                    } else {
                        // 未选中
                        $itemHtml = str_replace('{$IMG}', $btn['normal_img'], $itemHtml);
                        $itemHtml = str_replace('{$BTN_COLOR}', $bottomData['text_color'], $itemHtml);
                    }
                    $itemHtml = str_replace('{$BTN_TEXT}', $btn['name'], $itemHtml);
                    // 如果是购物车图标
                    if ($btn['label'] === Decoration::CART_MODULE) {
                        $itemHtml = str_replace('{$CART_ID}', 'id="end"', $itemHtml);
                        $cardNum = $this->getCartNum();
                        $cartNumLabel = '<label class="amountI style-icon-circle" id="amountLabel">' . $cardNum . '</label>';
                        $itemHtml = str_replace('{$CART_NUM_LABEL}', $cartNumLabel, $itemHtml);
                    } else {
                        $itemHtml = str_replace('{$CART_ID}', '', $itemHtml);
                        $itemHtml = str_replace('{$CART_NUM_LABEL}', '', $itemHtml);
                    }
                    $actionUrl = UtilModel::getLinkTypeUrl($btn['action'], $btn['action_data'], $storeId, $this->getMemberId());
                    $link = strpos($actionUrl, '?') === false ? '?' : '&';
                    $actionUrl .= "{$link}btn_label={$btn['label']}";
                    $itemHtml = str_replace('{$ACTION_URL}', $actionUrl, $itemHtml);
                    $btnsHtml .= $itemHtml;
                }
            }
            $html = str_replace('{$BTNS}', $btnsHtml, $bottomHtml);
        }
        return $html;
    }

    /**
     * 是否是首页
     * @param string $ctrl
     * @param string $act
     * @return boolean
     * User: hjun
     * Date: 2019-05-12 18:35:31
     * Update: 2019-05-12 18:35:31
     * Version: 1.00
     */
    public function isIndex($ctrl = '', $act = '')
    {
        if (empty($ctrl)) {
            $ctrl = strtolower(CONTROLLER_NAME);
        }
        if (empty($act)) {
            $act = strtolower(ACTION_NAME);
        }
        if ($ctrl === 'index') {
            if (in_array($act, ['store', 'index'])) {
                return true;
            }
        }
        if ($ctrl === 'decoration') {
            return true;
        }
        return false;
    }
}