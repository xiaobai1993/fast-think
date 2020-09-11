<?php

namespace app\book\model;


use think\Model;

/**
 * 图书评论
 * @property $id   评论的id
 * @property $book_id   图书的id
 * @property $content   图书评论的内容
*/

class BookCommentModel extends Model
{
    protected $table = 'book_comment';
}