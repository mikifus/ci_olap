<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Here are configurated the cubes
 *
 */

/*
|--------------------------------------------------------------------------
| Cubes
|--------------------------------------------------------------------------
|
| This is the configuration for the data models. Each cube represents
| a statistic to be collected.
|
*/
$config['cubes'] = array(
);

/*
|--------------------------------------------------------------------------
| Preset dimensions
|--------------------------------------------------------------------------
|
| These are commonly used dimensions that you don't need to repeat
| for each cube. Instead, you can re-use them.
|
*/
$config['preset_dimensions'] = array(
    'time' => array(
        'hour' => array(
            'day' => array(
                'month' => array(
                    'year'
                )
            )
        )
    )
);


/*
|--------------------------------------------------------------------------
| DB prefix
|--------------------------------------------------------------------------
|
| Prefix for the OLAP tables in the database.
| Then prefix + names of types of tables.
|
*/
$config['db_prefix'] = 'olap_';
$config['prefix_dimension'] = $config['db_prefix'].'_d_';
$config['prefix_fact'] = $config['db_prefix'].'_f_';