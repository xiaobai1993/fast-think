<?php
/**
 * Created by PhpStorm.
 * User: SmartCodeTool
 * Date: 2020/09/08
 * Time: 21:42:30
 */

namespace app\book\controller\admin;

use app\common\lib\AdminController;
use app\common\lib\TpQuerySet;
use app\book\validate\AuthorValidate;
use app\book\service\AuthorService;
use app\book\logic\AuthorParam;
use think\Request;

/**
 * author
 * Class Author
 * @package app\book\controller\admin
 */
class Author extends AdminController
{
    /**
     * 作者列表
     * @param Request $request
     * @return \think\Response\Json
     */
    public function lists(Request $request)
    {
        $param = $request->param();
        $result = AuthorService::getInstance()->lists(TpQuerySet::create(
            ['queryParam' => $param]
        ));
        return $this->json($result);
    }

    /**
     * 作者详情
     * @param Request $request
     * @return \think\Response\Json
     */
    public function detail(Request $request)
    {
        $id = $request->param('id');
        $result = AuthorService::getInstance()->setSearchPrefix('')
            ->detail(TpQuerySet::create(
                ['where' => [['id','=',$id]]]
            ));
        return $this->json($result);
    }


    /**
     * 创建作者
     * @param Request $request
     * @return \think\Response\Json
     */
    public function create(Request $request)
    {
        AuthorValidate::getInstance()->goCheck();
        $result = AuthorService::getInstance()->create(
            AuthorParam::create($request->param())
        );
        return $this->json(['id' => $result]);
    }

    /**
     * 编辑作者
     * @param Request $request
     * @return \think\Response\Json
     */
    public function update(Request $request)
    {
        AuthorValidate::getInstance()->goCheck();
        $result = AuthorService::getInstance()->update(
            AuthorParam::create($request->param())
        );
        return $this->json(['id' => $result]);
    }

    /**
     * 删除作者
     * @param Request $request
     * @return \think\Response\Json
     */
    public function delete(Request $request)
    {
        AuthorService::getInstance()->deleteIds();
        return $this->json(['msg' => '删除成功']);
    }
}