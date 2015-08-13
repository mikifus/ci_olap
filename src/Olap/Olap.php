<?php
/**
* OLAP library
*/
namespace Olap;

include_once 'olap_query.php';

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
     * Gets the CI instance.
     * Gets the db.
     */
    public function __construct()
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
            $this->preset_dimensions = $config['preset_dimensions'];
            $this->default_pagesize = $config['default_pagesize'];
        }
        $this->db = $this->_CI->db;
    }
    
    /**
     * Saves a new record in a fact table.
     * Arguments can be variable. They depend on the amount
     * of measures and dimensions.
     * 
     * @param string $fact_name: First argument defines the rest
     */
    public function add()
    {
        $args      = func_get_args();
        $fact_name = array_shift($args); // Extract first
        if( $this->cube_exists( $fact_name ) )
        {
            return $this->_add( $fact_name, $args );
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
    private function _add( $fact_name, $args )
    {
        $cube   = $this->get_cube( $fact_name );
        $q      = new olap_query ( $this->db ) ;
        return $q->procedure( $cube, $args );
    }

    /**
     * Returns the config info of a cube.
     * 
     * @param  string $fact_name
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
            throw new Exception("Olap library: No cube found.");
        }
        foreach( $cube_info['dimensions'] as $pos => $dimension )
        {
            if( is_string( $dimension ) && isset( $this->preset_dimensions[ $dimension ] ) )
            {
                unset( $cube_info['dimensions'][ $pos ] );
                foreach( $this->preset_dimensions[ $dimension ] as $name => $preset )
                {
                    $cube_info['dimensions'][ $name ] = $preset;
                }
            }
        }
        return $cube_info;
    }
    
    public function get_cube( $view_name )
    {
        $cube_info = $this->cube_info( $view_name );
        return new olap_cube( $cube_info, $this->preset_dimensions );
    }
    
    public function query( $query, $return = false )
    {
        $q      = new olap_query ( $this->db ) ;
        $query  = $q->parse( $query );
        
        $cube   = $this->get_cube( $query['view'] );
        
        $result = array();
        if( !empty($cube) )
        {
            switch ( $query['action'] )
            {
                case 'aggregate':
                    $q->aggregate( $cube );
                break;
                case 'count':
                    $q->count( $cube, $query['params']['count'] );
                break;
                default:
                    $q->select_all( $cube );
            }
            if( !empty( $query['params']['cut'] ) )
            {
                $q->cut( $cube, $query['params']['cut'] );
            }
            if( !empty( $query['params']['order'] ) )
            {
                $q->order( $cube, $query['params']['order'] );
            }
            $q->limit( $query['params']['limit'] );
            if( $return )
            {
                return $q->get_compiled_query( $cube );
            }
            $result = $q->result( $cube );
            // WARNING: DEBUG
            $this->last_query = $this->db->last_query();
            if( $result === FALSE )
            {
                throw new Exception("Olap library: The database query failed!");
            }
        }
        return $result;
    }
}