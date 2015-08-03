<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

namespace Olap\olap_cube;

class olap_cube
{
    private $measures = array();
    private $dimensions = array();
    private $order;
    
    function __construct( $data, $presets = array() )
    {
        $this->fact = $data['fact'];
        foreach( $data['measures'] as $measure )
        {
            $this->measures[ $measure ] = new olap_measure( $this, $measure );
        }
        foreach( $data['dimensions'] as $name => $dimension )
        {
            if( is_string( $dimension ) && isset( $presets[ $dimension ] ) )
            {
                $d = new olap_dimension( $this, $presets[ $dimension ] );
                $this->dimensions[ $dimension ] = $d;
                continue;
            }
            $this->dimensions[ $name ] = new olap_dimension( $this, $dimension );
        }
        $this->order = $data['order'];
    }
    function fact( $prefix = '' )
    {
        $prefix = !empty($prefix) ? $prefix : '';
        return $prefix . $this->fact;
    }
    function dimensions()
    {
        return $this->dimensions;
    }
    function dimension( $d )
    {
        return $this->dimensions[ $d ];
    }
    function measures()
    {
        return $this->measures;
    }
    function measure( $m )
    {
        return $this->measures[ $m ];
    }
    function measures_fields( $table = '' )
    {
        return $this->_extract_fields( 'measures', $table );
    }
    function dimensions_fields( $table = '' )
    {
        return $this->_extract_fields( 'dimensions', $table );
    }
    private function _extract_fields( $from, $table )
    {
        $prefix = !empty($table) ? $table . '.' : '';
        $list = array();
        foreach( $this->{ $from } as $m )
        {
            if( $field = $m->first_field() )
            {
                $list[] = $prefix . $field;
            }
        }
        return $list;
    }
    function measures_procedure_fields( $table = '' )
    {
        return $this->measures_fields( $table );
    }
    function dimensions_procedure_fields( $table = '' )
    {
        $dimensions = $this->dimensions();
        $result = array();
        foreach( $dimensions as $d )
        {
            $result[] = $d->first_field();
        }
        return $result;
    }
    function order()
    {
        return !empty($this->order) ? $this->order : array();
    }
    function get_all_fields( $table = '' )
    {
        return array_merge($this->measures_fields( $table ), $this->dimensions_fields( $table ) );
    }
    function get_procedure_fields( $table = '' )
    {
        return array_merge($this->measures_procedure_fields( $table ), $this->dimensions_procedure_fields( $table ) );
    }
}