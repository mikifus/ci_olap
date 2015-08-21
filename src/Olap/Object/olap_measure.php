<?php
namespace Olap\Object;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Measure object
 */
class olap_measure
{
    /**
     * The cube to which this
     * measure belongs
     * @var $cube
     */
    protected $cube;
    /**
     * The measure data is its definition,
     * specified in the config file
     * @var $data
     */
    protected $data = array();
    /**
     * Setting initial data
     * @param \Olap\Object\olap_cube $cube
     * @param array $data
     */
    function __construct( $cube, $data )
    {
        $this->cube = $cube;
        $this->data = $data;
    }
    /**
     * Measures have one field, but as this class
     * is extended to dimensions, the methods are
     * called the same.
     * 
     * It returns the measure field, with, optionally,
     * a table prefix.
     * 
     * @param string $table
     * @return string
     */
    function first_field( $table = '' )
    {
        $prefix = !empty($table) ? $table . '.' : '';
        return $prefix . $this->data;
    }
    /**
     * List of fields on this class. With
     * optional prefix.
     * @param string $table
     * @return array[string]
     */
    function fields( $table = '' )
    {
        return (array) $this->first_field( $table );
    }
}