<?php
/**
 * Created by PhpStorm.
 * User: SmartCodeTool
 * Date: 2020/09/08
 * Time: 15:24:16
 */

namespace app\api\logic;

use app\common\lib\DTO;

class BookSearchParam extends DTO
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

    /**
     * 出版社名称
     * @var
     * @deprecated (属性字段过期测试)
     */
    public $press_name;

    /**
     * 图书作者名
     * @var
     */
    public $author_name;

    /**
     * 图书作者id
     * @var
     */
    public $author_id;

    /**
     * 图书作者数量
     * @var
     */
    public $author_count;

    /**
     * 图书的评论
     * @var
     */
    public $comment;

}