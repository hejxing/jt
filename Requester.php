<?php
/**
 * Created by ax@jentian.com.
 * Date: 2015/6/12 13:19
 *
 * 控制请求的输入输出
 *
 */

namespace jt;

/**
 * 处理外部获取到的参数
 *
 * @package jt
 */
class Requester
{
    protected $originData = [];
    protected $validate   = [];
    protected $valueCache = [];
    /**
     * @type array
     */
    protected $useNameMap = null;

    protected $method = '';

    const CONVERT_TYPE  = ['int', 'float', 'double', 'bool'];
    const VALIDATE_TYPE = ['email', 'mobile', 'phone', 'identityCard', 'number', 'zn_ch'];

    const VALUE_RANGE_TYPE  = ['int', 'float', 'numeric', 'double'];
    const LENGTH_RANGE_TYPE = ['string'];

    const FALSE_VALUE = ['n', 'f', 'no', 'false'];

    const TRUE_ITEM    = ['require', 'lower', 'upper', 'unTrim', 'unEncode', 'unClean', 'unConvert'];
    const INPUT_TYPE   = ['any', 'get', 'post', 'path'];
    const VALUE_TYPE   = [
        'single'    => ['enum', 'bool', 'string', 'int', 'float', 'numeric', 'double', 'json', 'uuid', 'datetime', 'timestamp'],//json为字符串类型
        'composite' => ['object', 'objectList', 'list']
    ];
    const INJECT_VALUE = ['instance', 'param'];
    const VALUE_RULE   = ['default', 'format', 'validate', 'use', 'convert', 'min', 'max'];

    /**
     * 获取参数
     *
     * @param        $name
     * @param string $ruler
     * @return mixed
     */
    public function get($name, $ruler = null)
    {
        if ($ruler === null) {
            return $this->__get($name);
        }else {
            return $this->value($name, self::parseValidate($ruler, $name));
        }
    }

    /**
     * 验证某值是否合法,如果合法返回值，不合法返回null
     *
     * @param mixed  $value
     * @param string $ruler
     * @param string $name
     *
     * @return mixed
     */
    public static function validate($value, $ruler, $name = '')
    {
        return self::doProcess($value, self::parseValidate($ruler, $ruler), $name);
    }

    /**
     * 检测参数是否合法
     *
     * @param mixed  $value
     * @param array  $option
     * @param string $name 获取参数的名单项名称
     *
     * @return mixed
     */
    public static function doProcess($value, array $option, $name)
    {
        if (!$option) {
            return $value;
        }
        if ($value === null) {
            if (isset($option['default'])) {
                $value = $option['default'];
            }elseif (isset($option['require'])) {
                self::error('value_empty', '该项值必填', $name, $option);
            }else {
                return null;
            }
        }

        if (isset($option['enum']) && !in_array($value, $option['enum'])) {
            self::error('value_over', '只能从 [' . implode(', ', $option['enum']) . '] 中取值', $name, $option);
        }
        if (in_array($option['type'], self::CONVERT_TYPE) || in_array($option['type'], self::VALUE_TYPE['composite'])) { //转换类型
            $value = self::convert($value, $option['type'], $option['format']);
        }elseif (isset($option['validate'])) {
            $result = Validate::check($value, $option['validate']);
            if ($result === false) {
                self::error('value_validate_invalid', '值只允许是 [' . $option['validate'] . ']', $name, $option);
            }elseif ($result === null) {
                self::error('value_validate_type_invalid', '验证规则无效 [' . $option['validate'] . '],需要有效的规则或正则表达式', $name, $option);
            }
        }else {
            if (!self::typeCheck($value, $option['type'])) {
                self::error('value_type_invalid', '需要类型为 [' . $option['type'] . '] 的值', $name, $option);
            }
        }
        if (in_array($option['type'], static::VALUE_RANGE_TYPE)) {
            if ($option['min'] && $value < $option['min']) {
                self::error('value_too_less', '值不能小于 ' . $option['min'], $name, $option);
            }
            if ($option['max'] && $value > $option['max']) {
                self::error('value_too_large', '值不能大于 ' . $option['max'], $name, $option);
            }
        }

        if (in_array($option['type'], static::LENGTH_RANGE_TYPE)) {//比较长度
            if ($option['min'] && mb_strlen($value) < $option['min']) {
                self::error('value_too_less', '值不能少于 ' . $option['min'] . ' 位字符', $name, $option);
            }
            if ($option['max'] && mb_strlen($value) > $option['max']) {
                self::error('value_too_large', '值不能多于 ' . $option['max'] . ' 位字符', $name, $option);
            }
        }

        return $value;
    }

    /**
     * 数据类型转换
     *
     * @param $value
     * @param $type
     * @param $format
     *
     * @return mixed
     */
    static public function convert($value, $type, $format)
    {
        if ($value === null) {
            return null;
        }
        switch ($type) {
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'double':
                return doubleval($value);
            case 'bool':
                return in_array(strtolower($value), self::FALSE_VALUE) ? false : boolval(is_numeric($value) ? floatval($value) : $value);
            case 'array':
            case 'objectList':
            case 'object':
            case 'list':
                if (is_string($value)) {
                    $arr = json_decode(urldecode($value), true);
                    if ($arr === null) {
                        $arr = preg_split('/ *, */', $value);
                    }
                }elseif (is_array($value)) {
                    $arr = $value;
                }else {
                    $arr = [];
                }

                return $arr;
            case 'timestamp':
                if (is_numeric($value)) {
                    return intval($value);
                }

                return strtotime($value);
            case 'datetime':
                $time = is_numeric($value) ? $value : strtotime($value);

                return date($format ?: 'Y-m-d H:i:s', $time);
            default:
                return $value;
        }
    }

    /**
     * 值类型检查
     *
     * @param $value
     * @param $type
     * @return bool
     */
    static protected function typeCheck($value, $type)
    {
        switch ($type) {
            case 'numeric':
                return is_numeric($value);
                break;
            case 'json':
                return true;
                break;
            default:
                return true;
                break;
        }
    }


    /**
     * 模型中字段解析器
     *
     * @param string $strRuler 规则字串
     * @param string $name 参数名称 用于显示友好的错误提示
     *
     * @return array
     */
    public static function parseValidate($strRuler, $name)
    {
        $lined = ['raw' => $strRuler, 'type' => 'undefined'];
        $parts = preg_split('/ +/', $strRuler);
        foreach ($parts as $a) {
            if ($a) {
                $res = self::attr($a, $name);
                if (isset($res['type']) && in_array($res['type'], self::VALUE_TYPE['composite']) && $lined['type'] !== 'undefined') {
                    $lined['inType'] = $lined['type'];
                }
                if (in_array($lined['type'], self::VALUE_TYPE['composite']) && isset($res['type'])) {
                    $lined['inType'] = $res['type'];
                    unset($res['type']);
                }
                $lined = \array_merge($lined, $res);
            }
        }

        if ($lined['type'] === 'undefined') {
            $lined['type'] = 'string';
            $lined['raw']  = trim('string ' . $lined['raw']);
        }

        if (!isset($lined['format'])) {
            $lined['format'] = null;
        }

        if (in_array($lined['type'], self::VALUE_RANGE_TYPE)
            || in_array($lined['type'], self::LENGTH_RANGE_TYPE)
            || (isset($lined['inType']) && (in_array($lined['inType'], self::VALUE_RANGE_TYPE)
                    || in_array($lined['inType'], self::LENGTH_RANGE_TYPE)))
        ) {
            if (!isset($lined['min'])) {
                $lined['min'] = 0;
            }
            if (!isset($lined['max'])) {
                $lined['max'] = 0;
            }
        }

        return $lined;
    }

    /**
     * 解析规则属性
     *
     * @param string $a
     * @param string $name
     * @return array
     * @throws Exception
     */
    private static function attr($a, $name)
    {
        if (strpos($a, ':')) {
            list($key, $value) = explode(':', $a, 2);
        }else {
            list($key, $value) = [$a, null];
        }
        $result = [];
        switch (true) {
            case in_array($key, self::TRUE_ITEM):
            case in_array($key, self::INPUT_TYPE):
                $result[$key] = true;
                break;
            case in_array($key, self::VALUE_TYPE['composite']):
            case in_array($key, self::VALUE_TYPE['single']):
                if ($key === 'enum') {
                    $result[$key] = preg_split('/ *, */', $value);
                }else {
                    $result['type'] = $key;
                }
                break;
            case 'type' === $key:
                $value = $value ?: 'string';
                if (in_array($value, self::VALUE_TYPE['single']) || in_array($value, self::VALUE_TYPE['composite'])) {
                    $result['type'] = $value;
                }else {
                    throw new Exception("actionRulerError:当前 Action 配置表中 [{$name}] 项值 [{$key}] 的属性 [{$value}] 有误，请检查");
                }
                break;
            case in_array($key, self::VALUE_RULE):
                $result[$key] = $value;
                break;
            case in_array($key, self::VALIDATE_TYPE):
                $result['validate'] = $key;
                break;
            case in_array($key, self::INJECT_VALUE):
                if ($key === 'param') {
                    $result[$key] = preg_split('/ *, */', $value);
                }else {
                    $result[$key] = $value;
                }
                break;
            default:
                throw new Exception("actionRulerError:当前 Action 配置表中 [{$name}] 项值 [{$key}] 有误，请检查");
                break;
        }

        return $result;
    }

    /**
     * 从输入创建参数获取器
     *
     * @param array  $validate
     * @param string $source
     *
     * @return \jt\Requester
     */
    public static function createFromRequest(array $validate, $source)
    {
        $data = [];
        if ($source === 'query' || $source === 'any') {
            $data = self::parseInput(urldecode($_SERVER['QUERY_STRING']));
        }

        if ($source === 'body' || $source === 'any') {
            if (\strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') === 0) {
                $input = self::extractJson($_POST);
            }else {
                $input = self::parseInput(\file_get_contents('php://input'));
            }
            $data = \array_merge($data, $input);
        }

        return static::create($data, $validate, $source);
    }

    /**
     * 创建实例
     *
     * @param        $data
     * @param array  $validate
     * @param string $method
     *
     * @return \jt\Requester
     */
    public static function create($data, $validate = [], $method = '')
    {
        $requester             = new self();
        $requester->originData = $data;
        $requester->method     = $method;
        $requester->validate   = $validate;

        return $requester;
    }

    /**
     * 抽取json
     *
     * @param $data
     * @return array
     */
    private static function extractJson($data)
    {
        if (isset($data['__json'])) {
            $d = \json_decode($data['__json'], true);
            if ($d) {
                $data = $d + $data;
                unset($data['__json']);
            }
        }

        return $data;
    }

    /**
     * 解析获取到的值
     *
     * @param $string
     *
     * @return array
     */
    private static function parseInput($string)
    {
        $data = \json_decode($string, true);
        if ($data === null) {
            \parse_str($string, $data);
            $data = self::extractJson($data);
        }

        return $data;
    }

    /**
     * 验证并给出参数值
     * 如果值不存在返回null
     * 值不符合规则在严格模式下提示错误，在非严格模式下返回null
     * 当值不存在或值为null时，不满足require要求，要求不许为空需要require与min联合使用
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (!isset($this->valueCache[$name])) {
            $this->valueCache[$name] = $this->value($name, $this->getRuler($name));
        }

        return $this->valueCache[$name];
    }

    /**
     * 获取验证规则
     *
     * @param $name
     *
     * @return array|null
     */
    private function getRuler($name)
    {
        $ruler = [];
        if ($name && isset($this->validate[$name])) {
            $ruler = $this->validate[$name];
            if (\is_string($ruler)) {
                $ruler                 = self::parseValidate($ruler, $name);
                $this->validate[$name] = $ruler;
            }
        }

        return $ruler;
    }

    /**
     * 批量获取值
     *
     * @param string $names
     *
     * @return array
     */
    public function fetch($names = '*')
    {
        $data = [];
        $ns = preg_split('/ *, */', $names);
        foreach ($ns as $n) {
            if($n === '*'){
                foreach($this->validate as $n => $r){
                    $data[$n] = $this->__get($n);
                }
            }else{
                $data[$n] = $this->__get($n);
            }
        }

        return $data;
    }

    /**
     * 搜集输入名与输出名不一致的字段内容
     *
     */
    protected function collectAsMap()
    {
        if (is_array($this->useNameMap)) {
            return;
        }

        $this->useNameMap = [];
        foreach ($this->validate as $name => $ruler) {
            if (\strpos($name, ':')) {
                list(, $name) = explode(':', $name);
            }
            $option = $this->getRuler($name);
            if (isset($option['use'])) {
                $this->useNameMap[$option['use']] = $name;
            }
        }
    }

    /**
     * 获取所有的值(忽略null,当值为null或不符合验证规则时会自动忽略)
     *
     * @return array
     */
    public function fetchAll()
    {
        $data = [];
        $this->collectAsMap();

        foreach ($this->originData as $input => $value) {
            $name  = isset($this->useNameMap[$input]) ? $this->useNameMap[$input] : $input;
            $value = $this->__get($name);
            if ($value !== null) {
                $data[$name] = $value;
            }
        }
        //检查是否含有必填项
        foreach ($this->validate as $name => $validate) {
            if (isset($validate['require']) && !isset($data[$name])) {
                self::error('value_empty', '该项值必填', $name, $validate);
            }
        }

        return $data;
    }

    /**
     * 深度获取请求的数据
     * 目前只支持两级深度
     *
     * @return array
     */
    public function fetchDepth()
    {
        $data       = [];
        $originData = $this->fetchAll();
        foreach ($originData as $item) {
            $d = [];
            foreach ($item as $input => $value) {
                $name     = isset($this->useNameMap[$input]) ? $this->useNameMap[$input] : $input;
                $d[$name] = self::doProcess($value, $this->getRuler($name), $name);
            }
            $data[] = $d;
        }

        return $data;
    }

    /**
     * 获取指定列表之外的数据
     *
     * @param array ...$names 要排除的字段列表(支持逗号分隔)
     *
     * @return array
     */
    public function fetchExclude(...$names)
    {
        $data = $this->fetchAll();
        foreach ($names as $name) {
            $ns = preg_split('/ *, */', $name);
            foreach ($ns as $n) {
                if (isset($data[$n])) {
                    unset($data[$n]);
                }
            }
        }

        return $data;
    }

    /**
     * 获取分页参数 如果未定义，返回默认值
     *
     * @param int $pageSize 默认每页条数,当客户端未传值时起作用
     * @param int $page 默认当前页数，当客户端未传值时起作用
     *
     * @return array
     */
    public function fetchPage($pageSize = 10, $page = 1)
    {
        $option             = $this->fetch('page, pageSize');
        $option['pageSize'] = $option['pageSize'] ? intval($option['pageSize']) : $pageSize;
        $option['page']     = $option['page'] ? intval($option['page']) : $page;

        return $option;
    }

    /**
     * 获取值
     *
     * @param string $name 值名称
     * @param array  $ruler 验证规则
     *
     * @return mixed
     */
    protected function value($name, $ruler)
    {
        $field = $name;
        if ($ruler && isset($ruler['use'])) {
            $field = $ruler['use'];
        }

        $value = isset($this->originData[$field]) ? $this->originData[$field] : null;
        if (!$ruler) {
            return $value;
        }

        return self::doProcess($value, $ruler, $name);
    }

    /**
     * 是否包含该值（非法的值认为不包含）
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        //寻找规则
        return self::value($name, $this->getRuler($name)) !== null;
    }

    /**
     * 输出错误
     *
     * @param string $code 错误编码
     * @param string $msg 错误消息
     * @param string $name 值名称
     * @param array  $option
     * @throws Exception
     */
    private static function error($code, $msg, $name, array $option)
    {
        $field = isset($option['_desc']) ? $name . ':' . $option['_desc'] : $name;
        $msg   = '[' . $field . '] ' . $msg;
        if (RUN_MODE !== 'production') {
            $line = isset($option['_line']) ? ' At line ' . $option['_line'] : '';
            $msg .= '.' . $line;
        }

        $e = new Exception('inputIll:' . $msg);
        $e->addData(['field' => $name, 'code' => $code]);
        throw $e;
    }

    /**
     * 该列表中的值至少需要一项
     *
     * @param array  $depend
     * @param string $msg
     *
     * @return bool
     */
    public function needOne(array $depend, $msg = null)
    {
        foreach ($depend as $name) {
            if ($this->has($name)) {
                return true;
            }
        }
        if (\is_string($msg)) {
            static::error('require_value', $msg, implode(', ', $depend), []);
        }

        return false;
    }

    /**
     * 剥一块数据出来，保留验证规则，以便传递给其它方法使用
     *
     * @param $name
     * @return Requester
     */
    public function peel($name)
    {
        if (!$name) {
            return $this;
        }else {
            return $this;
        }
    }

    /**
     * 直接获取值
     *
     * @param string $name 值名称
     * @param string $source 值来源
     * @param array  $validate 验证规则
     * @return mixed
     */
    public static function directGet($name, $source = 'any', $validate = [])
    {
        $requester = self::createFromRequest($validate, $source);

        return $requester->get($name);
    }

    /**
     * 按列表顺序取出第一个非空值
     *
     * @param array ...$names
     * @return mixed|null
     */
    public function firstNotEmpty(...$names)
    {
        foreach ($names as $name) {
            $v = $this->__get($name);
            if ($v !== null) {
                return $v;
            }
        }

        return null;
    }

    /**
     * 填充默认值
     *
     * @param $ruler
     * @return array
     */
    public static function fillEmpty($ruler)
    {
        switch ($ruler['type']) {
            case 'string':
                return '';
            case 'bool':
                return false;
            case 'object':
                $buffer = [];
                foreach($ruler[2] as $r){
                    $buffer[$r[0]] = self::fillEmpty($r);
                }
                return $buffer;
            case 'objectList':
            case 'list':
                return [];
            default:
                return 0;
        }
    }

    /**
     * 自动根据接口声明的内容和类型换回数据
     *
     * @param array $ruler
     * @param array $data
     *
     * @return array
     */
    public static function revisionData($ruler, $data)
    {
        if (empty($data)) {
            return self::fillEmpty($ruler);
        }
        switch ($ruler[1]['type']) {
            case 'object':
                $buffer = [];
                if (is_array($data)) {
                    foreach ($ruler[3] as $r) {
                        $buffer[$r[0]] = self::revisionData($r, $data[$r[0]]??null);
                    }
                }

                return $buffer;
            case 'objectList':
                $buffer           = [];
                $ruler[1]['type'] = 'object';
                if (is_array($data)) {
                    foreach ($data as $d) {
                        $buffer[] = self::revisionData($ruler, $d);
                    }
                }

                return $buffer;
            case 'list':
                $buffer = [];
                if (is_array($data)) {
                    foreach ($data as $d) {
                        $buffer[] = isset($ruler[1]['inType']) ? self::convert($d, $ruler[1]['inType'], $ruler[1]['format']) : $d;
                    }
                }

                return $buffer;
            default:
                return self::convert($data, $ruler[1]['type'], $ruler[1]['format']);
        }

    }
}