# model_property_helper

这是一个基于thinkphp的command类实现的用于给model自动增加或者更新属性注释的工具，因为tp里面一个模型对应的是一个数据表。php是动态的语言，
模型对外提供的属性不能够在代码中定义，所以会导致以`$model->property`形式访问的时候编辑器会出警告，而且没有代码提示，很不友好。此外如果想
知道具体的含义需要去看数据表，或者去模型类查找具体的方法，也比较麻烦。因此可以通过在模型类顶部增加注释的方式，解决这个问题，但是每次都手动注释，
无疑会带来维护成本，这个工具就是解决这个问题的。

# 实现功能

- 可以给单个文件或者一个目录下所有模型文件自动生成注释。
- 自动生成的属性注释包含，数据表字段、模型定义的获取器、模型定义的关联方法。
- 当相关属性发生变化时，可以重复的执行，更新。
- 根据数据表备注模型的作用

# 使用方法

首先使用composer 安装
```
composer require  xiaobai/mph 
```

然后在command.php文件增加配置

```
'amp' => \xiaobai\think\command\ModelProperty::class,
```

完成以后

```
php think amp index/model #为index模块下model目录的所有的模型文件生成注释
```
也可以
```
php think amp index/model/PeopleModel.php #为index模块下model目录的PeopleModel生成注释
```

# 实验案例如下

原来的模型文件内容

```
class PeopleModel extends Model
{
    protected $table = 'new_people';

    /**
     * 人物的工作经历数据
     * @return \think\model\relation\HasMany
     */
    public function careerData()
    {
        return $this->hasMany(CareerModel::class,'people_guid','guid');
    }

    /**
     * 个人的详细信息
     * @return \think\model\relation\HasOne
     */
    public function profileData()
    {
        return $this->hasOne(ProfileModel::class,'people_guid','guid');
    }

    /**
     * 头像的完整路径
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getLogoFullPathAttr($value,$data)
    {
        return resource_url($data['urk']);
    }

}
```
效果如下

```
use think\Model;

/**
 * 人物表（投资者/创业者）
 * @property $guid   人物的guid
 * @property $publish_status   发布状态
 * @property $full_name   用户名称
 * @property $english_name   中文名称
 * @property $gender   性别
 * @property $byline   个性签名
 * @property $avatar_image   头像
 * @property $date_of_birth   出生日期
 * @property $contact_wechat   微信
 * @property $is_entrepreneur   是否是创业
 * @property $claimed_by   认领人
 * @property $claimed_at   认领时间
 * @property $career_data   CareerModel[]   人物的工作经历数据
 * @property $profile_data   ProfileModel   个人的详细信息
 * @property $logo_full_path   头像的完整路径
 */

class PeopleModel extends Model
{

```




