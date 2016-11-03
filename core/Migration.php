<?php

namespace atomar\core;

/**
 * This is the base for all migration classes
 *
 */
abstract class Migration {

    /**
     * Execute the migration.
     * @return boolean Returns true if the migration is a success otherwise false.
     */
    public function run() {
        return true;
    }
}