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
     * 执行DDL
     *
     * @param $sql
     */
    private function executeDDL($sql)
    {
        //去一个新线程执行
        $this->createPDO(false)->query($sql);
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
        $quotes = $this->getQuotes();
        $schema = '';
        if (strpos($table, '.') > 0) {
            $schema = explode('.', $table, 2)[0];
        }

        if ($schema) {
            $this->executeDDL("CREATE SCHEMA IF NOT EXISTS " . $schema);
            $this->executeDDL("SET search_path TO {$schema}");
        }

        $sql       = "CREATE TABLE {$table} (";
        $primary   = [];
        $increment = [];
        $sqlBuffer = [];
        foreach ($columns as $name => $column) {
            $notNull = '';
            $name = $column['field']??$name;
            $type = $this->genType($column);

            if (!isset($column['allowNull'])) {
                $notNull = ' NOT NULL';
            }

            if (isset($column['primary'])) {
                $primary[] = $name;
            }

            if (isset($column['increment'])) {
                $increment[] = $name;
            }

            $sqlBuffer[] = "{$quotes}{$name}{$quotes} {$type}{$notNull}";
        }
        $sql .= "\n" . implode(",\n", $sqlBuffer);
        if ($primary) {
            $sql .= ",\n PRIMARY KEY ({$quotes}" . implode($quotes.', '.$quotes, $primary) . $quotes.')';
        }
        $sql .= "\n)";
        //TODO 索引 备注
        $this->executeDDL($sql);
        $this->activeIncrement($table, $increment);
    }

    private function activeIncrement($table, array $columns)
    {
        $quotes = $this->getQuotes();
        foreach ($columns as $name) {
            $seqName = $table . '_' . $name . '_seq';
            $seqName = str_replace($quotes, '', $seqName);
            $this->executeDDL("CREATE SEQUENCE IF NOT EXISTS {$seqName} INCREMENT BY 1 START WITH 0 NO MINVALUE NO MAXVALUE CACHE 1 OWNED BY {$table}.{$quotes}{$name}{$quotes};");
            $this->executeDDL("ALTER TABLE {$table} ALTER COLUMN {$quotes}{$name}{$quotes} SET DEFAULT nextval('{$seqName}');");
        }
    }
}