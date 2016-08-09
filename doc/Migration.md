Migration
---

Atomar comes built with a robust migration manager. Migration is also supported in [Extensions](/admin/documentation/core/Extensions). You can generate a new migration by visiting the extension page and clicking 'New Migration'. A lightbox will appear in which you must define the version from which and the version to which you are migrating. When you click submit a new migration file will be generated that you can then edit.

The migration files are self explanitory so here is a shortened example

    /**
     * Migration from Atomar version 2.1.4 to 3.0.0
     */
    class migration_eab20eec1be1b149226dfcd384250e54 extends Migration {
      public function run() {

        $sql_update = <<<SQL
    -- put your sql here
    SQL;

        // perform updates
        R::begin();
        try {
          R::exec($sql_update);
          R::commit();
          return true;
        } catch (Exception $e) {
          R::rollback();
          log_error('Migration from Atomar version 2.1.4 to 3.0.0 failed', $e->getMessage());
          return false;
        }
      }
    }

All of the above code is automatically generated for you. You just need to provide the sql for the database updates.