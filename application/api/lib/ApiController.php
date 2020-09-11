<?php

namespace app\api\lib;

use app\common\helper\JsonStringHelper;
use app\common\lib\BaseController;

class ApiController extends BaseController
{
    public function initialize()
    {
        parent::initialize();
        //关闭模板view_base
        $this->view->config('view_base', '');
    }

    /**
     * 返回json格式数据
     *
     * @param array $data
     * @param string $msg
     * @param int $code
     * @return \think\Response\Json
     */
    protected function json($data = [], $msg = 'success', $code = 0)
    {
        return JsonStringHelper::jsonReturn($data, $msg, $code);
    }
}