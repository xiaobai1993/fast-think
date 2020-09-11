<?php
namespace app\book\model;

use think\Model;

/**
 * @property $id   出版社id
 * @property $name   出版社名字
*/

class PressModel extends Model
{
    protected $table = 'press';
}