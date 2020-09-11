<?php
/**
 * Created by PhpStorm.
 * User: guodong
 * Date: 2020/09/08
 * Time: 21:42:30
 */

namespace app\book\validate;;

use app\common\lib\BaseValidate;

class AuthorValidate extends BaseValidate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id|作者id' => 'require',
        'name|作者姓名' => 'require',
        'info|基本信息' => 'require',

    ];

    protected $scene = [
        'create' => [''],
        'update' => ['id']
    ];
}

/*

##

### 业务功能

添加或者修改作者

### 接口地址

`book/admin.Author/create|update`

### 请求参数

参数名称 | 参数类型 | 是否必填 | 说明
:--- | :---: | :---: | :---
id | Number | N | 作者id
name | String | N | 作者姓名
info | String | N | 基本信息


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


