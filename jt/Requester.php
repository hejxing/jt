<?php
/**
 * Created by ax@jentian.com.
 * Date: 2015/6/12 13:19
 *
 *
 */

namespace jt;

use jt\exception\TaskException;

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
    protected $strict     = true;

    protected $method = '';

    const CONVERT_TYPE  = ['int', 'float', 'double', 'bool', 'array'];
    const VALIDATE_TYPE = ['email', 'mobile', 'phone', 'idcard', 'number', 'zn_ch'];

    const VALUE_RANGE_TYPE  = ['int', 'float', 'numeric', 'double'];
    const LENGTH_RANGE_TYPE = ['string'];

    const FALSE_VALUE = ['n', 'f', 'no', 'false'];

    const TRUE_ITEM    = ['require', 'lower', 'upper', 'unTrim', 'unEncode', 'unClean', 'unConvert'];
    const INPUT_TYPE   = ['any', 'get', 'post', 'path'];
    const VALUE_TYPE   = [
        'single'    => ['enum', 'bool', 'string', 'int', 'float', 'numeric', 'double', 'json'],//json为字符串类型
        'composite' => ['object', 'objectList', 'list', 'array']
    ];
    const INJECT_VALUE = ['instance', 'param'];
    const VALUE_RULE   = ['default', 'format', 'validate', 'use', 'convert', 'min', 'max'];

    /**
     * 获取参数
     *
     * @param        $name
     * @param string $ruler
     * @param bool   $strict
     *
     * @return mixed
     */
    public function get($name, $ruler = null, $strict = null)
    {
        if ($ruler === null) {
            return $this->__get($name);
        }else {
            return $this->value($name, self::parseValidate($ruler, $name), $strict);
        }
    }

    /**
     * 验证某值是否合法,如果合法返回值，不合法返回null
     *
     * @param mixed  $value
     * @param string $ruler
     * @param bool   $strict
     * @param string $name
     *
     * @return mixed
     */
    public static function validate($value, $ruler, $strict = false, $name = '')
    {
        return self::doProcess($value, self::parseValidate($ruler, $ruler), $name, $strict);
    }

    /**
     * 检测参数是否合法
     *
     * @param        $value
     * @param array  $option
     * @param string $name 获取参数的名单项名称
     * @param bool   $strict 是否严格模式
     *
     * @return mixed
     */
    public static function doProcess($value, array $option, $name, $strict = true)
    {
        if (!$option) {
            return $value;
        }
        if ($value === null) {
            if (isset($option['default'])) {
                $value = $option['default'];
            }elseif (isset($option['require'])) {
                self::error('value_empty', '该项值必填', $name, $option, $strict);
            }else {
                return null;
            }
        }

        if (isset($option['enum']) && !in_array($value, $option['enum'])) {
            return self::error('value_over', '只能从 [' . implode(', ', $option['enum']) . '] 中取值', $name, $option, $strict);
        }
        if (in_array($option['type'], self::CONVERT_TYPE)) { //转换类型
            $value = self::convert($value, $option['type']);
        }elseif (isset($option['validate'])) {
            $result = Validate::check($value, $option['validate']);
            if ($result === false) {
                self::error('value_validate_invalid', '值需要一个有效的 [' . $option['validate'] . ']', $name, $option, $strict);
            }elseif ($result === null) {
                self::error('value_validate_type_invalid', '验证规则无效 [' . $option['validate'] . '],需要有效的规则或正则表达式', $name, $option, $strict);
            }
        }else {
            if (!self::typeCheck($value, $option['type'])) {
                return self::error('value_type_invalid', '需要一个类型为 [' . $option['type'] . '] 的值', $name, $option, $strict);
            }
        }
        if (in_array($option['type'], static::VALUE_RANGE_TYPE)) {
            if ($option['min'] && $value < $option['min']) {
                return self::error('value_too_less', '值不能小于 ' . $option['min'], $name, $option, $strict);
            }
            if ($option['max'] && $value > $option['max']) {
                return self::error('value_too_large', '值不能大于 ' . $option['max'], $name, $option, $strict);
            }
        }

        if (in_array($option['type'], static::LENGTH_RANGE_TYPE)) {//比较长度
            if ($option['min'] && \mb_strlen($value) < $option['min']) {
                return self::error('value_too_less', '值不能少于 ' . $option['min'] . ' 位字符', $name, $option, $strict);
            }
            if ($option['max'] && \mb_strlen($value) > $option['max']) {
                return self::error('value_too_large', '值不能多于 ' . $option['max'] . ' 位字符', $name, $option, $strict);
            }
        }

        return $value;
    }

    /**
     * 数据类型转换
     *
     * @param $value
     * @param $type
     *
     * @return mixed
     */
    static public function convert($value, $type)
    {
        if ($value === null) {
            return null;
        }
        switch ($type) {
            case 'int':
                return intval($value);
                break;
            case 'float':
                return floatval($value);
                break;
            case 'double':
                return doubleval($value);
                break;
            case 'bool':
                return in_array(strtolower($value), self::FALSE_VALUE) ? false : boolval(is_numeric($value) ? floatval($value) : $value);
                break;
            case 'array':
                return $value ? preg_split('/ *, */', $value) : [];
                break;
            default:
                return $value;
                break;
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
                $lined = \array_merge($lined, self::attr($a, $name));
            }
        }

        if ($lined['type'] === 'undefined') {
            $lined['type'] = 'string';
            $lined['raw']  = trim('string ' . $lined['raw']);
        }

        if (in_array($lined['type'], self::VALUE_RANGE_TYPE) || in_array($lined['type'], self::LENGTH_RANGE_TYPE)) {
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
     * @throws \jt\exception\TaskException
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
                if (in_array($value, self::VALUE_TYPE['single']) || in_array($value, self::VALUE_TYPE['composite'])) {
                    $result['type'] = $value;
                }else {
                    throw new TaskException("actionRulerError:当前 Action 配置表中 [{$name}] 项值 [{$key}] 的属性 [{$value}] 有误，请检查");
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
                throw new TaskException("actionRulerError:当前 Action 配置表中 [{$name}] 项值 [{$key}] 有误，请检查");
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
            $data = self::parseInput($_SERVER['QUERY_STRING']);
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
            $this->valueCache[$name] = $this->value($name, $this->getRuler($name), $this->strict);
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
     * @param array ...$names
     * @return array
     */
    public function fetch(...$names)
    {
        $data = [];
        foreach ($names as $name) {
            $ns = preg_split('/ *, */', $name);
            foreach ($ns as $n) {
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
     * @param bool $strict 是否严格模式
     *
     * @return array
     */
    public function fetchAll($strict = true)
    {
        $data = [];
        $this->collectAsMap();

        $originStrict = $this->strict;
        $this->strict = $strict;
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
                self::error('value_empty', '该项值必填', $name, $validate, $strict);
            }
        }
        $this->strict = $originStrict;

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
     * @param int $pageSize 每页条数
     * @param int $page 当前页数
     *
     * @return array
     */
    public function fetchPage($pageSize = 10, $page = 1)
    {
        $option             = $this->fetch('page', 'pageSize');
        $option['pageSize'] = $option['pageSize'] ? \intval($option['pageSize']) : $pageSize;
        $option['page']     = $option['page'] ? \intval($option['page']) : $page;

        return $option;
    }

    /**
     * 获取值
     *
     * @param string $name 值名称
     * @param array  $ruler 验证规则
     * @param bool   $strict 是否严格模式
     *
     * @return mixed
     */
    protected function value($name, $ruler, $strict = true)
    {
        $field = $name;
        if ($ruler && isset($ruler['use'])) {
            $field = $ruler['use'];
        }

        $value = isset($this->originData[$field]) ? $this->originData[$field] : null;
        if (!$ruler) {
            return $value;
        }

        return self::doProcess($value, $ruler, $name, $strict);
    }

    /**
     * 是否严格模式,严格模式下不进行自动转换,非严格模式下对于不合规则的值返回null
     *
     * @param $strict
     */
    public function setStrict($strict)
    {
        $this->strict = $strict;
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
        return self::value($name, $this->getRuler($name), false) !== null;
    }

    /**
     * 输出错误
     *
     * @param string $code 错误编码
     * @param string $msg 错误消息
     * @param string $name 值名称
     * @param array  $option
     * @param bool   $strict 是否严格模式
     *
     * @return null
     */
    private static function error($code, $msg, $name, array $option, $strict)
    {
        $field = isset($option['_desc']) ? $name . ':' . $option['_desc'] : $name;
        $msg   = '[' . $field . '] ' . $msg;
        if (RUN_MODE !== 'production') {
            $line = isset($option['_line']) ? ' At line: ' . $option['_line'] : '';
            $msg .= '.' . $line;
        }
        if ($strict) {
            Error::msg('inputIll', $msg, ['field' => $name, 'code' => $code]);
        }elseif (RUN_MODE !== 'production') {
            Error::notice($code, $msg);
        }

        return null;
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
            static::error('require_value', $msg, implode(', ', $depend), [], true);
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
}