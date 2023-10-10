<?php

namespace JFBauer\OpenAI\Services\QueryAnalyzer;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;

class QueryAnalyzer
{
    public static function executeRawQuery(string $rawQuery, $connection = null) {
        $connection ??= config('database.default');
        return DB::connection($connection)->select($rawQuery);
    }

    function getRawQueryFromQueryBuilderObject(Builder $queryBuilderObject)
    {
        // Get the raw SQL query and bindings
        $sqlQuery = $queryBuilderObject->toSql();
        $bindings = $queryBuilderObject->getBindings();

        // Replace the bindings with their values
        $fullSqlQuery = vsprintf(str_replace('?', "'%s'", $sqlQuery), $bindings);

        return $fullSqlQuery;
    }

}