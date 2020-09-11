<?php
/**
 * Created by PhpStorm.
 * User: SmartCodeTool
 * Date: 2020/09/09
 * Time: 13:43:00
 */

namespace app\book\logic;

use app\common\lib\DTO;

class BookCommentParam extends DTO
{
   
   /**
    * 评论的id
    * @var int
    */
   public $id;
   /**
    * 图书的id
    * @var int
    */
   public $book_id;
   /**
    * 图书评论的内容
    * @var string
    */
   public $content;

}