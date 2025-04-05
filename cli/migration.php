<?php

require_once __DIR__ . '/../vendor/autoload.php';

use org\lumira\fw\DB;

define('MIGRATION_PATH', __DIR__ . '/../migrations');

function check($v)
{
    switch (gettype($v)) {
        case 'string':
            if (strlen($v) == 0) return 0;
            if ($v[0] == '1' || $v[0] == 't' || $v[0] == 'y') {
                return true;
            }
            return false;
        default:
        return false;
    }
}

function print_help()
{
    $cmd = $_SERVER['argv'][0];
    printf(
        "usage: php $cmd <up|down>\n" .
        "usage: php $cmd make <name>\n",
        "usage: php $cmd help\n",
    );
    exit;
}

function make()
{
    $now = date('YmdHis');
    $name = $_SERVER['argv'][2];
    if (!file_exists(MIGRATION_PATH)) {
        mkdir(MIGRATION_PATH);
    }
    $filename = MIGRATION_PATH . '/' . $now . '-' . $name . '.php';
    file_put_contents($filename, file_get_contents(__DIR__ . '/template_migration'));
}

function up()
{
    init();
    $migrations = collect_migration($gen);
    $db = DB::use();
    $insert = $db->prepare(
        'INSERT INTO `migrations` VALUES(:name, :gen)'
    );
    $gen++;
    foreach (scandir(MIGRATION_PATH) as $filename) {
        if (!preg_match('/^(.+)\.php$/', $filename, $matches)) {
            continue;
        }
        $name = $matches[1];
        if (has_migration($migrations, $name)) {
            continue;
        }
        $migration = require_once MIGRATION_PATH . '/' . $filename;
        if (!key_exists('up', $migration)) {
            printf("Fatal error: up function for $name not found\n");
            continue;
        }
        printf("Running up $name\n");
        try {
            $migration['up']($db);
            printf("Running up $name success\n");
            $insert->execute([
                'name' => $name,
                'gen' => $gen,
            ]);
        } catch (PDOException $err) {
            printf("Running up $name failed\n");
            printf("Error: %s\n", $err->getMessage());
            printf("%s\n", $err->getTraceAsString());
            exit(1);
        }
    }
}

function down()
{
    if (!check(getenv('ALLOW_DOWNGRADE'))) {
        printf("Migration down is disabled\nSet ALLOW_DOWNGRADE=1 to enable\n");
        exit;
    }
    init();
    $migrations = array_reverse(collect_migration($gen));
    $delete = DB::prepare(
        'DELETE FROM `migrations` WHERE name = :name'
    );

    foreach ($migrations as $data) {
        if ($data['gen'] != $gen) {
            continue;
        }
        $name = $data['name'];
        $filepath = MIGRATION_PATH . '/' . $name . '.php';
        if (!file_exists($filepath)) {
            printf("Fatal error: Migration file for $name not found");
            exit(1);
        }
        $migration = require_once $filepath;
        if (!key_exists('down', $migration)) {
            printf("Fatal error: down function for $name not found\n");
            exit(1);
        }
        printf("Running down $name\n");
        try {
            $migration['down']($db);
            printf("Running down $name success\n");
            $delete->execute([
                'name' => $name,
            ]);
        } catch (PDOException $err) {
            printf("Running down $name failed\n");
            printf("Error: %s\n", $err->getMessage());
            printf("%s\n", $err->getTraceAsString());
            exit(1);
        }
    }
}

function collect_migration(&$gen)
{
    $gen = 0;
    $migrations = DB::query('SELECT * FROM `migrations`')->fetchAll();
    foreach ($migrations as $migration) {
        $data_gen = $migration['gen'] = intval($migration['gen']);
        if ($data_gen > $gen) {
            $gen = $data_gen;
        }
    }
    return $migrations;
}

function has_migration($migrations, $name)
{
    foreach ($migrations as $val) {
        if ($val['name'] == $name) {
            return true;
        }
    }
    return false;
}

function init()
{
    $init_table = DB::query(
        'CREATE TABLE IF NOT EXISTS `migrations` (
            `name` varchar(255) NOT NULL,
            `gen` int NOT NULL,
            PRIMARY KEY (`name`)
        )'
    );
    if (!$init_table) {
        printf('Failed to create \'migrations\' table.');
        exit(1);
    }
}

function main()
{
    if ($_SERVER['argc'] === 1) {
        print_help();
    }
    array_shift($_SERVER['argv']);
    switch ($subcmd = array_shift($_SERVER['argv'])) {
        case 'help':
            print_help();
            break;
        case 'make':
            make();
            break;
        case 'up':
            up();
            break;
        case 'down':
            down();
            break;
        default:
            printf("Unknown subcommand $subcmd");
    }
}

main();
