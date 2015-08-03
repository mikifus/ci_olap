 <?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* OLAP library
*/
namespace Olap;

use \Olap\olap_query;
use \Olap\olap_cube;
use \Olap\olap_measure;
use \Olap\olap_dimension;

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
    private function cube_info( $fact_name )
    {
        foreach( $this->cubes as $c )
        {
            if( $c['fact'] == $fact_name )
            {
                return $c;
            }
        }
        return array();
    }
    
    public function get_cube( $fact_name )
    {
        $cube_info = $this->cube_info( $fact_name );
        return new olap_cube( $cube_info, $this->preset_dimensions );
    }
    
    public function query( $query )
    {
        $q      = new olap_query ( $this->db ) ;
        $query  = $q->parse( $query );
        
        $cube   = $this->get_cube( $query['fact'] );
        
        $result = array();
        if( !empty($cube) )
        {
            switch ( $query['action'] )
            {
                case 'aggregate':
                    $q->aggregate( $cube );
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