<?php

namespace app\common\lib;

use app\common\helper\ArrayHelper;
use think\Db;
use think\facade\Request;
use think\Loader;
use think\Model;
use think\Paginator;

class AutoDocument
{

    static $flag_field = "make_doc";

    protected $classFileMaps = [];

    const noFound = '`不确定`';
    /**
     * api文档的格式
     * @var string
     */
    public static $template = '## {{api_name}}
### 业务功能
{{api_desc}}

### 接口地址 ({{method}})
`{{api_url}}` 

### 非路由地址（后台开发用）

`{{origin_url}}`
 
>测试地址 [{{test_url}}]({{test_url}})

### 请求参数
参数名称 |是否必填 |参数类型 | 案例 | 说明
:--- | :---: | :---: | :--- | :---
{{ask_param_desc}}

### 返回数据
```
{{response}}
```

参数名称 | 参数类型 | 说明
:--- | :---: | :---
{{response_desc}}';

    /**
     * 常用的注释
     */
    const whiteLists = [
        'code' => '编码',
        'total' => '总数',
        'msg' => '提示信息',
        'last_page' => '最后页码',
        'current_page' => '当前页',
        'data' => '数据',
        'per_page' => '每页大小',
        'order_field' => '排序的字段',
        'order_sort' => '排序类型，asc升序，desc降序',
     ];

    public function __construct()
    {
        $this->classFileMaps = [];
    }

    public function createDocument($outPutData)
    {
        $vars = [];
        $vars['test_url'] = Request::domain().Request::baseUrl()."?".http_build_query(Request::except(self::$flag_field));
        $vars['method'] = Request::method();
        $template = self::$template;
        $vars['api_url'] = Request::baseUrl();
        $controller = Request::controller();
        $module = Request::module();

        $vars['origin_url'] = strtolower( Request::module()."/".Loader::parseName(Request::controller(),0)."/".Request::action());
        $className = Loader::parseName(str_replace('.', '\\', 'app\\' . $module . '\\controller\\' . $controller), 1);
        $vars['class'] = $className;
        $vars['class_exist'] = class_exists($className);
        $method = Request::action();
        $vars['action'] = $method;
        $classReflect = new \ReflectionClass($vars['class']);
        $methodAction = $classReflect->getMethod($vars['action']);
        $vars['api_name'] = $this->getDocTitle($methodAction->getDocComment());
        $vars['api_desc'] = $this->getDocBody($methodAction->getDocComment());
        $model = $this->getModel($methodAction);
        $dtoClass = $this->getDtoClass($methodAction);
        $vars['ask_param_desc'] = $this->createAskParam($model, $dtoClass);
        $vars['response'] = json_decode(json_encode($outPutData, JSON_UNESCAPED_UNICODE), JSON_UNESCAPED_UNICODE);
        $comments = [];
        $this->getResponseComment($vars['response'], $model, $comments);
        $vars['response_desc'] = $comments;
        $this->assignVars($vars, $template);
        return $vars;
    }

    /**
     * @param Model|null $model
     * @param null $dtoClass
     * @return array
     */
    public function createAskParam(Model $model = null, $dtoClass = null)
    {
        $param = Request::param();
        $paramInfo = [];
        $paramKeys = array_keys($param);
        foreach ($param as $key => $value) {
            if ($key == self::$flag_field) {
                continue;
            }
            $oneItem = [];
            $oneItem['param_name'] = $key;
            if ($paramKeys[0] == $key) {
                $oneItem['is_must'] = 'Y';
            } else {
                $oneItem['is_must'] = 'N';
            }
            $oneItem['param_type'] = $this->getValueType($value);
            if (is_array($value)) {
                $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
            $oneItem['param_example'] = "例如:`$value`";
            $oneItem['param_example'] = str_replace("|","\|",$oneItem['param_example']);
            $oneItem['param_desc'] = $this->getAskKeyDesc($key, $dtoClass, $model);
            $oneItem['param_desc'] = str_replace("|","\|",$oneItem['param_desc']);

            $paramInfo[] = $oneItem;
        }
        return $paramInfo;
    }

    /**
     * 获取某个参数的请求文档
     * @param $key
     * @param \ReflectionClass|null $class
     * @param Model|null $model
     * @return string
     */
    public function getAskKeyDesc($key, \ReflectionClass $class = null, Model $model = null)
    {
        if (!$class) {
            return $this->getKeyDesc($key, $model);
        }
        if (strpos($key, 'search_') !== false) {
            $key = substr($key, 7);
        }
        if ($class->hasProperty($key)) {
            $property = $class->getProperty($key);
            return $this->getDocTitle($property->getDocComment()) ?: self::noFound;
        } else {
            return self::noFound;
        }
    }

    protected function assignVars($vars, $template)
    {
        $template = str_replace("{{api_name}}", $vars['api_name'], $template);
        $template = str_replace("{{api_desc}}", $vars['api_desc'], $template);
        $template = str_replace("{{api_url}}", $vars['api_url'], $template);
        $ask_param_desc = [];
        foreach ($vars['ask_param_desc'] as $var) {
            $ask_param_desc[] = implode("|", array_values($var));
        }
        $ask_param_desc = implode("\n", $ask_param_desc);
        $template = str_replace("{{ask_param_desc}}", $ask_param_desc, $template);
        $template = str_replace("{{response}}", json_encode($vars['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $template);
        $response_desc = [];
        foreach ($vars['response_desc'] as $var) {
            $response_desc[] = implode("|", array_values($var));
        }
        $response_desc = implode("\n", $response_desc);
        $template = str_replace("{{response_desc}}", $response_desc, $template);
        $template = str_replace("{{test_url}}", $vars['test_url'], $template);
        $template = str_replace("{{method}}", $vars['method'], $template);
        $template = str_replace("{{origin_url}}", $vars['origin_url'], $template);

        if (Request::param(self::$flag_field) == 2){
            $mention = new \Parsedown();
            echo $mention->text($template);
            die();
        }
        file_put_contents('comment.txt', $template);
        return $template;
    }

    /**
     * 获取数据的类型
     * @param $value
     * @return string
     */
    public function getValueType($value)
    {
        if (is_numeric($value)) {
            return "Number";
        } elseif (is_string($value)) {
            return "String";
        } elseif (is_array($value)) {
            if (ArrayHelper::isIndexed($value)) {
                return "Array";
            } else {
                return "Object";
            }
        } elseif ($value === null) {
            return "Object";
        } else {
            return self::noFound;
        }
    }

    /**
     * 获取字段的注释
     * @param $key
     * @param Model|null $model
     * @return string
     */
    public function getKeyDesc($key, Model $model = null, $keyPrefix = '')
    {
        if (in_array($key, array_keys(self::whiteLists)) && !$keyPrefix) {
            return self::whiteLists[$key];
        }
        if (!$model) {
            return self::noFound;
        }
        if (strpos($key, 'search_') !== false) {
            $key = substr($key, 7);
        }

        $methodName = Loader::parseName($key, 1, false);
        $modelClass = new \ReflectionClass(get_class($model));

        //尝试获取模型文件的头部注释去查找。

        $classDoc =  $modelClass->getDocComment();
        if (preg_match_all('/\*\s*?@property\s*(.*?)\s*\$([^\s]+)\s*?(.*)\n/',$classDoc,$matches)){
            $index = array_search($key,$matches[2]);
            if ($index !== false){
                return $matches[3][$index];
            }
        }
        $attrMethodName = 'get' . Loader::parseName($key, 1) . "Attr";
        if (strpos($key, '_data')) {
            if (!$this->checkMethodExist($model, $key)) {
                return self::noFound;
            }
            $method = $modelClass->getMethod($methodName);
            if ($method) {
                return $this->getDocTitle($method->getDocComment());
            } else {
                return self::noFound;
            }
        } elseif (method_exists($model, $attrMethodName)) {
            $method = $modelClass->getMethod($attrMethodName);
            $doc = $this->getDocTitle($method->getDocComment());
            if ($doc) {
                return $doc;
            } else {
                if (strpos(strtolower($key), 'full_path')) {
                    return $this->getKeyDesc(substr($key, 0, strlen($key) - strlen('full_path'))) . "的全路径，用来展示";
                } elseif (strpos(strtolower($key), 'for_display')) {
                    return $this->getKeyDesc(substr($key, 0, strlen($key) - strlen('full_path'))) . "对应的显示时间戳";
                }
            }
        } else {
            $fieldMaps = $this->getTableDocument($model);
            if (!isset($fieldMaps[$key]) && strpos($key, 'id') !== false) {
                return $key;
            }
            return $fieldMaps[$key]??self::noFound;
        }
    }

    /**
     * 根据控制器和方法名字获取service
     * @param \ReflectionMethod $method
     * @return null|Model
     */
    public function getModel(\ReflectionMethod $method)
    {
        $fileName = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $methodContent = $this->readFile($fileName, $startLine, $endLine);
        //找service
        if (preg_match('/([\S]*?Service)\:\:getInstance/', $methodContent, $matches)) {
            $service = trim($matches[1]);
            $fileContent = $this->getClassFileContent($method->class);
            $pattern = "#use\s*(app.*?$service)#";
            if (preg_match($pattern, $fileContent, $matches)) {
                $serviceClass = $matches[1];
                if (class_exists($serviceClass)) {
                    $service = new $serviceClass();
                    if ($service instanceof BaseService) {
                        return $service->getModel();
                    } else {
                        return null;
                    }
                } else {
                    return null;
                }
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * 获取dto的类
     * @param \ReflectionMethod $method
     * @return null|\ReflectionClass
     */
    public function getDtoClass(\ReflectionMethod $method)
    {
        $fileName = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $methodContent = $this->readFile($fileName, $startLine, $endLine);
        //找service
        if (preg_match('/([\S]*?Param)\:\:create/', $methodContent, $matches)) {
            $service = trim($matches[1]);
            $fileContent = $this->getClassFileContent($method->class);
            if (preg_match("#use\s*(app.*?$service)#", $fileContent, $matches)) {
                $serviceClass = $matches[1];
                if (class_exists($serviceClass)) {
                    return new \ReflectionClass($serviceClass);
                } else {
                    return null;
                }
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * 读文件
     * @param $file_name
     * @param $start
     * @param $end
     * @return string
     */
    function readFile($file_name, $start, $end)
    {
        $limit = $end - $start;
        $f = new \SplFileObject($file_name, 'r');
        $f->seek($start);
        $ret = "";
        for ($i = 0; $i < $limit; $i++) {
            $ret .= $f->current();
            $f->next();
        }
        return $ret;
    }


    /**
     * 获取类或者方法注释的标题，第一行
     * @param $docComment
     * @return string
     */
    public function getDocTitle($docComment)
    {
        if ($docComment !== false) {
            $docCommentArr = explode("\n", $docComment);
            $comment = trim($docCommentArr[1]);
            return trim(substr($comment, strpos($comment, '*') + 1));
        }
        return '';
    }

    /**
     * 获取方法的描述的主题，不包括标题
     * @param $docComment
     * @return string
     */
    public function getDocBody($docComment)
    {
        if ($docComment !== false) {
            $docCommentArr = explode("\n", $docComment);
            $comment = implode("\n",array_slice($docCommentArr, 2));
            $comment = preg_replace("#^([\s\S]*?)@[\s\S]*$#", "$1", $comment);
            $comment = str_replace("*", "", $comment);
            return trim(substr($comment, strpos($comment, '*') + 1));
        }
        return '';
    }


    /**
     * 根据模型获取表的注释
     * @param Model $model
     * @return array
     */
    public function getTableDocument(Model $model)
    {
        $createSQL = Db::query("show create table " . $model->getTable())[0]['Create Table'];
        preg_match_all("#`(.*?)`(.*?) COMMENT\s*'(.*?)',#", $createSQL, $matches);
        $fields = $matches[1];
        $comments = $matches[3];
        $fieldComment = [];
        //组织注释
        for ($i = 0; $i < count($matches[0]); $i++) {
            $key = $fields[$i];
            $value = $comments[$i];
            $fieldComment[$key] = $value;
        }
        return $fieldComment;
    }


    /**
     * 获取一个模型关联的模型
     * @param \ReflectionMethod $method
     */
    public function getRelationModel(\ReflectionMethod $method)
    {
        $fileName = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $methodContent = $this->readFile($fileName, $startLine, $endLine);
        if (preg_match('/\(([a-zA-Z].*Model)::class/', $methodContent, $m)) {
            $relationModel = $m[1];
            $relationModelClass = $this->getIncludeClassName($method->class, $relationModel);
            if ($relationModelClass) {
                $modelInstance = new $relationModelClass();
                return $modelInstance;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * 获取响应结果的注释
     * @param array $responseData
     * @param null $model
     * @param array $comments
     * @param string $keyPrefix
     */
    public function getResponseComment(array $responseData, $model = null, &$comments = [], $keyPrefix = '')
    {
        foreach ($responseData as $key => $value) {
            if (strpos($key, '_data') !== false && (is_array($value) || is_null($value))) {
                $classInstance = $this->getRelationModelByModel($key,$model);
                $dataType =  $this->getValueType($value);
                if ($classInstance){
                    $dataType .= "(".str_replace("Model","",pathinfo(str_replace("\\","/",get_class($classInstance)),PATHINFO_FILENAME)).")";
                }
                $comments[] = [
                    'field' => $this->getPrefix($keyPrefix, $key),
                    'type' => $dataType,
                    'desc' => $this->getKeyDesc($key, $model)
                ];
            } else {
                $comments[] = [
                    'field' => $this->getPrefix($keyPrefix, $key),
                    'type' => $this->getValueType($value),
                    'desc' => $this->getKeyDesc($key, $model)
                ];
            }

            if (is_array($value)) {
                if (ArrayHelper::isIndexed($value)) {
                    $nextValue = $value[0];
                } else {
                    $nextValue = $value;
                }
                $relationModel = !empty($classInstance) ? $classInstance : $model;
                if (!is_array($nextValue)) {
                    continue;// 索引数组为空的时候
                }
                $this->getResponseComment($nextValue, $relationModel, $comments, $this->getPrefix($keyPrefix, $key));
            }
        }
    }

    /**
     * 拼接返回结果前缀
     * @param string $prefix
     * @param string $next
     * @return string
     */
    protected function getPrefix($prefix = "", $next = "")
    {
        if (!$prefix) {
            return $next;
        } else {
            return $prefix . "." . $next;
        }
    }

    /**
     * 检查方法是否存在，父类里面的不算
     * @param $classObject
     * @param $methodName
     * @param string $type
     * @return bool
     */
    protected function checkMethodExist($classObject, $methodName, $type = 'public')
    {
        $ma = Loader::parseName($methodName, 1, false);
        if (!method_exists($classObject, $ma)) {
            return false;
        }
        $content = $this->getClassFileContent(get_class($classObject));
        if (preg_match("#$type\s*function $ma#", $content)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取类文件的内容
     * @param $className
     * @return mixed
     * @throws \Exception
     */
    protected function getClassFileContent($className)
    {
        if (class_exists($className)) {
            $classReflect = new \ReflectionClass($className);
        } else {
            throw new \Exception("类不存在", '1');
        }
        if (!isset($this->classFileMaps[$className])) {
            $this->classFileMaps[$className] = file_get_contents($classReflect->getFileName());
        }
        return $this->classFileMaps[$className];
    }

    public function getIncludeClassName($mainClass, $class)
    {
        $classFile = $this->getClassFileContent($mainClass);
        $pattern = "/use\s*(app.*?\\\\$class)/";
        if (preg_match($pattern, $classFile, $matches)) {
            return $matches[1];
        } else {
            $classReflect = new \ReflectionClass($mainClass);
            $possibleClass = $classReflect->getNamespaceName() . "\\" . $class;
            if (class_exists($possibleClass)) {
                return $possibleClass;
            } else {
                return "";
            }
        }
    }

    protected function getRelationModelByModel($key,Model $model = null)
    {
        if(!$model){
            return null;
        }

        $modelClass = new \ReflectionClass(get_class($model));
        $methodName = Loader::parseName($key, 1, false);

        $classDoc =  $modelClass->getDocComment();
        if (preg_match_all('/\*\s*?@property\s*(.*?)\s*\$([^\s]+)\s*?(.*)\n/',$classDoc,$matches)){
            $index = array_search($key,$matches[2]);
            if ($index !== false){
                $docClass =  $matches[1][$index];
                if (strpos($docClass,'[]') !== false){
                    $docClass = substr($docClass,0,strlen($docClass) - 2);
                }
               $relationClass =  $this->getIncludeClassName(get_class($model),$docClass);
               if (class_exists($relationClass)){
                   return new $relationClass();
               }
            }
        }
        if ($this->checkMethodExist($model, $methodName)) {
            $method = $modelClass->getMethod($methodName);
            $relationModel = $this->getRelationModel($method);
            return $relationModel;
        }else{
            return null;
        }
    }
}