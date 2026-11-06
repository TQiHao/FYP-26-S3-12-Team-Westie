<?php

require_once __DIR__ . "/../database/database.php";


class DatabaseDump
{
    private const ROWS_PER_INSERT = 100;

    private static function connect()
    {
        $db = (new Database())->connect();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }

        return $db;
    }

    public static function dump($path)
    {
        $file = null;

        try {
            $db = self::connect();

            $file = fopen($path, 'wb');
            if ($file === false) {
                return [1, "The backup file could not be created."];
            }

            fwrite($file, "-- UniBee database backup, written by PHP on " . date('Y-m-d H:i:s') . "\n");
            fwrite($file, "SET NAMES utf8mb4;\n");
            fwrite($file, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($file, "SET UNIQUE_CHECKS=0;\n");
            fwrite($file, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

            $db->exec("START TRANSACTION WITH CONSISTENT SNAPSHOT");

            $tables = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                self::writeTable($db, $file, $table);
            }

            $db->exec("COMMIT");

            fwrite($file, "SET FOREIGN_KEY_CHECKS=1;\n");
            fwrite($file, "SET UNIQUE_CHECKS=1;\n");
            fclose($file);

            return [0, ''];
        } catch (Throwable $e) {
            if (is_resource($file)) {
                fclose($file);
            }
            return [1, $e->getMessage()];
        }
    }

    public static function restore($path)
    {
        $file = null;

        try {
            $db = (new Database())->connect();
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $file = fopen($path, 'rb');
            if ($file === false) {
                return [1, "The backup file could not be opened."];
            }

            $db->exec("SET NAMES utf8mb4");
            $db->exec("SET FOREIGN_KEY_CHECKS=0");

            $statement = '';

            while (($line = fgets($file)) !== false) {
                $text = rtrim($line);

                if ($statement === '' && ($text === '' || strncmp($text, '--', 2) === 0)) {
                    continue;
                }

                if ($statement === '' && stripos($text, 'DELIMITER') === 0) {
                    fclose($file);
                    return [1, "This backup contains triggers or procedures, which need the mysql program to restore."];
                }

                $statement .= $line;

                if (substr($text, -1) === ';') {
                    self::run($db, $statement);
                    $statement = '';
                }
            }

            fclose($file);

            if (trim($statement) !== '') {
                self::run($db, $statement);
            }

            $db->exec("SET FOREIGN_KEY_CHECKS=1");

            return [0, ''];
        } catch (Throwable $e) {
            if (is_resource($file)) {
                fclose($file);
            }
            return [1, $e->getMessage()];
        }
    }

    private static function run($db, $statement)
    {
        try {
            $db->exec($statement);
        } catch (PDOException $e) {
            $isEmptyQuery = isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1065;
            if (!$isEmptyQuery) {
                throw $e;
            }
        }
    }

    // Write the structure and all rows of one table
    private static function writeTable($db, $file, $table)
    {
        $name = self::quoteName($table);

        $create = $db->query("SHOW CREATE TABLE $name")->fetch(PDO::FETCH_NUM);
        $structure = preg_replace('/\s*\n\s*/', ' ', $create[1]);

        fwrite($file, "-- Table $table\n");
        fwrite($file, "DROP TABLE IF EXISTS $name;\n");
        fwrite($file, $structure . ";\n");

        $rows = $db->query("SELECT " . self::selectList($db, $name) . " FROM $name", PDO::FETCH_ASSOC);

        $columns = null;
        $batch   = [];

        foreach ($rows as $row) {
            if ($columns === null) {
                $columns = '(' . implode(', ', array_map([self::class, 'quoteName'], array_keys($row))) . ')';
            }

            $values = [];
            foreach ($row as $value) {
                $values[] = self::literal($db, $value);
            }
            $batch[] = '(' . implode(', ', $values) . ')';

            if (count($batch) >= self::ROWS_PER_INSERT) {
                fwrite($file, "INSERT INTO $name $columns VALUES " . implode(', ', $batch) . ";\n");
                $batch = [];
            }
        }

        if ($batch) {
            fwrite($file, "INSERT INTO $name $columns VALUES " . implode(', ', $batch) . ";\n");
        }

        $rows->closeCursor();
        fwrite($file, "\n");
    }

    private static function selectList($db, $name)
    {
        $parts = [];

        foreach ($db->query("SHOW COLUMNS FROM $name")->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $field   = self::quoteName($column['Field']);
            $parts[] = stripos($column['Type'], 'float') === 0 ? "($field + 0e0) AS $field" : $field;
        }

        return implode(', ', $parts);
    }

    private static function quoteName($name)
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    private static function literal($db, $value)
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return var_export($value, true);
        }

        $value = (string) $value;

        if ($value !== '' && !preg_match('//u', $value)) {
            return '0x' . bin2hex($value);
        }

        return $db->quote($value);
    }
}