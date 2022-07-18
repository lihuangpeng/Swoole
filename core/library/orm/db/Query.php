<?php
/**
 * User: sethink
 */

namespace library\orm\db;

class Query
{
    /**
     * @var $mysql \PDO
     */
    protected $mysql;
    //sql生成器
    protected $builder;

    //db参数
    protected $options = [
        'table' => '',
        'alias' => [],
        'where' => [],
        'whereNum' => 0,
        'field' => '*',
        'order' => [],
        'distinct' => false,
        'join' => '',
        'union' => '',
        'group' => '',
        'having' => '',
        'limit' => '',
        'lock' => false,
        'fetch_sql' => false,
        'data' => [],
        'prefix' => '',
        'pool' => 'default',
        'setDefer' => true
    ];


    public function __construct($mysql)
    {
        //设置连接对象
        $this->mysql = $mysql;
        // 创建Builder对象
        $this->builder = new Builder();
    }


    /**
     * @文件信息
     *
     * @return string
     */
    protected function class_info()
    {
        return debug_backtrace();
    }

    /**
     * 连接开启事务
     */
    public function startTransaction()
    {
        return $this->mysql->beginTransaction();
    }

    /**
     * 连接提交事务
     */
    public function commit()
    {
        $result = $this->mysql->commit();
        return $result;
    }

    /**
     * 连接回滚事务
     */
    public function rollback()
    {
        $result = $this->mysql->rollBack();
        return $result;
    }

    /**
     * @表名
     *
     * @param $tableName
     * @return $this
     */
    public function name($tableName = '')
    {
        $this->options['table'] = $this->options['prefix'] . $tableName;
        return $this;
    }

    /**
     * @param string $tableName
     * @return $this
     */
    public function table($tableName = '')
    {
        $this->options['table'] = $tableName;
        return $this;
    }


    /**
     * @查询字段
     *
     * @param string $field
     * @return $this
     */
    public function field($field = '')
    {
        if (empty($field)) {
            return $this;
        }
        $field_array = explode(',', $field);
        //去重
        $this->options['field'] = array_unique($field_array);
        return $this;
    }


    /**
     * @order by
     *
     * @param array $order
     * @return $this
     */
    public function order($order = [])
    {
        $this->options['order'] = $order;
        return $this;
    }


    /**
     * @group by
     *
     * @param string $group
     * @return $this
     */
    public function group($group = '')
    {
        $this->options['group'] = $group;
        return $this;
    }


    /**
     * @having
     *
     * @param string $having
     * @return $this
     */
    public function having($having = '')
    {
        $this->options['having'] = $having;
        return $this;
    }


    //暂未实现
//    public function join()
//    {
//
//    }


    /**
     * @distinct
     *
     * @param $distinct
     * @return $this
     */
    public function distinct($distinct)
    {
        $this->options['distinct'] = $distinct;
        return $this;
    }


    /**
     * @获取sql语句
     *
     * @return $this
     */
    public function fetchSql()
    {
        $this->options['fetch_sql'] = true;
        return $this;
    }


    /**
     * @where语句
     *
     * @param array $whereArray
     * @return $this
     */
    public function where($field, $op = null, $condition = null)
    {
        if (is_array($field)) {
            $whereArray = $field;
        } elseif (is_object($field)) {
            $field($this);
            return $this;
        } else {
            if ($op != null && $condition == null) {
                $condition = $op;
                $op = '=';
            }
            $whereArray = [
                $field => [$op != null ? $op : '=', $condition]
            ];
        }

        $this->options['where'][$this->options['whereNum']] = $whereArray;
        $this->options['whereNum']++;
        return $this;
    }


    /**
     * @lock加锁
     *
     * @param bool $lock
     * @return $this
     */
    public function lock($lock = false)
    {
        $this->options['lock'] = $lock;
        return $this;
    }


    /**
     * @设置是否返回结果
     *
     * @param bool $bool
     * @return $this
     */
    public function setDefer(bool $bool = true)
    {
        $this->options['setDefer'] = $bool;
        return $this;
    }


    /**
     * @查询一条数据
     *
     * @return array|mixed
     */
    public function find()
    {
        $this->options['limit'] = 1;

        $result = $this->builder->select($this->options);

        if (!empty($this->options['fetch_sql'])) {
            return $this->getRealSql($result);
        }
        return $this->query($result);
    }


    /**
     * @查询
     *
     * @return bool|mixed
     */
    public function select()
    {
        // 生成查询SQL
        $result = $this->builder->select($this->options);

        if (!empty($this->options['fetch_sql'])) {
            return $this->getRealSql($result);
        }

        return $this->query($result);
    }


    /**
     * @ 添加
     *
     * @param array $data
     * @return mixed|string
     */
    public function insert($data = [])
    {
        $this->options['data'] = $data;

        $result = $this->builder->insert($this->options);

        if (!empty($this->options['fetch_sql'])) {
            return $this->getRealSql($result);
        }
        return $this->query($result);
    }


    public function insertAll($data = [])
    {
        $this->options['data'] = $data;

        $result = $this->builder->insertAll($this->options);

        if (!empty($this->options['fetch_sql'])) {
            return $this->getRealSql($result);
        }
        return $this->query($result);
    }


    public function update($data = [])
    {
        $this->options['data'] = $data;

        $result = $this->builder->update($this->options);

        if (!empty($this->options['fetch_sql'])) {
            return $this->getRealSql($result);
        }
        return $this->query($result);
    }


    public function delete()
    {
        // 生成查询SQL
        $result = $this->builder->delete($this->options);

        if (!empty($this->options['fetch_sql'])) {
            return $this->getRealSql($result);
        }

        return $this->query($result);
    }

    /**
     * @执行sql
     * @param $result
     * @return mixed
     * @throws \Exception
     */
    public function query($result)
    {
        $chan = new \chan(1);

        $class_info = $this->class_info();
        go(function () use ($chan, $result, $class_info) {
            try {
                if (is_string($result)) {
                    if (strpos($result, 'SELECT') !== false) {
                        $stmt = $this->mysql->query($result);
                        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    } else {
                        $rs = $this->mysql->exec($result);
                    }

                    if ($this->options['setDefer']) {
                        $chan->push($rs);
                    }
                } else {
                    $stmt = $this->mysql->prepare($result['sql']);

                    if ($stmt) {
                        foreach ($result['sethinkBind'] as $key => $value) {
                            $stmt->bindValue($key + 1, $value);
                        }
                        $rs = $stmt->execute();
                        if (strpos($result['sql'], 'SELECT') !== false) {
                            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                        }
                        if ($this->options['setDefer']) {
                            if ($this->options['limit'] == 1) {
                                if (count($rs) > 0) {
                                    $chan->push($rs[0]);
                                } else {
                                    $chan->push(null);
                                }
                            } else {
                                if (strstr($result['sql'], 'INSERT INTO')) {
                                    $chan->push($this->mysql->lastInsertId());
                                } else {
                                    $chan->push($rs);
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                if ($this->options['setDefer']) {
                    $chan->push(null);
                }
                throw new \Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
            }

        });
        if ($this->options['setDefer']) {
            return $chan->pop();
        }
    }

    /**
     * @sql语句
     *
     * @param $result
     * @return mixed
     */
    protected function getRealSql($result)
    {
        if (count($result['sethinkBind']) > 0) {
            foreach ($result['sethinkBind'] as $v) {
                $result['sql'] = substr_replace($result['sql'], "'{$v}'", strpos($result['sql'], '?'), 1);
            }
        }

        return $result['sql'];
    }


    public function __destruct()
    {
        unset($this->builder);
        unset($this->options);
    }


}
