<?php
/**
 * Created by PhpStorm.
 * User: guodong
 * Date: 2020/09/08
 * Time: 21:44:25
 */

namespace app\book\validate;;

use app\common\lib\BaseValidate;

class BookValidate extends BaseValidate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id|图书id' => 'require',
        'title|图书标题' => 'require',
        'press_id|出版社id' => 'require',

    ];

    protected $scene = [
        'create' => [''],
        'update' => ['id']
    ];
}

/*

##

### 业务功能

添加或者修改图书表

### 接口地址

`book/admin.Book/create|update`

### 请求参数

参数名称 | 参数类型 | 是否必填 | 说明
:--- | :---: | :---: | :---
id | Number | N | 图书id
title | String | N | 图书标题
press_id | Number | N | 出版社id


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


