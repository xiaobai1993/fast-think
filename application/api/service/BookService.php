<?php
/**
 * Created by PhpStorm.
 * User: SmartCodeTool
 * Date: 2020/09/08
 * Time: 15:24:16
 */
namespace app\api\service;

use app\api\logic\BookSearchParam;
use app\book\model\BookModel;
use app\common\lib\TpQuerySet;
use app\book\model\AuthorModel;
use app\book\service\BookService as BaseService;
use think\db\Query;

/**
 *  service
 * Class AuthorService
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

    /**
     * 根据外面传递进来的参数构造查询对象，拼接where
     * @param TpQuerySet $tpQuery
     * @return TpQuerySet
     */
    public function buildQuerySet(TpQuerySet $tpQuery)
    {
        /**
         * @var $param BookSearchParam
         */
        $param = $tpQuery->getQueryDto();
        $condition = [];
        if ($param->id){
            $condition[] = [$tpQuery->getQueryKeyByField("id"),'=',$param->id];
        }
        if ($param->title){
            $condition[] = [$tpQuery->getQueryKeyByField("title"),'like', "%".$param->title."%"];
        }
        if ($param->press_id){
            $condition[] = [$tpQuery->getQueryKeyByField("press_id"),'=', $param->press_id];
        }
        //一对一关联搜索
        if($param->press_name){
            $tableKey = $tpQuery->getQueryKeyByField("pressData-name"); //join
            $condition[] = [$tableKey, 'like', "%$param->press_name%"]; //出版社名字查询，一对一关联表查询
        }
        if($param->author_name){
            $tableKey = $tpQuery->getQueryKeyByField("authorData-name");
            $condition[] = [$tableKey, 'like', "%$param->author_name%"]; //作者名字,多对多
        }
        if($param->author_id){
            $tableKey = $tpQuery->getQueryKeyByField("authorRelationData-author_id");
            $condition[] = [$tableKey, 'in', explode(",",$param->author_id)]; //作者名字id
            $tpQuery->setDistinctRow();

        }
        if ($param->author_count){
            $tableKey = $tpQuery->getQueryKeyByField("authorRelationData|LEFT-author_id"); //left join
            $tpQuery->setDistinctRow();
            $tpQuery->setHaving("$tableKey = $param->author_count");
        }
        if ($param->comment){
            $tableKey = $tpQuery->getQueryKeyByField("commentData-content");
            $condition[] = [$tableKey,'like',"%$param->comment%"];
            $tpQuery->setDistinctRow();
        }
        $tpQuery->setWhere($condition);
        return $tpQuery;
    }

    /**
     * 数据分页列表页，其他不同业务的列表可以重新写一个方法
     * @param TpQuerySet $querySet
     * @return \think\Paginator
     */
    public function lists(TpQuerySet $querySet)
    {
        $querySet->setField("id,title,press_id");
        //2.//设置主表字段和with关联内容
        $querySet->setField("id,title,press_id");
        //with的内容取决于业务逻辑，列表页少一些，详情页多一些，但是没有本质区别
        $querySet->setWith(['author_data' => function (Query $query) {
            return $query->hidden(['pivot']);
        }, 'pressData', 'commentData']);//作者信息，出版社信息，评论信息
        $list = $this->search($querySet)->paginate(TpQuerySet::pageSize());
        foreach ($list as $item){

            /**
             * @var $item BookModel
             */
            $item->press_data->id;//编辑器代码提示优化，点击支持跳转
        }
        return $list;
    }

    /**
     * 详情页
     * @param TpQuerySet $querySet
     * @return array|null|\PDOStatement|string|\think\Model
     */
    public function detail(TpQuerySet $querySet)
    {
        //需要啥字段自己加
        $result = $this->search($querySet)->find();
        return $result;
    }
}
