<?php
/**
 * Created by PhpStorm.
 * User: SmartCodeTool
 * Date: 2020/09/09
 * Time: 13:43:00
 */

namespace app\book\controller\admin;

use app\common\lib\AdminController;
use app\common\lib\TpQuerySet;
use app\book\validate\BookCommentValidate;
use app\book\service\BookCommentService;
use app\book\logic\BookCommentParam;
use think\Request;

/**
 * book_comment
 * Class BookComment
 * @package app\book\controller\admin
 */
class BookComment extends AdminController
{
    /**
     * 图书评论列表
     * @param Request $request
     * @return \think\Response\Json
     */
    public function lists(Request $request)
    {
        $param = $request->param();
        $result = BookCommentService::getInstance()->lists(TpQuerySet::create(
            ['queryParam' => $param]
        ));
        return $this->json($result);
    }

    /**
     * 图书评论详情
     * @param Request $request
     * @return \think\Response\Json
     */
    public function detail(Request $request)
    {
        $id = $request->param('id');
        $result = BookCommentService::getInstance()->setSearchPrefix('')
            ->detail(TpQuerySet::create(
                ['where' => [['id','=',$id]]]
            ));
        return $this->json($result);
    }


    /**
     * 创建图书评论
     * @param Request $request
     * @return \think\Response\Json
     */
    public function create(Request $request)
    {
        BookCommentValidate::getInstance()->goCheck();
        $result = BookCommentService::getInstance()->create(
            BookCommentParam::create($request->param())
        );
        return $this->json(['id' => $result]);
    }

    /**
     * 编辑图书评论
     * @param Request $request
     * @return \think\Response\Json
     */
    public function update(Request $request)
    {
        BookCommentValidate::getInstance()->goCheck();
        $result = BookCommentService::getInstance()->update(
            BookCommentParam::create($request->param())
        );
        return $this->json(['id' => $result]);
    }

    /**
     * 删除图书评论
     * @param Request $request
     * @return \think\Response\Json
     */
    public function delete(Request $request)
    {
        BookCommentService::getInstance()->deleteIds();
        return $this->json(['msg' => '删除成功']);
    }
}