<?php

namespace app\common\helper;

use app\common\lib\AutoDocument;
use think\facade\Request;

/**
 * Class Strings
 * @package app\common\lib
 */
class JsonStringHelper
{
    /**
     * json数据返回全局默认数据
     *
     * @var array
     */
    protected static $jsonReturn = [];


    /**
     * 设置全局默认json返回数据
     *
     * @param array|string $data
     * @param string $key data|msg
     */
    public static function setJsonReturn($data, $key = 'data')
    {
        if ($key == 'data') {
            self::$jsonReturn['data'] = isset(self::$jsonReturn['data'])
                ? array_merge(self::$jsonReturn['data'], $data)
                : $data;
        } else {
            self::$jsonReturn[$key] = $data;
        }
    }

    /**
     * 返回json格式数据
     *
     * @param array $data
     * @param string $msg
     * @param int $code
     * @return \think\Response\Json
     */
    public static function jsonReturn($data = [], $msg = '', $code = 0)
    {
        $defaults = self::$jsonReturn;
        $data = (isset($defaults['data']) ? array_merge($defaults['data'], $data) : $data);

        $outPutData = [
            'code' => $code,
            'msg' => $msg ?: (isset($defaults['msg']) ? $defaults['msg'] : ''),
        ];

        $outPutData['data'] = $data;
        if (Request::param(AutoDocument::$flag_field) >= 1) {
            (new AutoDocument())->createDocument($outPutData);
        }

        return \think\response\Json::create($outPutData);
    }
}
