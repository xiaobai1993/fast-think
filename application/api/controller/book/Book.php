<?php
/**
 * Created by PhpStorm.
 * User: SmartCodeTool
 * Date: 2020/09/08
 * Time: 15:24:16
 */

namespace app\api\controller\book;

use app\api\service\BookService;
use app\api\logic\BookSearchParam;
use app\common\lib\BaseController;
use app\common\lib\TpQuerySet;
use think\db\Query;
use think\Request;
use app\book\model\BookModel;

/**
 * author
 * Class Author
 * @package app\book\controller\admin
 */
class Book extends BaseController
{
    /**
     * 图书列表
     * 提供给客户端的图书列表页、图书搜索页使用
     * @param Request $request
     * @return \think\Response\Json
     * @deprecated (这个接口不能满足业务需求过期了)
     */
    public function lists(Request $request)
    {
        $param = $request->param();
//        $param['status'] = 1; 需要统一加过来条件的，可以在这里加条件
        $tpQuery = new TpQuerySet();
        $queryParam = BookSearchParam::create($param);
        $tpQuery->setQueryDto($queryParam);
        $result = BookService::getInstance()->lists($tpQuery);
        return $this->json($result);
    }

    /**
     * 图书想去
     * @param Request $request
     * @return \think\Response\Json
     */
    public function detail(Request $request)
    {
        $param = $request->only('id');
        $tpQuery = new TpQuerySet();
        $queryParam = BookSearchParam::create($param);
        $tpQuery->setQueryDto($queryParam);
        $result = BookService::getInstance()->detail($tpQuery);
        return $this->json($result);
    }

    // 独立与service的 查询方式
    public function lists2()
    {
        //1.创建对象
        $queryBuilder = new TpQuerySet();
        $queryBuilder->setModel((new BookModel()));
        //2.//设置主表字段和with关联内容
        $queryBuilder->setField("id,title,press_id");
        //with的内容取决于业务逻辑，列表页少一些，详情页多一些，但是没有本质区别
        $queryBuilder->setWith(['author_data' => function (Query $query) {
            return $query->hidden(['pivot']);
        }, 'pressData', 'commentData']);//作者信息，出版社信息，评论信息

        //3.设置构造查询条件
        $where = [];
        //书的名字包含程序两个字的
        $tableKey = $queryBuilder->getQueryKeyByField("title");//主表查询
        $where[] = [$tableKey, 'like', "%程序%"];

        $tableKey = $queryBuilder->getQueryKeyByField("authorData-name");
        $where[] = [$tableKey, 'like', "%Dennis%"]; //作者名字为Dennis。多对多

        $tableKey = $queryBuilder->getQueryKeyByField("press_id");
        $where[] = [$tableKey, '=', 1]; //出版社id查询，本表的字段

        $tableKey = $queryBuilder->getQueryKeyByField("pressData-name");
        $where[] = [$tableKey, 'like', "%中国人民%"]; //出版社名字查询，一对一关联表查询
        $tableKey = $queryBuilder->getQueryKeyByField("commentData-content");
        $where[] = [$tableKey, 'like', "%好%"]; //查询评论的内容，包含好字的。

        // $queryBuilder->appendManyJoins() 其他需要自己额外手动连表的操作。
        $queryBuilder->setWhere($where);
        //4.按照评论表的id倒序，只是演示一下排序字段，自动left join功能
        $orderField = $queryBuilder->getQueryKeyByField("commentData|LEFT-id");
        $queryBuilder->setOrder("$orderField desc");

        //5.
        //根据生成的query进行后续操作，分页，查询，limit，灵活选择。
        //$list = $queryBuilder->query()->paginate();
        $list = $queryBuilder->query()->select();
        return json($list);
    }
}


