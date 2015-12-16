<?php
/**
 * 数据库结构生成器
 * User: hejxing
 * Date: 2015/6/14 8:33
 */

namespace jt\database;


class Schema extends Connector
{
    /**
     * 当前模块
     *
     * @type string
     */
    private $moudle = '';
    /**
     * 连接信息
     *
     * @type string
     */
    private $conn = '';

    /**
     * 构造连接
     *
     * @param $module
     * @param $conn
     */
    public function __construct($module, $conn)
    {
        $this->moudle = $module;
        $this->conn   = $conn;
        $this->config = self::loadConfig($module, $conn);
    }

    /**
     * 执行DDL
     *
     * @param $sql
     */
    private function executeDDL($sql)
    {
        parent::__construct($this->moudle, $this->conn);
        $this->query($sql);
    }

    /**
     * 创建数据库
     */
    public function createDataBase()
    {
        $dbName         = $this->config['dBPrefix'] . $this->config['schema'];
        $this->selectDb = false;

        $sql = "CREATE DATABASE {$dbName} ENCODING '{$this->config['charset']}' TEMPLATE postgres";
        $this->executeDDL($sql);
    }

    private function genType(array $option)
    {
        $type = $option['type'];
        switch ($type) {
            case 'numeric':
                $type = $option['fieldType'];
                break;
            case 'string':
                $type = $option['fieldType'];
                if ($type !== 'text') {
                    $type .= "({$option['length']})";
                }
                break;
        }

        return $type;
    }

    /**
     * 如果表不存在,则创建表
     *
     * @param       $table
     * @param array $columns
     */
    public function createTable($table, array $columns)
    {
        $schema = '';
        if (strpos($table, '.') > 0) {
            $schema = explode('.', $table, 2)[0];
        }

        if ($schema) {
            $this->executeDDL("CREATE SCHEMA IF NOT EXISTS " . $schema);
        }

        $sql       = "CREATE TABLE {$table} (";
        $primary   = [];
        $sqlBuffer = [];
        foreach ($columns as $name => $column) {
            if (isset($column['field'])) {
                $name = $column['field'];
            }
            $type = $this->genType($column);

            if (!isset($column['allowNull'])) {
                $notNull = 'NOT NULL ';
            }

            if (isset($column['primary'])) {
                $primary[] = $name;
            }

            $sqlBuffer[] = "\"{$name}\" {$type} {$notNull}";
        }
        $sql .= "\n" . implode(",\n", $sqlBuffer);
        if ($primary) {
            $sql .= ",\n PRIMARY KEY (\"" . implode('", "', $primary) . '")';
        }
        $sql .= "\n)";
        //TODO 索引 备注

        $this->executeDDL($sql);
    }
}