### fast-think简介

fast-think是基于thinkphp封装的，用于快速高效的进行业务开发的模式，包含了自定义的command命令、自定义查询构造器，文档生成器等等。仅仅需要一条MySQL建表语句，代码、注释支持一键自动生成，接口文档实时自动生成。亲自尝试下，感受下前所未有的速度吧!(该框架是个人对以往业务的总结、自己设计的，`也许不能适应所有的场景，但是大多数业务场景开发速度一定是最快的`)，本工程代码可以任意更改，如果对你有帮助、希望给个star，不想看文档的可以直接先划到最后看效果图。github加载比较慢，可以看码云地址 https://gitee.com/xiaobai1993/fast-think

### fast-think 详细介绍

#### 需求驱动

主要是工作中一直用thinkphp，但是随着业务复杂代码越来越难以维护，仅仅根据tp的官方手册已经找不到对代码进行改进的地方了。所以有必要基于实用的角度反思、总结、设计一套新的开发模式，让开发更高效，快捷，减少代码维护成本。详细内容可以看里, https://www.jianshu.com/p/b6817c75804d ，内容较多，可以跳过。


#### 如何用到自己的项目

- 如果项目是新的，完全可以以当前项目为骨架，在上面进行开发，希望fork一份thinkphp5.1的代码，做下文修改，以后composer install 自己修改过的（大部分公司都是fork后自己用的）。

- 如果项目已经存在，可以借鉴下模块的职责划分，可以将某些类直接复制到自己的工程使用。如果采用了ORM查询，相信项目的TPQuerySet
会大大简化你的查询逻辑。

#### 对比原生的thinkphp有何提升

- 更加明确了模块的含义，解决了MVC模式代码臃肿的问题，引入了Service、Enum，DTO。简化了Validate操作。
- 封装了通用的查询类TPQuerySet，简化了查询。一行代码调用，自动join、自动解决alias重命问题，只需要关心关联的定义，不用在手动join。
- 提供了一套实际可行的开发模式，提供了代码生成器、模型属性生成器、文档实时生成器，大大提升开发效率。

#### 框架架构

![7581599994844_.pic_hd.jpg](https://github.com/xiaobai1993/fast-think/blob/master/doc/struct.jpg)

- module：模块，一般按照业务划分，比如案例里面的book就可以认为是图书模块，其他的可以有user、shop等等。这些模块的controller/admin 放后台的业务功能。模块相关的对客户端用户提供的功能都在api下。比如案例里面的api/book是图书模块对客户端的接口。

- service：存放业务逻辑代码，默认的service会引用一个model，用来提供对model相关的增删改查，service之间可以相互调用。位于相关模块的service承担后台相关的业务，api模块下的service要继承对应的后台service，可以将一些后台和api都用的方法写在后台service。

- model:对应一张数据，全局唯一，里面不允许写业务逻辑代码，只允许定义orm关联关系的方法、获取器/修改器，动态属性、重写model自带的方法。

- controller:对外提供api接口，不允许直接调用模型，只允许调用service。

- validate:验证器，尽量避免实例化相应的类，应在controller里面，统一调用gocheck方法，自动验证。

- logic:存放业务上下文之间传递的param类，比如创建一条数据表数据，根据客户端提交的信息实例化对应的类，存入数据库。业务之间应该尽量避免实用array/dict做参数传递，应该根据场景定义param类，需要不同param数据转换的，可以在具体的param类定义相关方法用于param转换。

- enum:一些枚举类，用于定义一些枚举，也可以考虑将枚举的值写在service里面，用const表示。但是BaseEnum功能更强大，支持根据enum值实例化对应的enum类对象，可以以enum类做参数进行传递，代码可读性会更好。



#### 对thinkphp代码的改动

1.  thinkphp/library/think/model/Relation.php 

增加下面这两个方法，TPQuerySet类需要用来确定连表条件

```
/**
 * 获取关联表外键
 * @access public
 * @return String
 */
public function getForeignKey()
{
    return $this->foreignKey;

/**
 * 获取关联表主键
 * @access public
 * @return String
 */
public function getLocalKey()
{
    return $this->localKey;
}
```
2.thinkphp/library/think/model/relation/BelongsToMany.php 

注释掉 protected function baseQuery()方法, 否则会影响多对多搜索

增加
```
    /**
     * 获取中间表的名称或者别名
     * @return string
     */
    public function getMiddle()
    {
        return $this->middle;
    }
```

### 功能测试

下载本工程到本地，用nginx启动项目，nginx增加配置
```
location / { 
   if (!-e $request_filename) {
   		rewrite  ^(.*)$  /index.php?s=/$1  last;
    }
}
```
将init.sql里面包含的sql语句导入本地测试数据库，然后修改config目录下的database.php数据库的连接信息与本地匹配。案例提供了一个book后台模块，和在api模块下的图书列表页接口。比如我需要在book模块下生成和book表相关的业务，我仅仅需要拷贝book表的创建语句、放在auto_code.sql文件，然后在终端执行
```
php think auto_code book
```
注释、代码就生成完毕了，就是这么简单。对于列表页来说，如果需要增加筛选条件，只要修改service的`buildQuerySet`的内容即可。后台的代码`buildQuerySet`里面获取客户端提交的参数的时候用了dict接收，因为后台一般筛选规则比较固定，用dict可以在循环里面方便的进行处理，不用逐个if判断。但是这样写也会导致文档生成器无法自动生成对请求参数的含义相关内容。所以api模块下提供了另一种以param对象传递的形式，个人偏向于这一种方式。book/controller/admin下的Author、Book、BookComment都是采用这种方式生成的。

如上基本代码完成，下面看一眼需求是什么样子的，这里以api为例，假设需要在book/lists接口，需要返回图书的基本信息，还要包含作者的信息、出版社的信息等等，在BookModel定义相关的关联关系，方法名以Data结尾（文档生成器会自动注释），然后在service里面对应的lists方法，用TpQuerySet提供的setField、setWith方法去设置一下就ok了。如果需要关联查询、搜索？不用担心，TpQuerySet提供了`getQueryKeyByField`方法，获取任何数据表字段都用这个方法，它对连表操作做了封装，自动帮你根据model定义的关联关系自动连表、自动解决可能带来的表名冲突问题。你只需要关心该字段的业务条件，至于是否需要连表、如何连表，你不用关系，一切交给它就好了，所有的筛选条件都是下面这个样子，if里面不仅仅可以构建where条件，其他的需要根据对业务做差异化处理的也可以写进去，比如having，group by等等。

```
   if($param->press_name){
        $tableKey = $tpQuery->getQueryKeyByField("pressData-name");
        $condition[] = [$tableKey, 'like', "%$param->press_name%"]; 
   }
```

php是一门弱类型的语言，模型的属性都是动态属性，如何提示开发体验呢，让编辑器对代码进行提示呢，比如刚刚在book定义了一些关联方法，希望编辑器能够友好一些。

```
     /**
     * @var $item BookModel
     */
     $item->press_data->id;//编辑器代码提示优化，点击支持跳转
```
不用担心，执行`php think amp book/model`该模块下所有的模型的属性注释会自动更新，这样就可以进行代码跳转提示了。

--- 

**最后的重点在这里，以前接口需要写文档对吧，现在可以对它说再见了！！** 

`只需要在代码里面写好注释、其他的交给文档生成器自动完成`

在完成上述的配置后，把下面链接的主机地址换成配置好的本地地址，可以看到返回json数据
```
http://tp.test.cn/api/book.book/lists?title=C%E7%A8%8B%E5%BA%8F&author_id=3&press_name=%E4%BA%BA%E6%B0%91%E5%87%BA%E7%89%88
```
如果需要文档，只需要在后面多增加一个参数make_doc=2，就会显示，该有的注释都有，全都来自于代码注释，如果对文档的不满意，可以对文档模板进行改造。如果不希望文档直接输出到浏览器，可以选择make_doc=1,文档会生成在public目录下的comment.txt文件，里面是markdown格式的语法。因为url和代码目录是有层次的，所以可以考虑将文档输出到一个和代码目录层级一样的目录下面，更加方便查找。

![代码自动生成.gif](https://github.com/xiaobai1993/fast-think/blob/master/doc/auto_code.gif)

![模型注释自动生成.gif](https://github.com/xiaobai1993/fast-think/blob/master/doc/auto_model_property.gif)


![文档自动生成.gif](https://github.com/xiaobai1993/fast-think/blob/master/doc/auto_doc.gif)

