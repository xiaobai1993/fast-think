<?php

namespace app\common\lib;

use app\common\helper\WhereHelper;
use think\db\Query;
use think\facade\Request;
use think\Loader;
use think\Model;
use think\model\Relation;
use think\model\relation\BelongsTo;
use think\model\relation\BelongsToMany;
use think\model\relation\HasMany;
use think\model\relation\HasOne;

/**
 * tp5.1的查询选项配置类
 * Class TpQuerySet
 * @package app\common\lib
 */
class TpQuerySet
{
    /**
     * limit字段
     * @var string
     */
    public $limit;

    /**
     * 是否分页
     * @var bool
     */
    public $isPage = false;

    /**
     * 是否使用limit
     * @var bool
     */
    public $isLimit = false;

    /**
     * 默认的页数
     * @var int
     */
    protected $pageSize = 15;

    /**
     * with的选项
     * @var array
     */
    protected $with = [];

    /**
     * withJoin选项
     * @var array
     */
    protected $withJoin = [];

    /**
     * where条件
     * @var array
     */
    protected $where = [];

    /**
     * 查询的选项
     * @var string
     */
    protected $field = "*";


    /**
     * order field排序
     * @var
     */
    protected $orderField;
    /**
     * 排序的字段
     * @var
     */
    protected $order = [];

    /**
     * 分组字段
     * @var
     */
    protected $group;

    /**
     * 追加的属性
     * @var array
     */
    protected $append = [];

    /**
     * 关联属性
     * @var array
     */
    protected $withAttr = [];

    /**
     * 隐藏字段
     * @var
     */
    protected $hidden = [];

    /**
     * 显示字段
     * @var
     */
    protected $visible;

    /**
     * 客户端上传查询参数
     * @var
     */
    protected $queryParam = [];

    /**
     * 对应的模型的实例
     * @var Model
     */
    protected $model;


    /**
     * 去除重复的行，不用随意用group by
     * @var
     */
    protected $distinctRow = false;

    /**
     * 关联统计的数量
     * @var array
     */
    protected $withCount = [];


    /**
     * 是否使用默认的排序
     * @var bool
     */
    protected $useDefaultOrder = true;

    /**
     * 存放一对多，或者多对多的联查条件，因为withjoin只支持一对一，不支持一对多和多对多
     * @var array
     */
    protected $manyJoins = [];

    /**
     * 具体业务逻辑触发权限时候额外拼接的where条件
     * @var array
     */
    protected $authWhere = [];

    /**
     * having语句
     * @var string
     */
    protected $having = "";


    /**
     * 其他的参数
     * @var array
     */
    protected $extraParam = [];

    /**
     * 数量
     * @var int
     */
    protected $limitNumber = 0;

    /**
     * @var DTO;
     */
    protected $queryDto;

    /**
     * 每页的最大大小
     * @var int
     */
    protected $maxPageSize = 0;


    /**
     * 快速构造方法
     * @param array $config
     * @return static
     */
    public static function create(array $config = [])
    {
        return new static($config);
    }

    /**
     * 创建一个干净的实例，给复杂的分组，绘图业务用。
     * @return TpQuerySet
     */
    public static function cleanInstance()
    {
        $instance = new  TpQuerySet();
        $instance->setOrder("");
        $instance->setUseDefaultOrder(false);
        $instance->setField("");
        return $instance;
    }

    /**
     * 初始化时传递一些参数，设置配置
     *
     * TpQuerySet constructor
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            if (property_exists(self::class, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * 设置查询的dto
     * @param DTO $dto
     */
    public function setQueryDto(DTO $dto)
    {
        $this->queryDto = $dto;
    }

    /**
     * @return DTO
     */
    public function getQueryDto()
    {
        return $this->queryDto;
    }

    public function getWhere()
    {
        return $this->where;
    }

    public function setWhere($where)
    {
        $this->where = array_merge($this->where, $where);
        return $this;
    }

    public function setLimit($page, $size)
    {
        $this->limit = ($page - 1) * $size . ',' . $size;
        return $this;
    }

    public function setWith($with)
    {
        $this->with = array_merge($this->with, $with);
        return $this;
    }

    public function getWith()
    {
        return $this->with;
    }

    public function setGroup($group)
    {
        $this->group = $group;
        return $this;

    }

    public function getGroup()
    {
        return $this->group;
    }

    public function setWithJoin($withJoin)
    {
        return $this->withJoin = $withJoin;
    }

    public function getWithJoin()
    {
        return $this->withJoin;
    }

    public function setField($field, $isAppend = false)
    {
        if ($isAppend == false) {
            $this->field = $field;
        } else {
            if ($this->field){
                $this->field = $this->field . "," . $field;
            }else{
                $this->field = $field;
            }
        }
        return $this;
    }

    /**
     * 获取field的原始的值
     * @return string
     */
    public function getOriginField()
    {
        return $this->field;
    }

    public function getField($hasTable = true)
    {
        $field = WhereHelper::buildFields($this->field, $hasTable == true ? $this->model->getTable() : "");
        if ($hasTable && $field && $this->getDistinctRow()) {
            return "DISTINCTROW " . $field;
        }
        return $field;
    }

    public function getRawField()
    {
        return $this->field;
    }

    public function getAppend()
    {
        $returnAppend = [];
        foreach ($this->append as $item) {
            $returnAppend[] = Loader::parseName($item, 0, false);
        }
        return $returnAppend;
    }

    public function setAppend($append)
    {
        $this->append = is_array($append) ? $append : explode(",", $append);
        return $this;

    }

    public function setWithAttr($withAttr)
    {
        $this->withAttr = $withAttr;
        return $this;
    }

    public function getWithAttr()
    {
        return $this->withAttr;
    }

    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
        return $this;
    }

    public function getHidden()
    {
        return $this->hidden;
    }

    public function setVisible($visible)
    {
        $this->visible = $visible;
        return $this;
    }

    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * @param $order
     */
    public function setOrder($order)
    {
        if (is_string($order)) {
            $orders = explode(",", $order);
        } else {
            $orders = (array)$order;
        }
        foreach ($orders as $item) {
            //兼容case语句
            if (preg_match('/^([\s\S]*?)(desc|DESC)/', $item, $matches)) {
                $this->order[trim($matches[1])] = 'desc';
            } else if (preg_match('/^([\s\S]*?)(asc|ASC)/', $item, $matches)) {
                $this->order[trim($matches[1])] = 'asc';
            }
        }
        return $this;
    }

    public function getOrder($sortField = 'sortField', $sortOrder = 'sortOrder')
    {
        if (empty($this->order)) {
            $sort = Request::param($sortField);
            $order = Request::param($sortOrder);
            if (!empty($sort) && !empty($order)) {
                return $sort . " " . $order;
            }
        } else {
            $items = [];
            foreach ($this->order as $field => $sort) {
                $items[] = $field . " " . $sort;
            }
            return implode(",",$items);
        }
        if (!$this->order) {
            return "";
        }
        return $this->order;
    }

    public static function getRequestParam($name = '')
    {
        return Request::param($name);
    }

    public static function getRequestOnly($name = '')
    {
        if ($name) {
            return Request::only($name);
        } else {
            return Request::param();
        }
    }

    public static function pageSize($isLimit = true, $field = 'page_size')
    {
        if (Request::param(AutoDocument::$flag_field) >= 1) {
            return 1;
        }
        $page = Request::param($field) ? Request::param($field) : 15;
        if ($isLimit == true) {
            return $page > 20 ? 20 : $page;
        }
        return $page;

    }

    public function getPageSize($field = "page_size")
    {
        if (Request::param(AutoDocument::$flag_field) >= 1) {
            return 1;
        }
        $page = Request::param($field) ? Request::param($field) : $this->pageSize;
        $maxPageSize = $this->getMaxPageSize();
        if ($maxPageSize != 0 && $maxPageSize < $page) {
            return $maxPageSize;
        } else {
            return $page;
        }
    }

    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
    }

    public function setQueryParam(array $qParam)
    {
        $this->queryParam = array_merge($this->queryParam, $qParam);
        return $this;
    }

    public function getQueryParam()
    {
        return $this->queryParam;
    }

    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    /**
     * 调整with或者withJoin
     *
     * @param $key
     * @return string
     */
    public function getQueryKeyByField($key)
    {
        return $this->processWithQueryParam($key, $this->getModel()->getTable());
    }

    /**
     * 该函数根据客户端传递进来的参数，自动将with操作转换为withJoin
     *
     * @param  string $key 客户端传递的参数字段，如果需要使用withJoin，格式 `withData`-field，withData是with属性的驼峰命名，field是查询的字段，中间用'-'连接
     * @param  string $mainTable 表名
     * @return string
     */
    protected function processWithQueryParam($key, $mainTable)
    {
        if (strpos($key, "-") !== false) {
            list($joinTable, $field) = explode("-", $key); // 带有-符号的表示要连表查询，根据-符号拆分表名和字段
            if (strpos($joinTable, "|") !== false) {
                $method = explode("|", $joinTable)[0];
            } else {
                $method = $joinTable;
            }
            if (method_exists($this->model, $method)) { //模型的关联方法存在
                $returnTable = $this->autoJoin($joinTable);
                if ($returnTable == $method) {
                    return $method . "." . $field;
                }
            }
        }
        return $mainTable . "." . $key;
    }

    /**
     * 过滤withjoin，改用手动连表
     */
    public function filterWithJoin()
    {
        if (!$this->withJoin) {
            return;
        }
        foreach ($this->withJoin as $key => $value) {
            if (is_callable($value)) {
                $this->autoJoin($key);
            } else {
                $this->autoJoin($value);
            }
        }
    }

    /**
     *  order里面的字段自动连表
     */
    public function makeOrderJoin()
    {
        foreach ($this->order as $field => $sort) {
            if (strpos($field, ".")) {
                list($joinTable, $field) = explode('.', $field, 2);
                if (strpos($joinTable, "_") === false) {
                    $this->autoJoin($joinTable);
                }
            }
        }
    }

    /**
     * 设置withCount
     * @param $withCount
     * @return $this
     */
    public function setWithCount(array $withCount)
    {
        $this->withCount = array_merge($this->withCount, $withCount);
        return $this;
    }

    /**
     * 获取withCount
     * @return array
     */
    public function getWithCount()
    {
        return implode(",",$this->withCount);
    }

    /**
     * 自动连表
     * @param $joinTable
     * @return string
     */
    protected function autoJoin($joinTable)
    {
        if (strpos($joinTable, "|")) {
            list($joinTable, $joinType) = explode("|", $joinTable, 2);
        } else {
            $joinType = "INNER";
        }
        if (!method_exists($this->model,$joinTable)){
            return;
        }
        $relation = $this->model->$joinTable();
        $mainTable = $this->model->getTable();
        if ($relation instanceof Relation) { //调用后返回的是relation对象
            $realJoinTable = $relation->getModel()->getTable();
            $foreignKey = $relation->getForeignKey();
            $localKey = $relation->getLocalKey();
            if ($relation instanceof HasOne) {
                $joinCond = "(" . $joinTable . "." . $foreignKey . "=" . $mainTable . "." . $localKey . ")";
                $this->appendManyJoins($realJoinTable . " " . $joinTable, $joinCond, $joinType);
            } elseif ($relation instanceof BelongsTo) {
                $joinCond = "(" . $joinTable . "." . $localKey . "=" . $mainTable . "." . $foreignKey . ")";
                $this->appendManyJoins($realJoinTable . " " . $joinTable, $joinCond, $joinType);
            } elseif ($relation instanceof HasMany) { //一对多
                $relationSql = $relation->buildSql();
                $matches = [];
                preg_match("#WHERE(.*?)\)$#", $relationSql, $matches);
                $joinCond = "(" . $joinTable . "." . $foreignKey . "=" . $mainTable . "." . $localKey . ")";
                if ($matches) {
                    $joinCond = $joinCond . " AND " . "(" . $matches[1] . ")";
                }
                $this->appendManyJoins($realJoinTable . " " . $joinTable, $joinCond, $joinType);
            } elseif ($relation instanceof BelongsToMany) {
                $middleName = $relation->getMiddle();
                $middleAlias = $mainTable . "_" . $relation->getMiddle() . "_" . $joinTable;
                //防止中间表在一次查询里面重名
                $relationSql = $relation->buildSql();
                $matches = [];
                preg_match("#WHERE(.*?)\)#", $relationSql, $matches);
                $relationWhere = "";
                if ($matches) {
                    $relationWhere = str_replace('pivot', $middleAlias, $matches[1]);
                }
                $anotherModel = $relation->getModel();
                if (!$relationWhere) {
                    $joinMidCond = "(" . $mainTable . "." . $this->model->getPk() . "=" . $middleAlias . "." . $localKey . ")";
                } else {
                    $joinMidCond = "(" . $mainTable . "." . $this->model->getPk() . "=" . $middleAlias . "." . $localKey . " AND $relationWhere)";
                }
                $this->appendManyJoins($middleName . " " . $middleAlias, $joinMidCond, $joinType);
                $joinMidCond2 = $joinTable . "." . $anotherModel->getPk() . "=" . $middleAlias . "." . $foreignKey;
                $this->appendManyJoins($anotherModel->getTable() . " " . $joinTable, $joinMidCond2, $joinType);
            } else {
                return $mainTable;
            }
            return $joinTable;
        }
        return $joinTable;
    }

    /**
     * 查询条件
     *
     * @param $key
     * @param $value
     * @param string $op
     * @return array
     */
    public static function buildCond($key, $value, $op = '=')
    {
        if (is_array($value)) {
            return [$key, 'in', $value];
        } else {
            if (preg_match('/^.*?_at$/', $key) && strpos($value, ' ~ ')) {
                return WhereHelper::timestampInterval($value, $key);
            } elseif (is_numeric($value) && (strpos($key, 'id') !== false) || strpos($key, 'is') !== false) {
                return [$key, $op, $value];
            } elseif ((strpos($value, 'null') !== FALSE)) {
                return [$key, "$value"];
            } elseif (is_string($value)) {
                return [$key, $op, "$value"];
            }
            return [$key, $op, $value];
        }
    }

    /**
     * 获取关联的名字
     * @param string $relation
     * @return mixed
     */
    protected function getModelRelations($relation = '')
    {
        $relations = $this->model->with($this->getWith())
            ->find()->getRelation($relation);
        $this->model->removeOption();
        return $relations;
    }

    public function getManyJoins()
    {
        $joinArray = [];
        foreach ($this->manyJoins as $joinTable => $joinItem) {
            $joinWhere = $joinItem[0];
            $joinType = $joinItem[1];
            $joinArray[] = [$joinTable, $joinWhere, $joinType];
        }
        return $joinArray;
    }

    public function appendManyJoins($joinTable, $condition, $joinType = "INNER", $override = false)
    {
        if (isset($this->manyJoins[$joinTable]) && $override == false) {
            $this->manyJoins[$joinTable][] = [$condition, $joinType];
        } else {
            $this->manyJoins[$joinTable] = [$condition, $joinType];
        }
    }

    public function setAuthWhere(array $where)
    {
        $this->authWhere = array_merge($this->authWhere, $where);
        return $this;
    }

    public function getAuthWhere()
    {
        return $this->authWhere;
    }


    public function filterAuthWhere()
    {
        if (empty($this->authWhere)) {
            return;
        }
        foreach ($this->authWhere as $where) {
            list($key, $op, $value) = $where;
            if ($key && strpos($key, '.') !== false) {
                list($table, $filed) = explode(".", $key);
                $this->autoJoin($table);
            }
        }
    }

    public function setExtraParam(array $param)
    {
        $this->extraParam = $param;
        return $this;
    }

    public function getExtraParam($value = '')
    {
        if (!$value) {
            return $this->extraParam;
        }
        return $this->extraParam[$value]??"";
    }

    public function setLimitNumber($limitNumber)
    {
        $this->limitNumber = $limitNumber;
        return $this;
    }

    public function getLimitNumber()
    {
        return $this->limitNumber;
    }

    public function setHaving($having)
    {
        $this->having = $having;
        return $this;
    }

    public function getHaving()
    {
        return $this->having;
    }

    /**
     * 便利的返回查询对象的实例
     * @param BaseModel|null $model
     * @return Query
     */
    public function query(BaseModel $model = null)
    {
        if (!$this->model && $model) {
            $this->model = $model;
        }
        return $this->queryWithSet();
    }

    public function setUseDefaultOrder($value)
    {
        $this->useDefaultOrder = $value;
        return $this;
    }

    public function getUseDefaultOrder()
    {
        return $this->useDefaultOrder;
    }

    public function setDistinctRow($value = true)
    {
        if($value == true){
            $mainTableKey = $this->getModel()->getTable() . "." . $this->getModel()->getPk();
            $this->setGroup($mainTableKey);
        }else{
            $this->setGroup("");
        }
        return $this;
    }

    public function getDistinctRow()
    {
        return $this->distinctRow;
    }

    public function setMaxPageSize($maxPageSize)
    {
        $this->maxPageSize = $maxPageSize;
    }

    public function getMaxPageSize()
    {
        return $this->maxPageSize;
    }

    public function setOrderField(array $value,$key = ''){

        if (!$value){
            return;
        }
        if($this->model && ! $key){
            $key = $this->model->getTable().".".$this->model->getPk();
        }
        $this->orderField = "FIELD(".$key.",".implode(",",$value).")";
    }

    public function getOrderField()
    {
        return $this->orderField;
    }


    public function queryWithSet()
    {
        $query = $this->model->db();
        $query = $query
            ->field($this->getField())
            ->append($this->getAppend())
            ->withAttr($this->getWithAttr());


        if ($this->getWithCount()) {
            $query = $query->withCount($this->getWithCount());
        }

        //自动根据order的条件连表
        $this->makeOrderJoin();
        $this->filterWithJoin();

        $manyJoins = $this->getManyJoins();
        if ($manyJoins) {
            foreach ($manyJoins as $join) {
                $query = $query->join($join[0], $join[1], $join[2]);
            }
        }
        //对于已经hidden的属性，就没有必要with查询，只需要拼接连表语句
        $hideAttr = $this->getHidden();
        $realWith = [];

        foreach ($this->getWith() as $withKey => $withItem) {
            if (in_array($withKey, $hideAttr, true) || in_array($withItem, $hideAttr, true)) {
                continue;
            }
            $realWith[$withKey] = $withItem;
        }

        $query = $query
            ->where($this->getWhere())
            ->with($realWith)
            ->group($this->getGroup())
            ->having($this->getHaving());

        $order = $this->getOrder();

        $pkID = $this->getModel()->getTable() . "." . $this->getModel()->getPk();

        if ($order) {
            if (strpos($order, "$pkID desc") === false && $this->getUseDefaultOrder()) {
                $order = $order . "," . ($pkID) . " desc";
            }
        } elseif ($this->getUseDefaultOrder()) {
            $order = $pkID . " desc";
        }
        if (strpos($order, "CASE") !== false) {
            $query = $query->orderRaw($order);
        } elseif ($order) {
            $query = $query->order($order);
        }

        if ($this->getHidden()) {
            $query->hidden($this->getHidden());
        }
        if ($this->getVisible()) {
            $query->visible($this->getVisible());
        }

        return $query;
    }
}

