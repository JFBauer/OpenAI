<?php

namespace JFBauer\OpenAI\Services\QueryAnalyzer;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class QueryAnalyzer
{
    public static function executeRawQuery(string $rawQuery, $connection = null) {
        $connection ??= config('database.default');
        return DB::connection($connection)->select($rawQuery);
    }

    public static function getRawQueryFromQueryBuilderObject(Builder $queryBuilderObject)
    {
        // Get the raw SQL query and bindings
        $sqlQuery = $queryBuilderObject->toSql();
        $bindings = $queryBuilderObject->getBindings();

        // Replace the bindings with their values
        $fullSqlQuery = vsprintf(str_replace('?', "'%s'", $sqlQuery), $bindings);

        return $fullSqlQuery;
    }

    public static function executeQueryBuilderObject(Builder $queryBuilderObject)
    {
        $rawQuery = self::getRawQueryFromQueryBuilderObject($queryBuilderObject);
        return self::executeRawQuery($rawQuery, $queryBuilderObject->getConnection());
    }

    public static function returnExecutionTimeOfRawQuery(string $rawQuery, $connection = null)
    {
        $startTime = microtime(true);
        self::executeRawQuery($rawQuery, $connection);
        $endTime = microtime(true);
        return $endTime - $startTime;
    }

    public static function returnExplanationOfRawQuery(string $rawQuery, $connection = null)
    {
        $connection ??= config('database.default');
        $query = DB::connection($connection)->select("EXPLAIN $rawQuery");
        return self::executeRawQuery($query, $connection);
    }

    public static function returnPromptForAnalysisOfRawQuery(string $rawQuery, $connection = null)
    {
        $prompt = "This is the SQL query for which we want to improve the performance:".PHP_EOL.$rawQuery.PHP_EOL.PHP_EOL;
        $prompt .= "This is the Explain plan for the query:".PHP_EOL.self::returnExplanationOfRawQuery($rawQuery, $connection).PHP_EOL.PHP_EOL;

        return $prompt;
    }
}