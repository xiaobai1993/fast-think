## 图书想去
### 业务功能


### 接口地址 (GET)
`/api/book.book/detail` 

### 非路由地址（后台开发用）

`api/book.book/detail`
 
>测试地址 [http://tp.test.cn/api/book.book/detail?id=3](http://tp.test.cn/api/book.book/detail?id=3)

### 请求参数
参数名称 |是否必填 |参数类型 | 案例 | 说明
:--- | :---: | :---: | :--- | :---
id|Y|Number|例如:`3`|图书id

### 返回数据
```
{
    "code": 0,
    "msg": "success",
    "data": {
        "id": 3,
        "title": "C程序设计语言",
        "press_id": 1
    }
}
```

参数名称 | 参数类型 | 说明
:--- | :---: | :---
code|Number|编码
msg|String|提示信息
data|Object|数据
data.id|Number|   图书id
data.title|String|   图书标题
data.press_id|Number|   出版社id