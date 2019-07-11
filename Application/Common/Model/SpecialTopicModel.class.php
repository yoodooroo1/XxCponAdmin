<?php

namespace Common\Model;

use Think\Cache\Driver\Redis;

/**
 * Class SpecialTopicModel
 * 专题模型
 * @package Common\Model
 * User: hjun
 * Date: 2017-12-01 14:26:14
 */
class SpecialTopicModel extends BaseModel
{

    const MODULE_TWO = 2;
    const MODULE_THREE = 3;
    const MODULE_FOUR = 4;
    const MODULE_ONE = 1;
    const MODULE_GOODS = 7;

    const SOURCE_CLASS_GOODS = 0;
    CONST SOURCE_TAG_GOODS = 1;

    protected $tableName = 'mb_special_topic';

    protected $_validate = [
        ['st_name', 'require', '请输入专题名称', 0, 'regex', 3]
    ];

    /**
     * @param int $storeId 商家ID
     * @param int $channelId 渠道号
     * @param int $page 页数
     * @param int $limit 条数
     * @param array $map 额外查询条件
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-09 14:27:56
     * Desc: 根据商家ID或者渠道ID 获取专题列表
     * Update: 2017-10-09 14:27:57
     * Version: 1.0
     */
    public function getStList($storeId = 0, $channelId = 0, $page = 1, $limit = 0, $map = [])
    {
        // 判断参数
        $storeId = (int)$storeId;
        $channelId = (int)$channelId;
        // 条件查询 使用 store_id 和 channel_id 都可以 大于0就用 没有大于0就不用
        $where = [];
        $where['is_delete'] = -1;
        $storeId > 0 ? $where['store_id'] = $storeId : null;
        $channelId > 0 ? $where['channel_id'] = $channelId : null;
        $where = array_merge($where, $map);
        $skip = ($page - 1) * $limit;
        $take = $limit;
        $order = 'create_time DESC';
        $options = [];
        $options['where'] = $where;
        $options['field'] = true;
        $options['skip'] = $skip;
        $options['take'] = $take;
        $options['order'] = $order;
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        foreach ($list as $key => $value) {
            $list[$key]['create_time_string'] = date('Y-m-d H:i:s', $value['create_time']);
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * @param int $stId 专题ID
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-09 17:02:38
     * Desc: 根据ID删除单个主题
     * Update: 2017-10-09 17:02:42
     * Version: 1.0
     */
    public function delStById($stId = 0)
    {
        if (is_array($stId)) return $this->delBatchSt($stId);
        $stId = (int)$stId;
        if ($stId <= 0) return getReturn(-1, L('INVALID_PARAM'));
        $where = [];
        $where['st_id'] = $stId;
        $options = [];
        $options['field'] = 'st_id,is_delete';
        $options['where'] = $where;
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (empty($info)) return getReturn(-1, L('RECORD_INVALID'));
        if ((int)$info['is_delete'] === 1) return getReturn(-1, L('OUT_OF_DATE'));
        $data = [];
        $data['is_delete'] = 1;
        $options = [];
        $options['where'] = $where;
        return $this->saveData($options, $data);
    }

    /**
     * 批量删除专题
     * @param array $stId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-01 15:24:34
     * Update: 2017-12-01 15:24:34
     * Version: 1.00
     *
     */
    public function delBatchSt($stId = [])
    {
        if (empty($stId)) return getReturn(-1, L('INVALID_PARAM'));
        $where = [];
        $where['st_id'] = ['in', implode(',', $stId)];
        $options = [];
        $options['where'] = $where;
        $data = [];
        $data['is_delete'] = 1;
        $options = [];
        $options['where'] = $where;
        return $this->saveData($options, $data);
    }

    /**
     * @param int $stId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-09 18:01:28
     * Desc: 获取主题信息
     * Update: 2017-10-09 18:01:33
     * Version: 1.0
     */
    public function getStInfo($stId = 0)
    {
        $stId = (int)$stId;
        $where = [];
        $where['st_id'] = $stId;
        $where['is_delete'] = -1;
        $options = [];
        $options['field'] = true;
        $options['where'] = $where;
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (empty($info)) return getReturn(-1, L('RECORD_INVALID'));
        $info['theme_title'] = json_decode($info['page_config'], 1);
        /*if (empty($info['theme_title'])) {
            return getReturn(401, '旧版本的专题页不再支持编辑,请新建新版专题页');
        }*/
        // 解析格子组件
        $info['st_content'] = empty($info['st_content']) ? '' : json_decode($info['st_content'], 1);
        // 排序
        $info['st_content'] = array_sort($info['st_content'], 'sort', 'ASC');
        // 获取组件最大ID
        $maxMId = $info['st_content'][0]['module_id'];
        foreach ($info['st_content'] as $key => $value) {
            if ($value['module_id'] > $maxMId) $maxMId = $value['module_id'];
            if ($value['module_type'] == 1) $info['type_1_key'] = $key;
            if ($value['module_type'] == 5) $info['type_5_key'] = $key;
        }
        $info['maxModuleId'] = $maxMId;
        // 获取系统功能的action
        $model = D('LinkType');
        $result = $model->getSystemLinkTypeActionArr();
        if ($result['code'] !== 200) return $result;
        $systemAction = $result['data'];
        foreach ($info['st_content'] as $key => $value) {
            // 转换系统功能的type 适用于界面上数值绑定
            foreach ($value['content']['dataset'] as $k => $val) {
                if (in_array($val['action'], $systemAction)) {
                    $info['st_content'][$key]['content']['dataset'][$k]['weburl'] = $val['action'];
                    $info['st_content'][$key]['content']['dataset'][$k]['action'] = 'system';
                }
                $type = $info['st_content'][$key]['content']['dataset'][$k]['action'];
                if (empty($type)) {
                    $info['st_content'][$key]['content']['dataset'][$k]['action'] = 'no_action';
                }
            }
        }
        return getReturn(200, '', $info);
    }

    /**
     * @param int $stId
     * @param int $storeId
     * @param int $channelId
     * @param array $data
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-13 16:53:06
     * Desc: 保存/新增 主题格子信息
     * Update: 2017-10-13 16:53:07
     * Version: 1.0
     */
    public function saveStInfo($stId = 0, $storeId = 0, $channelId = 0, $data = [])
    {
        $stId = (int)$stId;
        $storeId = (int)$storeId;
        $channelId = (int)$channelId;
        $info = [];
        $where = [];
        if ($stId > 0) {
            $where['st_id'] = $stId;
            $options = [];
            $options['where'] = $where;
            $options['field'] = true;
            $info = $this->queryRow($options)['data'];
            if (empty($info)) return getReturn(-1, L('RECORD_INVALID'));
        }
        $data['store_id'] = $storeId;
        $data['channel_id'] = $channelId;
        $data['create_time'] = empty($info['create_time']) ? NOW_TIME : $info['create_time'];
        $data['st_name'] = empty($data['theme_title']['title_name']) ? $data['st_name'] : $data['theme_title']['title_name'];
        $isOld = empty($data['theme_title']['title_name']) ? 1 : 0;
        $data['st_desc'] = $data['theme_title']['page_desc'];
        $pageConfig = json_encode($data['theme_title'], JSON_UNESCAPED_UNICODE);
        $data['page_config'] = empty($pageConfig) ? '' : $pageConfig;
        unset($data['theme_title']);
        // DOM缓存字符串
        $dom = [];
        foreach ($data['st_content'] as $key => $value) {
            // 这里为了能使用统一的方法 这里将专题单列的类型改为格子单列的类型 但其实原始数据没有改 只是在做检查的时候临时改了一下
            if ($value['module_type'] == ThemeGirdModel::MODULE_THEME) {
                $value['module_type'] = ThemeGirdModel::MODULE_ONE;
            }
            // 界面上绑定系统功能为-1 这里要进行转换
            $index = $key + 1;
            foreach ($value['content']['dataset'] as $k => $val) {
                $index2 = $k + 1;
                if ($val['action'] == 'system') {
                    $result = UtilModel::checkDatasetSystemParam($value, $val, $index, $index2);
                    if (!isSuccess($result)) return $result;
                    $data['st_content'][$key]['content']['dataset'][$k]['action'] = $val['weburl'];
                    $data['st_content'][$key]['content']['dataset'][$k]['weburl'] = '';
                }
                // action 转换旧版本的导航type
                $data['st_content'][$key]['content']['dataset'][$k]['type'] = UtilModel::actionToType($data['st_content'][$key]['content']['dataset'][$k]['action'], UtilModel::getStoreType($storeId));
                if (empty($data['st_content'][$key]['content']['dataset'][$k]['title'])) {
                    $data['st_content'][$key]['content']['dataset'][$k]['title'] = UtilModel::getParamTitle($data['st_content'][$key]['content']['dataset'][$k]['action'], $data['st_content'][$key]['content']['dataset'][$k]['weburl']);
                }
                // 检查每个参数
                $result = UtilModel::checkLinkType($data['st_content'][$key]['content']['dataset'][$k]['action'], $data['st_content'][$key]['content']['dataset'][$k]['weburl']);
                if (!isSuccess($result)) {
                    $msg = $result['msg'];
                    $result = UtilModel::getModuleError($msg, $value, $index, $index2);
                    if (!isSuccess($result)) {
                        return $result;
                    }
                }
            }

            // 如果是商品模块 独立检查数据
            if ($value['module_type'] == self::MODULE_GOODS) {
                $result = UtilModel::checkGoodsModule($storeId, $value, $index);
                if (!isSuccess($result)) {
                    return $result;
                }
            }

            // 获取每个组件的DOM编码
            $data['st_content'][$key]['dom_item'] = $this->getThemeGirdModuleDom($data['st_content'][$key], $storeId, $data['st_name'], $isOld);
            $dom[] = $data['st_content'][$key]['dom_item'];
        }
        $data['st_content'] = json_encode($data['st_content'], JSON_UNESCAPED_UNICODE);
        $data = $this->create($data);
        $domain = getStoreDomain($storeId);
        $data['st_url'] = "{$domain}/index.php?c=Page&a=specialTopic&se={$storeId}&st={$stId}";
        if (false === $data) return getReturn(-1, $this->getError());
        if ($stId > 0) {
            $options = [];
            $options['where'] = $where;
            $result = $this->saveData($options, $data);
        } else {
            $result = $this->addData([], $data);
        }
        if ($result['code'] !== 200) return $result;
        if ($stId > 0) {
            $redis = Redis::getInstance();
            $redis->del("{$stId}_html");
        } else {
            $data = [];
            $data['st_url'] = "http://{$_SERVER['HTTP_HOST']}/index.php?c=Page&a=specialTopic&se={$storeId}&st={$result['data']}";
            $where = [];
            $where['st_id'] = $result['data'];
            $options = [];
            $options['where'] = $where;
            $this->saveData($options, $data);
        }
        $info = $this->getStInfo($stId > 0 ? $stId : $result['data']);
        return getReturn(200, '', $info);
    }

    /**
     * @param array $modules 组件信息
     * @param int $storeId
     * @param string $themeName 主题名称
     * @param int $isOld 是否旧的
     * @return string '' DOM代码密文
     * User: hj
     * Date: 2017-10-13 17:32:16
     * Desc: 获取主题格子 每个组件的DOM代码
     * Update: 2017-10-13 17:32:18
     * Version: 1.0
     */
    private function getThemeGirdModuleDom($modules = [], $storeId = 0, $themeName = '', $isOld = 0)
    {
        if (empty($modules)) return '';
        $dom = '';
        switch ((int)$modules['module_type']) {
            case self::MODULE_ONE:
                if ($isOld == 1) {
                    $url = $this->getModulesDataLinkTypeUrl($modules['content']['dataset'][0], $storeId);
                    $more = $url == 'javascript:;' ? '' : '<span class="_more">更多</span>';
                    $defaultWord = '';
                    // 如果没有配图，则默认配图+文字 否则使用配图不加文字
                    if (empty($modules['content']['dataset'][0]['imgurl'])) {
                        $modules['content']['dataset'][0]['imgurl'] = "/Public/admin2/img/system/md_themeAdInfo/tName_no.png";
                    }
                    $defaultWord = '<h4>' . $themeName . '</h4>';
                    $dom = '<a class="columnT" href="' . $url . '"><img data-src="' . $modules['content']['dataset'][0]['imgurl'] . '?_750xx4">' . $defaultWord . $more . '</a>';

                } else {
                    $html = '';
                    foreach ($modules['content']['dataset'] as $key => $value) {
                        $url = $this->getModulesDataLinkTypeUrl($value, $storeId);
                        $html .= '<li class="swiper-slide">
<a href="' . $url . '" data-height="' . $modules['model_height'] . '">
<img data-src="' . $value['imgurl'] . '?_750xx4">
</a>
</li>';
                    }
                    $dom = '<div class="adBanner"><ul class="swiper-wrapper">' . $html . '</ul><ul class="swiper-pagination"></ul></div>';
                }
                break;
            case self::MODULE_TWO:
                $html = '';
                foreach ($modules['content']['dataset'] as $key => $value) {
                    $url = $this->getModulesDataLinkTypeUrl($value, $storeId);
                    $html .= '<a href="' . $url . '" data-height="' . $modules['model_height'] . '"><img data-src="' . $value['imgurl'] . '?_750xx4"></a>';
                }
                $dom = '<div class="list2 border_b1">' . $html . '</div>';
                break;
            case self::MODULE_THREE:
            case self::MODULE_FOUR:
                $html = '';
                foreach ($modules['content']['dataset'] as $key => $value) {
                    $url = $this->getModulesDataLinkTypeUrl($value, $storeId);
                    $html .= '<a href="' . $url . '" data-height="' . $modules['model_height'] . '"><img data-src="' . $value['imgurl'] . '?_750xx4"></a>';
                }
                $dom = '<div class="list' . $modules['module_type'] . '">' . $html . '</div>';
                break;
            default:
                break;
        }
        return base64_encode($dom);
    }

    /**
     * @param array $data
     * @param int $storeId
     * @return string ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-14 20:55:19
     * Desc: 获取组件的跳转方式
     * Update: 2017-10-14 20:55:20
     * Version: 1.0
     */
    private function getModulesDataLinkTypeUrl($data = [], $storeId = 0)
    {
        return UtilModel::getLinkTypeUrl($data['action'], $data['weburl'], $storeId);
    }

    /**
     * 获取专题的HTML
     *
     * 根据专题ID获取
     * @param int $stId 专题ID
     * @param int $storeId
     * @param int $channelId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-04 16:57:43
     * Update: 2017-12-04 16:57:43
     * Version: 1.00
     */
    public function getSpecialTopicHTML($stId = 0, $storeId = 0, $channelId = 0)
    {
        $redis = Redis::getInstance();
        $html = $redis->get("{$stId}_html");
        if (empty($html)) {
            $where = [];
            $where['is_delete'] = -1;
            $storeId > 0 ? $where['store_id'] = $storeId : null;
            $channelId > 0 ? $where['channel_id'] = $channelId : null;
            $where['st_id'] = $stId;
            $options = [];
            $options['where'] = $where;
            $options['field'] = 'page_config,st_content';
            $result = $this->queryRow($options);
            if ($result['code'] !== 200) return $result;
            $info = $result['data'];
            $html = "";

            /*
             * 获取页面配置
             * theme_title:{
                  title_name:'专题名称',
                  page_desc:'',  //页面描述
                  bg_color:'#eff0f4', //背景颜色
                  is_transparent:1,//背景颜色是否透明 1是，0否
                  page_gap:0,//页边间隙是否隔5px
                  img_gap:0 //图与图间隙 是否隔2px
                }
             */
            $pageConfig = json_decode($info['page_config'], 1);
            // 样式
            $class = ['theme-div', 'theme-list'];
            if ($pageConfig['img_gap'] == 1) $class[] = 'img-gap';
            if ($pageConfig['page_gap'] == 1) $class[] = 'page-gap';
            $class = implode(' ', $class);
            // 背景色
            $style = [];
            $pageConfig['is_transparent'] == 1 ?
                $style[] = 'background-color: none' : $style[] = "background-color: {$pageConfig['bg_color']}";
            $style = implode(';', $style);

            $modules = json_decode($info['st_content'], 1);
            foreach ($modules as $k => $val) {
                if ($val['status'] == 1) {
                    $html .= base64_decode($val['dom_item']);
                    if ($val['module_type'] == self::MODULE_GOODS) {
                        $dom = UtilModel::getModuleGoodsHTML($storeId, $val);
                        $html .= $dom;
                    }
                }
            }
            $div = "<div class='{$class}' style='{$style}'>{$html}</div>";
            $html = $div;
            $redis->set("{$stId}_html", $div);
        }
        return getReturn(200, '', $html);
    }
}