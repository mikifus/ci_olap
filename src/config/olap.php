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
| Dimensions
|--------------------------------------------------------------------------
|
|
|
*/
$config['dimensions'] = array(
    'time' => array(
        'add_field' => 'datetime',
        'unified_field' => 'id_time',
        'time.datetime' => array(
            'fields'    => array('datetime'),
            'unified_field' => 'time'
        ),
        'time.year' => array(
            'fields'    => array('year'),
            'hierarchy' => array('month'),
            'unified_field' => 'time'
        ),
        'time.month' => array(
            'fields'    => array('month'),
            'hierarchy' => array('month_day'),
            'unified_field' => 'time'
        ),
        'time.month_day' => array(
            'fields'    => array('month_day'),
            'hierarchy' => array('hour'),
            'unified_field' => 'time'
        ),
        'time.hour' => array(
            'fields'    => array('hour'),
            'unified_field' => 'time'
        ),
        'time.minute' => array(
            'fields'    => array('minute'),
            'unified_field' => 'time'
        ),
        'time.second' => array(
            'fields'    => array('second'),
            'unified_field' => 'time'
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