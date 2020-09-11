<?php

namespace app\common\lib;

use app\common\helper\ArrayHelper;
use app\common\helper\WhereHelper;
use think\db\Query;
use think\facade\Request;
use think\Model;

/**
 * Class BaseService
 * @package app\common\lib
 */
class BaseService
{

    /**
     * 当前管理的模型
     * @var Model
     */
    protected $model;

    /**
     * 搜索条件的前缀
     * @var string
     */
    protected $searchPrefix = "search_"; //搜索条件的前缀

    /**
     * 排序的字段名，客户端在这个字段上传参数指定排序 ['id,desc','edit_time,asc']
     * @var string
     */
    protected $orderFieldName = "order_field";

    protected $bankStatus = ['ALL', 'all'];

    /**
     * 设置搜索的过滤字段
     * @var array
     */
    protected $searchAllow = [

    ];


    /**
     * 根据外面传递进来的参数构造查询对象，拼接where
     * @param TpQuerySet $tpQuery
     * @return TpQuerySet
     */
    public function buildQuerySet(TpQuerySet $tpQuery)
    {

    }

    public function __construct($modelClass = null)
    {
        if ($modelClass) {
            $this->model = new $modelClass();
        } else {
            $staticClass = static::class;
            $modelClass = preg_replace('/^(.*?)service(.*?)Service$/', '$1model$2Model', $staticClass);
            if ($staticClass != $modelClass && class_exists($modelClass)) {
                $this->model = new $modelClass();
            } else {
                $tryParentClass = get_parent_class($staticClass);
                $modelClass = preg_replace('/^(.*?)service(.*?)Service$/', '$1model$2Model', $tryParentClass);
                if ($tryParentClass != $modelClass && class_exists($modelClass)) {
                    $this->model = new $modelClass();
                }
            }
        }
    }


    /**
     * 搜索查找
     * @param TpQuerySet $tpQuerySet
     * @return Query
     */
    public function search(TpQuerySet $tpQuerySet)
    {
        $tpQuerySet->setModel($this->model);
        $this->buildQuerySet($tpQuerySet);
        $query = $tpQuerySet->query();
        return $query;
    }


    /**
     * 获取当前service对应模型的主键
     * @return array|string
     */
    public function getPk()
    {
        return $this->model->getPk();
    }

    public function getTable()
    {
        return $this->model->getTable();
    }

    /**
     * 获取主表的数据
     * @return string
     */
    public function getTableKey()
    {
        return $this->model->getTable().".".$this->model->getPk();
    }

    public function getModel()
    {
        return $this->model;
    }

    /**
     * @var
     */
    protected static $_instance;

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance[static::class]) || is_null(self::$_instance[static::class])) {
            self::$_instance[static::class] = new static();
        }
        return static::$_instance[static::class];
    }

    /**
     * 根据条件更新某个模型下面的字段的值
     * @param $condition
     * @param $field
     * @param $value
     */
    public function updateField($condition, $field, $value)
    {
        $this->model->where($condition)->update([$field => $value]);
    }

    /**
     * 根据条件自增/自减数据
     * @param array $condition
     * @param $field
     * @param string $option
     * @param int $step
     * @return int|true
     * @throws \think\Exception
     */
    public function setFieldAuto($condition, $field, $option = 'inc', $step = 1)
    {
        if ($option == 'dec') {
            return $this->model->where($condition)->setDec($field, $step);
        }

        return $this->model->where($condition)->setInc($field, $step);
    }

    /**
     * 过滤搜索的条件
     *
     * @param $param
     * @return array
     */
    protected function filterSearchParam($param)
    {
        $validParam = [];
        //获取有效的数组
        foreach ($param as $key => $value) {
            if ($key == 'vf' || $key == 'vf_type' || $key == 'make_doc') {
                continue;
            }
            if (!empty($this->searchPrefix) && strpos($key, $this->searchPrefix) !== 0) { //不以前置开头
                continue;
            }
            if (!is_array($value) && trim($value) === '') {
                continue;
            }
            if (in_array($value, $this->bankStatus, true)) {
                continue;
            }
            $realKey = !empty($this->searchPrefix) ? substr($key, strlen($this->searchPrefix)) : $key;//去掉前缀获取真正的key
            if (empty($this->searchAllow)) { //为空表示设置搜索条件
                $validParam[$realKey] = $value;
            } else {
                $limit = isset($this->searchAllow[$realKey]) ? $this->searchAllow[$realKey] : [];
                if (empty($limit)) { //为空表示没设置条件
                    $validParam[$realKey] = $value;
                } elseif (is_array($limit) && count($limit) == 2) {
                    $allowValue = is_array($limit[1]) ? $limit[1] : explode(",", $limit[1]);
                    if (($limit[0] == 'in' && in_array($value, $allowValue)) ||
                        ($limit[0] == 'not in' && !in_array($value, $allowValue))
                    ) {
                        $validParam[$realKey] = $value;
                    }
                }
            }
        }
        return $validParam;
    }

    /**
     * 构造排序条件
     * @param string $orderField
     * @param string $orderSort
     * @param string $default
     * @return mixed
     */
    protected function buildSort($orderField = 'order_field', $orderSort = 'order_sort', $default = '')
    {
        $field = Request::param($orderField);
        $sort = Request::param($orderSort);
        if ($field && $sort && in_array($sort, ['asc', 'desc'])) {
            return $this->getModel()->getTable() . "." . $field . " $sort";
        }else{
            return $default;
        }
    }

    /**
     * @param $data
     * @param bool $multi
     * @return mixed
     */
    public function afterSelect(&$data, $multi = true)
    {
        if ($data && method_exists($this, 'output')) {
            if ($multi) {
                foreach ($data as &$r) {
                    $this->output($r);
                }
            } else {
                $this->output($data);
            }
        }
        return $data;
    }

    /**
     * 设置表的前缀
     * @param string $prefix
     * @return $this
     */
    public function setSearchPrefix(string $prefix = "")
    {
        $this->searchPrefix = $prefix;
        unset(self::$_instance[static::class]);
        return $this;
    }

    public function deleteIds($ids = "")
    {
        if (!$ids) {
            $ids = Request::param($this->getPk());
        } else {
            if (!is_array($ids)) {
                $ids = explode(",", $ids);
            }
        }
        $this->delete(WhereHelper::buildIdsCondition($ids, $this->getPk()));
    }

    public function delete($where)
    {
        if (!$where) {
            exception("没有删除条件");
        }
        $this->model->where($where)->delete();
    }


    /**
     * 对状态进行分组
     * @param string $field 分组的字段
     * @param array $values 值
     * @return array
     */
    protected function makeGroupInfo($field, $values)
    {
        $data = $this->model->where($field, "in", $values)
            ->group("$field")
            ->field("$field,COUNT(*) count")
            ->select()
            ->toArray();
        $all = $this->model->count(1);
        $mapData = ArrayHelper::index($data, $field);
        $return = [];
        foreach ($values as $value) {
            if (isset($mapData[$value])) {
                $return[] = $mapData[$value];
            } else {
                $return[] = [$field => $value, 'count' => 0];
            }
        }
        $return[] = [$field => 'ALL', 'count' => $all];
        return $return;
    }

    /**
     * 返回通用列表的字段
     * @return string
     */
    public static function getCommonListField()
    {
        return "*";
    }

    /**
     * 构建排序字段
     * @param $order
     * @return array|string
     */
    public function parseOrder($order)
    {
        if (!$order || !is_string($order)){
            return [];
        }
        //排序
        if ($order && strpos($order, "|") !== false) {
            list($field, $sort) = explode("|", $order);
            if (in_array($sort, ['asc', 'desc'])) {
                return [$field,$sort];
            }
        }else{
            return [];
        }
    }

}
