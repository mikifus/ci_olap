<?php
defined('BASEPATH') OR exit('No direct script access allowed');

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
    array(
        'fact' => 'sale',
        'measures' => array( 'price' ),
        'dimensions' => array(
                'shop'    => array('id_shop'),
                'product' => array('id_product'),
                'time'
            )
    )
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
        'year' => array(
            'fields'    => array('year'),
            'unified_field' => 'time',
            'hierarchy' => array('month')
        ),
        'month' => array(
            'fields'    => array('month'),
            'unified_field' => 'time',
            'hierarchy' => array('day')
        ),
        'day' => array(
            'fields'    => array('day'),
            'unified_field' => 'time',
            'hierarchy' => array('hour')
        ),
        'hour' => array(
            'fields'    => array('hour'),
            'unified_field' => 'time',
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
$config['dimension_prefix'] = $config['db_prefix'].'_d_';
$config['fact_prefix'] = $config['db_prefix'].'_f_';