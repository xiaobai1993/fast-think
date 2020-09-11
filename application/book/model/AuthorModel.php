<?php
namespace app\book\model;

use think\Model;

/**
 * 作者表
 * @property $id   作者id
 * @property $name   作者名字
 * @property $info   基本信息
*/

class AuthorModel extends Model
{
    protected $table = 'author';
}