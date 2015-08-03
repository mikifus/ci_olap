<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
        $fields = array();
        if( $this->preset )
        {
            foreach( $this->data['dimensions'] as $key => $subd )
            {
                if( !empty($subd['fields']) )
                {
                    $fields = array_merge($fields, $subd['fields']);
                }
            }
        }
        $result = array();
        if( !empty( $fields ) )
        {
            foreach( $fields as $f )
            {
                $result[] = $prefix . $f;
            }
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
    function hierarchy()
    {
        $list = array();
        $current = $this->hierarchy_down();
        if( empty($current) )
        {
            // It returns itself in the list
            return array( $this );
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
            return null;
        }
        if( $this->preset )
        {
            $next_level = reset( $this->data['hierarchy'] );
            $dimension = $this->data['dimensions'][ $next_level ];
            return new olap_dimension( $this->cube, $dimension );
        }
        $next_level = reset( $this->data['hierarchy'] );
        new olap_dimension( $this->cube, $this->cube->dimension( $next_level ) );
    }
}