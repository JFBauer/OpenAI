<?php

namespace JFBauer\OpenAI\Services\QueryAnalyzer;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class QueryAnalyzer
{
    public static function executeRawQuery(string $rawQuery, $connection = null) {
        $connection = $connection ?? config('database.default');
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
        $connection = $connection ?? config('database.default');
        $query = "EXPLAIN $rawQuery";
        return self::executeRawQuery($query, $connection);
    }

    public static function returnPromptForAnalysisOfRawQuery(string $rawQuery, $connection = null)
    {
        $explainResults = self::returnExplanationOfRawQuery($rawQuery, $connection);
        $tablesArray = self::getTableNamesFromExplainQuery($explainResults);
        $databaseSchemas = self::getDatabaseSchemasForTables($tablesArray);

        $prompt = "You are a an expert in SQL database, we have a query which needs optimizing. We will provide you all the info you need below step by step.\n\n";
        $prompt .= "<pre>This is the SQL query for which we want to improve the performance:\n".$rawQuery."\n\n";
        $prompt .= "This is the Explain plan for the query:\n</pre>".print_r($explainResults, true)."\n\n";
        $prompt .= "<pre>These are all the unique tables used in the query:\n</pre>".print_r($tablesArray, true)."\n\n";
        $prompt .= "<pre>This is all the info regarding the tables:\n</pre>".print_r($databaseSchemas['tables'], true)."\n\n";
//        $prompt .= "<pre>This is all the info regarding the columns:\n</pre>".print_r($databaseSchemas['columns'], true)."\n\n";
        $prompt .= "<pre>This is all the info regarding the indexes:\n</pre>".print_r($databaseSchemas['indexes'], true)."\n\n";
        $prompt .= "I would like for you to split your advice into 3 parts, of course it could be that there's no suggestions in some of these categories or maybe in all. Please give all the suggestions that you have that you know would likely help.\n";
        $prompt .= "Please add an extensive explanation for each of your suggestions as to why it's a good idea and which information led you to make the suggestion so that I might learn what to look for:\n";
        $prompt .= "1st. Give a list of suggestions to make into indexes, please mention explicitly if a suggested index is a composite index.\n";
        $prompt .= "2nd. Give a list of suggestions to change the query (and why), include a copy of the new suggested query at the end here. Make sure to keep into account that moving subqueries into joins could cause a multiplication of rows. If you need more info regarding the relationship between specific tables please ask me about them instead of making assumptions!\n";
        $prompt .= "3rd. Give a list of more advanced suggestions, this could include moving calculated parts in the select to be pre-calculated or other suggestions which need a combination of changes in the database, code or other places. Again, if you need more info to make a certain suggestion please ask me about it!";

        return $prompt;
    }

    public static function getTableNamesFromExplainQuery($explainQueryResults) {
        $tables = [];

        foreach ($explainQueryResults as $explainQueryResult) {
//            $tableName = str_replace("\r", "", $explainQueryResult->table);
//            $tableName = str_replace("\n", "", $tableName);
//            $tableName = trim(preg_replace('/\s+/', ' ', $tableName));
//            var_dump($tableName);
//            echo 'name'.$tableName.'isset'.isset($tableName).'empty'.empty($tableName).'strlen'.strlen($tableName).PHP_EOL;
//            if (isset($tableName) &&$tableName != null && $tableName != '' && strlen($tableName) != 0 && $tableName != "" && !in_array($tableName, $tables)) {
//                $tables[] = $tableName;
//            }

            // Check if the table name is a valid identifier and does not already exist.
            if (!empty($explainQueryResult->table)  && !in_array($explainQueryResult->table, $tables) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $explainQueryResult->table)) {
                $tables[] = $explainQueryResult->table;
            }
        }

        return $tables;
    }

    public static function getDatabaseSchemasForTables(array $tables, $connection = null) {
        $connection = $connection ?? config('database.default');
        $tableNames = implode("','", $tables);

        // Query for table-level information
        $tableQuery = "SELECT table_name, table_schema, table_type, engine, table_rows, data_length, index_length
                  FROM information_schema.tables
                  WHERE table_name IN ('$tableNames')";

        // Query for column-level information
//        $columnQuery = "SELECT table_name, column_name, data_type, column_default, is_nullable
//                   FROM information_schema.columns
//                   WHERE table_name IN ('$tableNames')";
//        $columnQuery = "SELECT table_name, column_name, data_type
//                   FROM information_schema.columns
//                   WHERE table_name IN ('$tableNames')";

        // Query for index information (including index type)
//        $indexQuery = "SELECT table_name, index_name, column_name, non_unique, index_type
//                  FROM information_schema.statistics
//                  WHERE table_name IN ('$tableNames')";
        $indexQuery = "SELECT table_name, index_name, column_name
                  FROM information_schema.statistics
                  WHERE table_name IN ('$tableNames')";

        $tableInfo = self::executeRawQuery($tableQuery, $connection);
//        $columnInfo = self::executeRawQuery($columnQuery, $connection);
        $indexInfo = self::executeRawQuery($indexQuery, $connection);

        return [
            'tables' => $tableInfo,
//            'columns' => $columnInfo,
            'indexes' => $indexInfo,
        ];
    }
}
