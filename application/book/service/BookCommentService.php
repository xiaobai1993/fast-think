<?php
/**
 * Created by PhpStorm.
 * User: SmartCodeTool
 * Date: 2020/09/09
 * Time: 13:43:00
 */
namespace app\book\service;

use app\common\lib\BaseService;
use app\common\lib\TpQuerySet;
use app\book\model\BookCommentModel;
use app\book\logic\BookCommentParam;
use think\Db;
use think\db\Query;

/**
 * 图书评论 service
 * Class BookCommentService
 * @package app\dbase\service
 */
class BookCommentService extends BaseService
{
    /**
     * 搜索条件的前缀
     *
     * @var string
     */
    protected $searchPrefix = "";


    public function __construct($modelClass = null)
    {
        parent::__construct($modelClass);
        $this->model = new BookCommentModel();
    }

    /**
     * 根据外面传递进来的参数构造查询对象，拼接where
     * @param TpQuerySet $tpQuery
     * @return TpQuerySet
     */
    public function buildQuerySet(TpQuerySet $tpQuery)
    {
        // 获取传入参数
        $param = $tpQuery->getQueryParam();
        // 过滤搜索条件
        $param = $this->filterSearchParam($param);
        $condition = [];
        foreach ($param as $key => $value) {
            if(in_array($key,$this->getModel()->getTableFields())){
                $tableKey = $tpQuery->getQueryKeyByField($key);
                $condition[] = TpQuerySet::buildCond($tableKey, $value);
            }
        }
        $tpQuery->setWhere($condition);
        return $tpQuery;
    }

    /**
     * 数据分页列表页
     * @param TpQuerySet $querySet
     * @return \think\Paginator
     */
    public function lists(TpQuerySet $querySet)
    {
        $list = $this->search($querySet)->paginate(TpQuerySet::pageSize());
        return $list;
    }

    /**
     * 详情页
     * @param TpQuerySet $querySet
     * @return array|null|\PDOStatement|string|\think\Model
     */
    public function detail(TpQuerySet $querySet)
    {
        $result = $this->search($querySet)->find();
        return $result;
    }

    /**
     * 排序
     *
     * @param $sorts
     * @return array
     */
    public function sort($sorts)
    {
        foreach ($sorts as $id => $sort) {
            $res = BookCommentModel::update(['sort' => $sort], $id);
            if (!$res) {
                exception($res->getError(), 100100);
            }
        }
        return ['msg' => '操作成功'];
    }

    /**
     * @param BookCommentParam $param
     * @param int $returnType
     * @return mixed
     * @throws \Exception
     */
    public function create(BookCommentParam $param, $returnType = 0)
    {
        Db::startTrans();
        try {
            $model = BookCommentModel::create($param->toArr());
            Db::commit();
            return $returnType == 0 ? $model->getKey() : $model;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 更新
     * @param BookCommentParam $param
     * @param array $where
     * @return bool
     * @throws \Exception
     */
    public function update(BookCommentParam $param, $where = [])
    {
        Db::startTrans();
        try {
            if (!$where) {
                $where = [$this->getPk() => $param->id];
            }
            $model = BookCommentModel::update($param->toArr(), $where);
            Db::commit();
            return $model->getKey();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

}
