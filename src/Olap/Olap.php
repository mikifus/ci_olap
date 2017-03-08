<?php
/**
* OLAP library
*/
namespace Olap;

require 'Object/olap_query.php';
require 'Object/olap_cube.php';
require 'Object/olap_measure.php';
require 'Object/olap_dimension.php';
require 'Object/olap_query_parser.php';

use \Olap\Object\olap_query;
use \Olap\Object\olap_cube;
use \Olap\Object\olap_measure;
use \Olap\Object\olap_dimension;
use \Olap\Object\olap_query_parser;


if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Olap
{
    /**
     * CodeIgniter instance
     * @var $_CI
     */
    private $_CI;

    /**
     * Stores the configured cubes
     * @var $cubes
     */
    private $cubes = array();

    /**
     * Prefix for the OLAP tables
     * @var $prefix
     * @var $prefix_fact
     * @var $prefix_dimension
     */
    private $prefix = '';
    private $prefix_fact = '';
    private $prefix_dimension = '';

    /**
     * Errors that happen during a query
     * @var $cubes
     */
    private $_errors = array();

    /**
     * Gets the CI instance.
     * Gets the db.
     */
    public function __construct( $db = null )
    {
        $this->_CI =& get_instance();
        log_message('debug', 'Olap: Library initialized.');
        if( $this->_CI->config->load('olap', TRUE, TRUE) ){
            log_message('debug', 'Olap: config loaded from config file.');
            $config       = $this->_CI->config->item('olap');
            $this->cubes  = $config['cubes'];
            $this->prefix           = $config['db_tables_prefix'];
            $this->prefix_fact      = $config['prefix_fact'];
            $this->prefix_dimension = $config['prefix_dimension'];
            $this->prefix_view      = $config['prefix_view'];
//            $this->preset_dimensions = $config['preset_dimensions'];
            $this->dimensions = $config['dimensions'];
            $this->default_pagesize = $config['default_pagesize'];
        }
        $this->db = !is_null($db) ? $db : $this->_CI->db;
    }

    /**
     * Saves a new record in a fact table.
     * Arguments can be variable. They depend on the amount
     * of measures and dimensions.
     *
     * @param string $fact_name: Fact or cube name
     * @param array $parameters: Named parameters for this cube
     */
    public function add( $fact_name, $parameters )
    {
        if( $this->cube_exists( $fact_name ) )
        {
            $cube       = $this->get_cube( $fact_name );
            $insert_params = array();
            foreach( $parameters as $param_name => $value ) {
                if( $cube->is_measure($param_name) ) {
                    $insert_params[ $param_name ] = $value;
                } else {
                    $d = $cube->dimension( $param_name );
                    $insert_params[ $d->insert_field() ] = $value;
                }
            }

            $q = new olap_query ( $this->db ) ;
            return $q->insert_fact( $cube, $insert_params );
        }
        return FALSE;
    }

    /**
     * Checks if a cube exists in the loaded configuration
     *
     * @param  string $fact_name
     * @return boolean
     */
    private function cube_exists( $fact_name )
    {
        $info = $this->cube_info( $fact_name );
        return (bool) !empty( $info );
    }

    /**
     * Prepares the procedure and the data for the database
     * to insert a new record.
     *
     * @param  string $fact_name
     * @param  array  $args
     * @return boolean
     */
//    private function _add( $fact_name, $args )
//    {
//        $cube   = $this->get_cube( $fact_name );
//        $q      = new olap_query ( $this->db ) ;
//        return $q->procedure( $cube, $args );
//    }

    /**
     * Adds a dimension value. Gives back its ID.
     *
     * @param string $fact_name: First argument defines the rest
     */
    public function dim_add( $dim_name, $parameters )
    {
        if( isset( $this->dimensions[ $dim_name ] ) )
        {
            $d = new olap_dimension(null, $dim_name, $this->dimensions[$dim_name]);
            $q      = new olap_query ( $this->db ) ;
            return $q->dim_procedure( $dim_name, $d->dim_insert_field(), (array) $parameters );
        }
        return FALSE;
    }

    /**
     * Returns the config info of a cube.
     *
     * @param  string $view_name
     * @return array
     */
    private function cube_info( $view_name )
    {
        $cube_info = array();
        foreach( $this->cubes as $c )
        {
            if( $c['fact'] == $view_name )
            {
                $cube_info = $c;
                $cube_info['view'] = $this->prefix_fact . $view_name;
                break;
            }
            else if( isset($c['views']) && in_array( $view_name, $c['views'] ) )
            {
                $cube_info = $c;
                $cube_info['view'] = $this->prefix_view . $view_name;
                break;
            }
        }
        if( empty($cube_info) )
        {
            throw new \Exception("Olap library: Cube not found.");
        }
        foreach( $cube_info['dimensions'] as $pos => $dimension )
        {
            if( is_string( $dimension ) && isset( $this->dimensions[ $dimension ] ) )
            {
                $cube_info['dimensions'][ $dimension ] = $this->dimensions[ $dimension ];
//                unset( $cube_info['dimensions'][ $pos ] );
//                foreach( $this->dimensions[ $dimension ] as $name => $preset )
//                {
//                    $cube_info['dimensions'][ $name ] = $preset;
//                }
            }
        }
        return $cube_info;
    }
    /**
     * Returns the cube specified by a view
     * @param string $view_name
     * @return olap_cube
     */
    public function get_cube( $view_name )
    {
        $cube_info = $this->cube_info( $view_name );
        return new olap_cube( $cube_info, $this->dimensions );
    }
    /**
     * Main method of the library.
     * It processes a query string and returns
     * the resulting data.
     *
     * @param string $query
     * @return string|array
     */
    public function query( $query )
    {
        $result = array();
        $q = $this->build_query( $query );
        $result = $q->result();
        $this->_errors = $q->errors();

        // WARNING: DEBUG
        $this->last_query = $this->db->last_query();

        if( $result === FALSE )
        {
            throw new \Exception("Olap library: The database query failed!");
        }
        return $result;
    }

    /**
     * Builds, compiles the database query and returns it as
     * a string.
     * FOr debugging purposes.
     *
     * @param string $query
     * @return string
     */
    public function get_database_query( $query )
    {
        $q = $this->build_query( $query );
        return $q->get_compiled_query();
    }

    /**
     * Builds an olap query object from a query string.
     *
     * @param string $query
     * @return \Olap\Object\olap_query
     */
    private function build_query( $query )
    {
        $parser = new olap_query_parser;
        $q      = new olap_query( $this->db ) ;
        $q_data = $parser->parse( $query );
        $cube   = $this->get_cube( $q_data['view'] );
        if( empty($cube) )
        {
            throw new \Exception("Olap library: Failed to build query.");
        }
        $q->set_cube( $cube );
        $q->build( $q_data );

        return $q;
    }

    /**
     * Getter for the errors array
     * @return array
     */
    public function error() {
        return $this->_errors;
    }
}