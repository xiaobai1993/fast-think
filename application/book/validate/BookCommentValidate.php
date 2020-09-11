<?php
/**
 * Created by PhpStorm.
 * User: guodong
 * Date: 2020/09/09
 * Time: 13:43:00
 */

namespace app\book\validate;;

use app\common\lib\BaseValidate;

class BookCommentValidate extends BaseValidate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id|评论的id' => 'require',
        'book_id|图书的id' => 'require',
        'content|图书评论的内容' => 'require',

    ];

    protected $scene = [
        'create' => [''],
        'update' => ['id']
    ];
}

/*

##

### 业务功能

添加或者修改图书评论

### 接口地址

`book/admin.BookComment/create|update`

### 请求参数

参数名称 | 参数类型 | 是否必填 | 说明
:--- | :---: | :---: | :---
id | Number | N | 评论的id
book_id | Number | N | 图书的id
content | String | N | 图书评论的内容


### 返回数据
```
{
"code": 0,
"msg": "success",
"data": {
"id":123214
}
}
```
*/


