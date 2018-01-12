<?php


namespace core\console\controllers;

use Core;
use core\components\Controller;
use core\components\View;
use core\console\App;
use core\db\Connection;
use core\db\Migration;
use core\helpers\Console;
use core\helpers\FileHelper;

class MigrateController extends Controller
{
    private $_migrationsPath;

    /**
     * @var Connection
     */
    private $db;

    /**
     * @inheritdoc
     */
    public function beforeAction($action, $params = [])
    {
        Console::output('<== Core-Lite Migration Tool ==>' . PHP_EOL);
    }
    /**
     * @inheritdoc
     */
    public function init(){
        if (isset(App::$instance->request->args['migrationPath'])) {
            $this->_migrationsPath = FileHelper::normalizePath(Core::getAlias('@app/' . App::$instance->request->args['migrationPath']));
        } else {
            $this->_migrationsPath = Core::getAlias('@app' . DIRECTORY_SEPARATOR . 'migrations');
        }
        if (isset(App::$instance->request->args['db'])){
            $dbName = App::$instance->request->args['db'];
            $this->db = App::$instance->$dbName;
        }
        FileHelper::createDirectory($this->_migrationsPath);
    }
    /**
     * @inheritdoc
     */
    public function actionIndex()
    {
        $this->actionUp();
    }

    public function actionCreate()
    {
        if (!isset(App::$instance->request->args[0])) {
            throw new \Exception('Missed migration name', Console::FG_RED);
        }
        $name = App::$instance->request->args[0];
        if (!$this->validateName($name)) {
            Console::output('The migration name should contain letters, digits, underscore and/or backslash characters 
            only.', Console::FG_RED);
            return;
        }
        $migrationName = 'm' . date('ymd') . '_' . time() . '_' . $name;
        $migrationPath = $this->_migrationsPath . DIRECTORY_SEPARATOR . $migrationName . '.php';
        $migrationFullName = (isset(App::$instance->request->args['migrationPath'])
                ? App::$instance->request->args['migrationPath'].'/' : '') .$migrationName;
        if (Console::confirm("Create new migration $migrationFullName.php?", true)) {
            $handle = fopen($migrationPath, 'w');
            if ($handle !== false) {
                $template = View::renderPartial(FileHelper::normalizePath(Core::getAlias('@crl/view/migration.php')),[
                   'classname' => $migrationName
                ]);
                fwrite($handle, $template);
                fclose($handle);
                Console::output("Migration $migrationName created.", Console::FG_GREEN);
                return;
            }
            throw new \Exception('Can not create migration file');
        }
        return;
    }

    public function actionUp()
    {
        $this->db = $this->db != null ? $this->db : App::$instance->db;
        if ($this->checkForMigrationTable()) {
            $applied = array_map([$this, 'getMigrationName'], $this->db->createQueryBuilder()->select('migration_name')
                ->from('migrations')->queryAll());
            $migrations = FileHelper::findFiles($this->_migrationsPath);
            $migrationToApply = [];
            foreach ($migrations as $migration) {
                $parts = explode(DIRECTORY_SEPARATOR, str_replace('.php', '', $migration));
                $migrationName = array_pop($parts);
                if (!in_array($migrationName, $applied)) {
                    $migrationToApply[$migrationName] = $migration;
                }
            }
            if (empty($migrationToApply)) {
                Console::output('No new migrations found. Your system is up-to-date.', Console::FG_GREEN);
            } else {
                ksort($migrationToApply);
                $preparedMigrations = [];
                Console::output('Migrations to be applied:');
                $key = 0;
                foreach ($migrationToApply as $migration => $migrationPath) {
                    require_once $migrationPath;
                    $preparedMigrations[] = new $migration();
                    $key++;
                    Console::output("    $key. $migration");
                }
                if (Console::confirm('Apply all migrations?', true)) {
                    /**
                     * @var Migration $migration
                     */
                    foreach ($preparedMigrations as $migration) {
                        $migrationName = $migration::className();
                        Console::output('Applying ' . $migrationName . '...');
                        if (!$this->applyMigration($migration)) {
                            Console::output('Failed to apply migration ' . $migrationName, Console::FG_RED);
                            return;
                        }
                        $this->createMigrationRecord($migrationName);
                        Console::output($migrationName . ' applied', Console::FG_GREEN);
                    }
                    Console::output('Migrated up successful', Console::FG_GREEN);
                }
            }
        }
    }

    public function actionDown()
    {
        $this->db = $this->db != null ? $this->db : App::$instance->db;
        if ($this->checkForMigrationTable()) {
            $limit = isset(App::$instance->request->args[0]) ? App::$instance->request->args[0] : 1;
            $toDowngrade = array_map([$this, 'getMigrationName'], $this->db->createQueryBuilder()->select('migration_name')
                ->from('migrations')->orderBy(['applied_time' => 'DESC'])->limit($limit)->queryAll());
            if (empty($toDowngrade)) {
                Console::output('No migrations to revert.');
                return;
            }
            Console::output('Migrations to be reverted:');
            foreach ($toDowngrade as $key => $migration) {
                Console::output('    ' . ($key + 1) . '. ' . $migration);
            }
            if (Console::confirm('Revert the above migrations?', true)) {
                $preparedMigrations = [];
                foreach ($toDowngrade as $migrationName) {
                    $migrationPath = $this->_migrationsPath . DIRECTORY_SEPARATOR . $migrationName . '.php';
                    if (!is_file($migrationPath)) {
                        Console::output("Migration file not found. Cannot revert migration $migrationName", Console::FG_RED);
                        return;
                    }
                    require_once $migrationPath;
                    $preparedMigrations[] = new $migrationName();
                }
                foreach ($preparedMigrations as $migration) {
                    $migrationName = $migration::className();
                    Console::output('Reverting ' . $migrationName . '...');
                    if (!$this->revertMigration($migration)) {
                        Console::output('Failed to revert migration ' . $migrationName, Console::FG_RED);
                        return;
                    }
                    $this->removeMigrationRecord($migrationName);
                    Console::output($migrationName . ' reverted', Console::FG_GREEN);
                }
                Console::output('Migrated down successful', Console::FG_GREEN);
            }
        }
    }

    public function actionHistory()
    {
        $this->db = $this->db != null ? $this->db : App::$instance->db;
        if ($this->checkForMigrationTable()) {
            $limit = isset(App::$instance->request->args[0]) ? App::$instance->request->args[0] : 10;
            $history = $this->db->createQueryBuilder()->select('*')
                ->from('migrations')->orderBy(['applied_time' => 'DESC'])->limit($limit)->queryAll();
            if (empty($history)) {
                Console::output('No applied migrations found.');
                return;
            }
            Console::output("Showing the last $limit applied migrations:", Console::FG_YELLOW);
            foreach ($history as $migration) {
                Console::output('    [' . date('d.m.Y H:i:s', strtotime($migration['applied_time'])) . '] ' . $migration['migration_name']);
            }
        }
    }

    private function validateName($name)
    {
        return preg_match('/^[\w\\\\]+$/', $name);
    }

    private function checkForMigrationTable()
    {
        if (in_array('migrations', $this->db->getSchema()->getTableNames())) {
            return true;
        }
        Console::output('Not found `migrations` table. Creating it...');
        if ($this->createMigrationsTable('migrations', [
            'migration_name' => 'VARCHAR(255) NOT NULL COMMENT \'name of applied migration\'',
            'applied_time' => 'TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) COMMENT \'time of applied migration\''
        ])) {
            Console::output('`migrations` table successfully created.');
            return true;
        }
        return false;
    }

    private function createMigrationsTable($name, array $fields)
    {
        try {
            $query = 'CREATE TABLE ' . $this->db->quoteTableName($name) . ' (';
            $preparedFields = [];
            foreach ($fields as $key => $value) {
                $preparedFields[] = $this->db->quoteColumnName($key) . ' ' . $value;
            }
            $query .= implode(', ', $preparedFields) . ', PRIMARY KEY (' . $this->db->quoteColumnName('migration_name') . '))';
            $command = $this->db->createCommand($query);
            $command->execute();
            return true;
        } catch (\PDOException $ex) {
            throw $ex;
        }
    }

    private function getMigrationName($value)
    {
        return $value['migration_name'];
    }

    /**
     * @param Migration $migration
     * @return bool
     */
    private function applyMigration($migration)
    {
        if ($migration->up() === false) {
            return false;
        }
        return true;
    }

    /**
     * @param Migration $migration
     * @return bool
     */
    private function revertMigration($migration)
    {
        if ($migration->down() === false) {
            return false;
        }
        return true;
    }

    /**
     * @param string $migrationName
     */
    private function createMigrationRecord($migrationName)
    {
        $this->db->createQueryBuilder()->insert('migrations', [
            'migration_name' => $migrationName
        ]);
    }

    /**
     * @param string $migrationName
     */
    private function removeMigrationRecord($migrationName)
    {
        $this->db->createQueryBuilder()->delete()->from('migrations')->where([
            'migration_name' => $migrationName
        ])->execute();
    }

}