<?php

namespace app\book\model;


use think\Model;

/**
 * 图书作者关系
 * @property $id   主键id
 * @property $author_id   作者id
 * @property $book_id   图书id
 * @property $is_main   是否是主要作者 1表示是，0表示不是
*/

class AuthorBookRelationModel extends Model
{
    protected $table = 'author_book_relation';
}