## ~~图书列表~~(这个接口不能满足业务需求过期了)
### 业务功能
提供给客户端的图书列表页、图书搜索页使用

### 接口地址 (GET)
`/api/book.book/lists` 

### 非路由地址（后台开发用）

`api/book.book/lists`
 
>测试地址 [http://tp.test.cn/api/book.book/lists?title=C%E7%A8%8B%E5%BA%8F&author_id=4&press_name=%E4%BA%BA%E6%B0%91%E5%87%BA%E7%89%88&comment=%E4%B8%8D%E9%94%99](http://tp.test.cn/api/book.book/lists?title=C%E7%A8%8B%E5%BA%8F&author_id=4&press_name=%E4%BA%BA%E6%B0%91%E5%87%BA%E7%89%88&comment=%E4%B8%8D%E9%94%99)

### 请求参数
参数名称 |是否必填 |参数类型 | 案例 | 说明
:--- | :---: | :---: | :--- | :---
title|Y|String|例如:`C程序`|图书标题
author_id|N|Number|例如:`4`|图书作者id
press_name|N|String|例如:`人民出版`|~~出版社名称~~(属性字段过期测试)
comment|N|String|例如:`不错`|图书的评论

### 返回数据
```
{
    "code": 0,
    "msg": "success",
    "data": {
        "total": 1,
        "per_page": 1,
        "current_page": 1,
        "last_page": 1,
        "data": [
            {
                "id": 3,
                "title": "C程序设计语言",
                "press_id": 1,
                "author_data": [
                    {
                        "id": 3,
                        "name": "Dennis M. Ritchie",
                        "info": "C语言发明者"
                    },
                    {
                        "id": 4,
                        "name": "Brian W. Kernighan",
                        "info": "计算机专家"
                    }
                ],
                "press_data": {
                    "id": 1,
                    "name": "中国人民出版社"
                },
                "comment_data": [
                    {
                        "id": 1,
                        "book_id": 3,
                        "content": "非常不错的C语言教材"
                    },
                    {
                        "id": 2,
                        "book_id": 3,
                        "content": "太好了"
                    }
                ]
            }
        ]
    }
}
```

参数名称 | 参数类型 | 说明
:--- | :---: | :---
code|Number|编码
msg|String|提示信息
data|Object|数据
data.total|Number|总数
data.per_page|Number|每页大小
data.current_page|Number|当前页
data.last_page|Number|最后页码
data.data|Array|数据
data.data.id|Number|   图书id
data.data.title|String|   图书标题
data.data.press_id|Number|   出版社id
data.data.author_data|Array(Author)|   作者信息
data.data.author_data.id|Number|   作者id
data.data.author_data.name|String|   作者名字
data.data.author_data.info|String|   基本信息
data.data.press_data|Object(Press)|   出版社信息
data.data.press_data.id|Number|   出版社id
data.data.press_data.name|String|   出版社名字
data.data.comment_data|Array(BookComment)|   图书的评论
data.data.comment_data.id|Number|   评论的id
data.data.comment_data.book_id|Number|   图书的id
data.data.comment_data.content|String|   图书评论的内容