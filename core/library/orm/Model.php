<?php

namespace library\orm;

use library\orm\Db;
use library\Loader;
use Swoole\Coroutine;

/**
 * @package library\orm
 * @method db\Query  where() static
 * @method db\Query  order() static
 * @method db\Query  group() static
 * @method db\Query  select() static
 * @method db\Query  find() static
 * @method db\Query  insert() static
 * @method db\Query  insertAll() static
 * @method db\Query  delete() static
 *
 */

abstract class Model
{
    protected $table;
    protected $connection = null;

    public function __construct()
    {
        if(empty($this->table)){
            $arr = explode('\\', get_class($this));
            $this->table = Loader::parseName(array_pop($arr));
        }
    }

    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([new static(),$name],$arguments);
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([Db::getInstance()->connection($this->connection)->table($this->table),$name],$arguments);
    }
}