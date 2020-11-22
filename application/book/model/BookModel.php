<?php

namespace app\book\model;

use think\Model;

/**
 * 图书
 * @property $id   图书id
 * @property $title   图书标题
 * @property $press_id   出版社id
 * @property PressModel   $press_data   出版社信息
 * @property AuthorModel[]   $author_data   作者信息
 * @property BookCommentModel[]   $comment_data   图书的评论
 * @property AuthorBookRelationModel[]   $author_relation_data   图书和 图书作者关联表的关联，用于搜索减少连表
*/

class BookModel extends Model
{
    protected $table = 'book';
    /**
     * 出版社信息
     * @return \think\model\relation\HasOne
     */
    public function pressData()
    {
        return $this->hasOne(PressModel::class, 'id', 'press_id');
    }

    /**
     * 作者信息
     * @return \think\model\relation\BelongsToMany
     */
    public function authorData()
    {
        $table = (new AuthorBookRelationModel())->getTable();
        return $this->belongsToMany(AuthorModel::class, $table, 'author_id', 'book_id');
    }

    /**
     * 图书的评论
     * @return \think\model\relation\HasMany
     */
    public function commentData()
    {
        return $this->hasMany(BookCommentModel::class,'book_id','id');
    }

    /**
     * 图书和 图书作者关联表的关联，用于搜索减少连表
     * @return \think\model\relation\HasMany
     */
    public function authorRelationData()
    {
        return $this->hasMany(AuthorBookRelationModel::class,'book_id','id');
    }
}