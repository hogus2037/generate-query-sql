<?php

namespace Hogus\Query;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Schema\Blueprint;

class GrammarGenerate
{
    protected $statements;

    protected $grammar;

    /** @var \Illuminate\Database\Connection */
    protected $connection;

    protected $driver = '';

    public function __construct($name = null)
    {
        $this->statements = collect();

        /** @var \Illuminate\Database\Connection */
        $this->connection = DB::connection($name);

        $this->grammar = $this->connection->getQueryGrammar();
    }

    public function update($query, array $values, array $attributes = [])
    {
        $query = $query instanceof Builder ? $query : $this->table($query)->where($attributes);

        $sql = $this->grammar->compileUpdate($query, $values);
        $binds = $query->cleanBindings($this->grammar->prepareBindingsForUpdate($query->bindings, $values));

        $this->generateContent($sql, $binds);

        return $this;
    }

    public function insert($query, array $values)
    {
        $query = $query instanceof Builder ? $query : $this->table($query);

        $sql = $this->grammar->compileInsert($query, $values);
        $binds = $query->cleanBindings(Arr::flatten($values, 1));

        $this->generateContent($sql, $binds);

        return $this;
    }

    public function delete($query, array $attributes = [])
    {
        $query = $query instanceof Builder ? $query : $this->table($query)->where($attributes);

        $sql = $this->grammar->compileDelete($query);
        $binds = $query->cleanBindings($this->grammar->prepareBindingsForDelete($query->bindings));

        $this->generateContent($sql, $binds);

        return $this;
    }

    public function change($table, Closure $callback)
    {
        $schemaBuilder = $this->connection->getSchemaBuilder();
        $connection = $schemaBuilder->getConnection();

        $blueprint = $this->createBlueprint($table, $callback);
        $sqls = $blueprint->toSql($connection, $connection->getSchemaGrammar());

        foreach ($sqls as $sql) {
            $this->statements->push($sql. ';');
        }

        return $this;
    }

    protected function createBlueprint($table, Closure $callback = null)
    {
        $prefix = $this->connection->getConfig('prefix_indexes')
            ? $this->connection->getConfig('prefix')
            : '';

        return Container::getInstance()->make(Blueprint::class, compact('table', 'callback', 'prefix'));
    }

    protected function table($table): Builder
    {
        return $this->connection->table($table);
    }

    protected function generateContent($sql, $binds)
    {
        $realSql = str_replace(['%', '?', '%s%s'], ['%%', '%s', '?'], $sql);
        $full_sql = vsprintf($realSql, array_map([$this->connection->getPDO(), 'quote'], $binds));

        $this->statements->push($full_sql.';');
    }

    public function save($filename, $extension = 'sql')
    {
        $contents = $this->statements->implode(PHP_EOL);

        $this->getAdapter()->put($filename.".". $extension, $contents);
    }

    public function getAdapter()
    {
        return Storage::disk($this->driver);
    }

    public function driver($name)
    {
        $this->driver = $name;

        return $this;
    }

    public function dump()
    {
        foreach ($this->statements as $sql) {
            print_r($sql.PHP_EOL);
        }
    }
}
