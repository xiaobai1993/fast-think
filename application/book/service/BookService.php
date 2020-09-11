<?php
/**
 * Created by PhpStorm.
 * User: SmartCodeTool
 * Date: 2020/09/08
 * Time: 21:44:25
 */
namespace app\book\service;

use app\common\lib\BaseService;
use app\common\lib\TpQuerySet;
use app\book\model\BookModel;
use app\book\logic\BookParam;
use think\Db;
use think\db\Query;

/**
 * 图书表 service
 * Class BookService
 * @package app\dbase\service
 */
class BookService extends BaseService
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
        $this->model = new BookModel();
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
            /*if($key == 'self_key'){
                //自己实现对应的condition
            }
            else*/if(in_array($key,$this->getModel()->getTableFields())){
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
            $res = BookModel::update(['sort' => $sort], $id);
            if (!$res) {
                exception($res->getError(), 100100);
            }
        }
        return ['msg' => '操作成功'];
    }

    /**
     * @param BookParam $param
     * @param int $returnType
     * @return mixed
     * @throws \Exception
     */
    public function create(BookParam $param, $returnType = 0)
    {
        Db::startTrans();
        try {
            $model = BookModel::create($param->toArr());
            Db::commit();
            return $returnType == 0 ? $model->getKey() : $model;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 更新
     * @param BookParam $param
     * @param array $where
     * @return bool
     * @throws \Exception
     */
    public function update(BookParam $param, $where = [])
    {
        Db::startTrans();
        try {
            if (!$where) {
                $where = [$this->getPk() => $param->id];
            }
            $model = BookModel::update($param->toArr(), $where);
            Db::commit();
            return $model->getKey();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

}
