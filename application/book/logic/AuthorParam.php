<?php
/**
 * Created by PhpStorm.
 * User: SmartCodeTool
 * Date: 2020/09/08
 * Time: 21:42:30
 */

namespace app\book\logic;

use app\common\lib\DTO;

class AuthorParam extends DTO
{
   
   /**
    * 作者id
    * @var int
    */
   public $id;
   /**
    * 作者姓名
    * @var string
    */
   public $name;
   /**
    * 基本信息
    * @var string
    */
   public $info;

}