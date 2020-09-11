<?php
/**
 * Created by PhpStorm.
 * User: chenshenglin
 * Date: 2019/2/23
 * Time: 1:27 PM
 */

namespace app\common\enum;


use app\common\lib\BaseEnum;

class GenderEnum extends BaseEnum
{
    const M = "M";
    const F = "F";
    const UNKNOWN = "UNKNOWN";
    public static $MAP = [
        self::M => "男",
        self::F => "女",
        self::UNKNOWN => "未知",
    ];
}

