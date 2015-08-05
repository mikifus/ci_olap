<?php
namespace Olap;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class olap_dimension extends olap_measure
{
    private $preset = false;
    
    function __construct( $cube, $data )
    {
        parent::__construct( $cube, $data );
        $this->preset = $data['preset'];
    }
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
    function first_field( $table = '' )
    {
        $prefix = !empty($table) ? $table . '.' : '';
        if( empty( $this->data['fields'] ) )
        {
            return NULL;
        }
        return $prefix . reset( $this->data['fields'] );
    }
    function insert_field( $table = '' )
    {
        $prefix = !empty($table) ? $table . '.' : '';
        if( isset( $this->data['unified_field'] ) )
        {
            return $prefix . $this->data['unified_field'];
        }
        return $this->first_field( $table );
    }
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
    function hierarchy_down()
    {
        if( !is_array($this->data['hierarchy']) )
        {
            return NULL;
        }
        $preset = NULL;
        $next_level = reset( $this->data['hierarchy'] );
        return $this->cube->dimension( $next_level );
    }
}