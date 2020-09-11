<?php
/**
 * Created by PhpStorm.
 * User: SmartCodeTool
 * Date: 2020/09/08
 * Time: 21:44:25
 */

namespace app\book\controller\admin;

use app\common\lib\AdminController;
use app\common\lib\TpQuerySet;
use app\book\validate\BookValidate;
use app\book\service\BookService;
use app\book\logic\BookParam;
use think\Request;

/**
 * book
 * Class Book
 * @package app\book\controller\admin
 */
class Book extends AdminController
{
    /**
     * 图书表列表
     * @param Request $request
     * @return \think\Response\Json
     */
    public function lists(Request $request)
    {
        $param = $request->param();
        $result = BookService::getInstance()->lists(TpQuerySet::create(
            ['queryParam' => $param]
        ));
        return $this->json($result);
    }

    /**
     * 图书表详情
     * @param Request $request
     * @return \think\Response\Json
     */
    public function detail(Request $request)
    {
        $id = $request->param('id');
        $result = BookService::getInstance()->setSearchPrefix('')
            ->detail(TpQuerySet::create(
                ['where' => [['id','=',$id]]]
            ));
        return $this->json($result);
    }


    /**
     * 创建图书表
     * @param Request $request
     * @return \think\Response\Json
     */
    public function create(Request $request)
    {
        BookValidate::getInstance()->goCheck();
        $result = BookService::getInstance()->create(
            BookParam::create($request->param())
        );
        return $this->json(['id' => $result]);
    }

    /**
     * 编辑图书表
     * @param Request $request
     * @return \think\Response\Json
     */
    public function update(Request $request)
    {
        BookValidate::getInstance()->goCheck();
        $result = BookService::getInstance()->update(
            BookParam::create($request->param())
        );
        return $this->json(['id' => $result]);
    }

    /**
     * 删除图书表
     * @param Request $request
     * @return \think\Response\Json
     */
    public function delete(Request $request)
    {
        BookService::getInstance()->deleteIds();
        return $this->json(['msg' => '删除成功']);
    }
}