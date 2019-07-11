<?php

namespace Common\Model;
/**
 * Class BrandModel
 * User: hj
 * Date: 2017-10-27 00:33:03
 * Desc: 品牌模型
 * Update: 2017-10-27 00:33:04
 * Version: 1.0
 * @package Common\Model
 */
class BrandModel extends BaseModel
{
    protected $tableName = 'mb_mallbrand';

    /**
     * @param int $storeId
     * @param int $channelId
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-27 00:37:49
     * Desc: 获取品牌列表
     * Update: 2017-10-27 00:37:50
     * Version: 1.0
     */
    public function getBrandList($storeId = 0, $channelId = 0, $condition = [])
    {
        if (empty($storeId) && empty($channelId)) return getReturn(-1, '参数错误');
        $where = [];
        if ($storeId > 0) $where['store_id'] = $storeId;
        $where['isdelete'] = 0;
        if ($channelId > 0) $where['channel_id'] = $channelId;
        $where = array_merge($where, $condition);
        $list = $this
            ->field('id,brandname')
            ->where($where)
            ->order('sort DESC,id DESC')
            ->select();
        if (false === $list) return getReturn();
        return getReturn(200, '', $list);
    }

    /**
     * 获取全部有效品牌列表
     * 因为品牌是商城有的 所以用channel_id 来找
     * @param int $channelId 渠道号
     * @param int $page 页数
     * @param int $limit 条数 默认不分页
     * @param array $condition 额外查询条件
     * @param int $type 列表的类型
     *  1-默认类型 即
     *      [
     *          ['brand_id'=>1, 'brandname'=>'xx']
     *      ]
     *  2-按首字母分类
     *      [
     *          'A' => ['brand_id'=>1, 'brandname'=>'xx']
     *      ]
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-26 16:21:20
     * Version: 1.0
     */
    public function getMallBrandList($channelId = 0, $page = 1, $limit = 0, $condition = [], $type = 1)
    {
        if ((int)$channelId <= 0) return getReturn(-1, '参数无效');
        $where = [];
        $where['channel_id'] = $channelId;
        $where['isdelete'] = 0;
        $where['isshow'] = 1;
        $where = array_merge($where, $condition);
        $field = [
            'id brand_id,brandname,logo,description'
        ];
        $field = implode(',', $field);
        $skip = ($page - 1) * $limit;
        $take = $limit;
        // hj 2017-09-29 22:52:08 排序方式
        $order = (int)$type === 1 ? 'sort DESC,id ASC' : 'brandname ASC';
        $list = $this
            ->field($field)
            ->where($where)
            ->limit($skip, $take)
            ->order($order)
            ->select();
        if (false === $list) {
            logWrite("查询品牌列表出错:" . $this->getDbError());
            return getReturn();
        }
        switch ((int)$type) {
            case 2:
                $newList = [];
                $char = 'A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z';
                $char = explode(',', $char);
                foreach ($char as $key => $value) {
                    $newList[$value] = [];
                }
                foreach ($list as $key => $value) {
                    $list[$key]['name_and_desc'] = $value['brandname'] . (empty($value['description']) ? '' : "/{$value['description']}");
                    $newList[getFirstCharter($value['brandname'])][] = $list[$key];
                }
                $list = $newList;
                break;
            default:
                break;
        }
        return getReturn(200, '', $list);
    }

    /**
     * 获取推荐的品牌列表
     * 品牌页用
     * @param int $channelId
     * @param int $page
     * @param int $limit
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-27 09:27:46
     * Version: 1.0
     */
    public function getRecBrandList($channelId = 0, $page = 1, $limit = 0)
    {
        $where = [];
        $where['isrecommend'] = 1;
        return $this->getMallBrandList($channelId, $page, $limit, $where);
    }
}