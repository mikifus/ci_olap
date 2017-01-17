<?php
namespace Olap\Object;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Olap cube object
 *
 */
class olap_cube
{
    private $measures = array();
    private $dimensions = array();
    private $views = array();
    private $order;
    private $current_view = '';

    function __construct( $data, $preset )
    {
        $this->fact  = $data['fact'];
        $this->views = $data['views'];
        $this->set_view( $data['view'] );
        foreach( $data['measures'] as $name => $measure )
        {
            $this->measures[ $measure ] = new olap_measure( $this, $name, $measure );
        }
        foreach( $data['dimensions'] as $name => $dimension )
        {
            $this->dimensions[ $name ] = new olap_dimension( $this, $name, $dimension );
        }
//        foreach( $preset as $dimension_prop => $dimension )
//        {
//            $this->dimensions[ $dimension_prop ] = new olap_dimension( $this, $dimension );
//        }
        $this->order = $data['order'];
    }
    /**
     * If the cube was invoked with a view name
     * it must be indicated.
     * @param string $view_name
     */
    function set_view( $view_name )
    {
        $this->current_view = $view_name;
    }
    /**
     * Returns the currently set view
     * @return string
     */
    function current_view()
    {
        return $this->current_view;
    }
    /**
     * Returns the fact of the cube, wuth a
     * prefix if required
     * @param string $prefix
     * @return string
     */
    function fact( $prefix = '' )
    {
        $prefix = !empty($prefix) ? $prefix : '';
        return $prefix . $this->fact;
    }
    /**
     * Returns the list of dimensions of this cube
     * @return array
     */
    function dimensions()
    {
        return $this->dimensions;
    }
    /**
     * Returns a dimension of the cube, by name
     * @param string $dimenstion_name
     * @return array
     */
    function dimension( $dimenstion_name )
    {
        return $this->dimensions[ $dimenstion_name ];
    }
    /**
     * Returns the list of measures in this cube
     * @return array
     */
    function measures()
    {
        return $this->measures;
    }
    /**
     * Returns a measure by name
     * @param string $measure_name
     * @return array
     */
    function measure( $measure_name )
    {
        return $this->measures[ $measure_name ];
    }
    /**
     * Returns the database fields of all measures.
     * A table can be specified to use views
     * @param string $table
     * @return array[string]
     */
    function measures_fields( $table = '' )
    {
        return $this->_extract_fields( 'measures', $table );
    }
    /**
     * Same as @method measure_fields but with dimensions
     * @param string $table
     * @return array[string]
     */
    function dimensions_fields( $table = '' )
    {
        return $this->_extract_fields( 'dimensions', $table );
    }
    /**
     * Auxiliar function to extract the fields
     * of dimensions or measures
     * @param string $from
     * @param string $table
     * @return arrray
     */
    private function _extract_fields( $from, $table )
    {
        $prefix = !empty($table) ? $table . '.' : '';
        $list = array();
        foreach( $this->{ $from } as $m )
        {
            $list =  array_merge( $list, $m->fields($prefix) );
        }
        return $list;
    }
    /**
     * Measures' fields in procedures.
     * Currently it just calls @method measure_fields
     * @alias measure_fields
     * @param string $table
     * @return array
     */
    function measures_procedure_fields( $table = '' )
    {
        return $this->measures_fields( $table );
    }
    /**
     * Dimensions' fields are ugly, so the procedure
     * can make it friendlier. This returns
     * the fields of the procedure.
     * @return array
     */
    function dimensions_procedure_fields()
    {
        $dimensions = $this->dimensions();
        $result = array();
        foreach( $dimensions as $d )
        {
            $field = $d->insert_field();
            if( in_array( $field, $result ) )
            {
                continue;
            }
            $result[] = $field;
        }
        return $result;
    }
    /**
     * Returns the order parameters of the cube
     * @return array
     */
    function order()
    {
        return !empty($this->order) ? $this->order : array();
    }
    /**
     * Returns all fields of measures and dimensions
     * @param string $table
     * @return array
     */
    function get_all_fields( $table = '' )
    {
        return array_merge($this->measures_fields( $table ), $this->dimensions_fields( $table ) );
    }
    /**
     * Same as @method get_all_fields but with the
     * procedure fields
     * @param string $table
     * @return array
     */
    function get_procedure_fields( $table = '' )
    {
        return array_merge($this->measures_procedure_fields( $table ), $this->dimensions_procedure_fields( $table ) );
    }
}