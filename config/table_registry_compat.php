<?php
/**
 * Compatibility helper for CakePHP 5
 * 
 * This file provides a global helper function to maintain backward compatibility
 * with TableRegistry::getTableLocator()->get() calls throughout the codebase.
 * 
 * Note: TableRegistry exists in CakePHP 5 but the get() method was removed.
 * Use TableRegistry::getTableLocator()->get() for new code.
 */

if (!function_exists('table_registry_get')) {
    /**
     * Helper function to get a table instance (backward compatibility)
     * 
     * @param string $alias The alias name you want to get.
     * @param array $options The options you want to build the table with.
     * @return \Cake\ORM\Table
     */
    function table_registry_get($alias, array $options = [])
    {
        return \Cake\ORM\TableRegistry::getTableLocator()->get($alias, $options);
    }
}
