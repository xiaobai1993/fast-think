<?php
/**
 * Created by PhpStorm.
 * User: SmartCodeTool
 * Date: 2020/09/08
 * Time: 21:44:25
 */

namespace app\book\logic;

use app\common\lib\DTO;

class BookParam extends DTO
{
   
   /**
    * 图书id
    * @var int
    */
   public $id;
   /**
    * 图书标题
    * @var string
    */
   public $title;
   /**
    * 出版社id
    * @var int
    */
   public $press_id;

}