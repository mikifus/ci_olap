<?php
namespace Olap\Object;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * The dimensions can be used as measures
 * but they as well implement hierarchy methods.
 *
 */
class olap_dimension extends olap_measure
{
    /**
     * A cube is required to initiate the class.
     *
     * @param \Olap\Object\olap_cube $cube
     * @param array $data
     */
    function __construct( $cube, $name, $data )
    {
        parent::__construct( $cube, $name, $data );
    }
    /**
     * List of fields, can optionally add a prefix.
     * @param string $table
     * @return array[string]
     */
    function fields( $table = '' )
    {
        $prefix = !empty($table) ? $table . '.' : '';
        $result = array();
        foreach( $this->data['fields'] as $f )
        {
            $result[] = $prefix . $f;
        }
        return $result;
    }
    /**
     * Only the first field, as specified in the config.
     * Can optionally add a prefix.
     * @param string $table
     * @return string
     */
    function first_field( $table = '' )
    {
        $prefix = !empty($table) ? $table . '.' : '';
        if( empty( $this->data['fields'] ) )
        {
            return NULL;
        }
        return $prefix . reset( $this->data['fields'] );
    }
    /**
     * When in an insert, dimensions do not always
     * use their fields directly but an alias. This
     * depends on configuration.
     * @param string $table
     * @return string
     */
    function insert_field( $table = '' )
    {
        $prefix = !empty($table) ? $table . '.' : '';
        if( isset( $this->data['unified_field'] ) )
        {
            return $prefix . $this->data['unified_field'];
        }
        return $this->first_field( $table );
    }
    /**
     * From the current dimension, it returns
     * all the ones under the same hierarchy.
     * Or an empty array.
     *
     * @return array
     */
    function hierarchy()
    {
        $list = array( $this ); // The first (zero) position refers to itself
        $current = $this->hierarchy_down();
        if( is_null($current) )
        {
            return array();
        }
        $list[] = $current;
        while( $next = $current->hierarchy_down() )
        {
            $current = $next;
            $list[] = $current;
        }
        return $list;
    }
    /**
     * Returns the next dimension in the current hierarchy
     * @return \Olap\Object\olap_dimension
     */
    function hierarchy_down()
    {
        if( !is_array($this->data['hierarchy']) )
        {
            return NULL;
        }
        $next_level = reset( $this->data['hierarchy'] );
        return $this->cube->dimension( $next_level );
    }

    function name() {
        return $this->name;
    }
}