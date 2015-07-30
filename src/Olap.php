 <?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* OLAP library
*/

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
            $query_data = $this->cube_compile( $fact_name, $args );
            $this->db->query('SELECT '.$query_data[0].";", $query_data[1]);
        }
    }
    
    /**
     * Checks if a cube exists in the loaded configuration
     * 
     * @param  string $fact_name
     * @return boolean
     */
    private function cube_exists( $fact_name )
    {
        return ! empty( $this->cube_get( $fact_name ) );
    }
    
    /**
     * Prepares the procedure and the data for the database
     * to insert a new record.
     * 
     * @param  string $fact_name
     * @param  array  $args
     * @return boolean
     */
    private function cube_compile( $fact_name, $args )
    {
        $measures    = $this->cube_measures  ( $fact_name );
        $dimensions  = $this->cube_dimensions( $fact_name );
        $params      = array_merge( $measures, $dimensions );
        
        $params_place = array();
        $params_values = array();
        foreach( $params as $pname )
        {
            $next_val = array_shift( $args );
            if( empty($next_val) )
            {
                log_message('debug', 'Olap: Data compilation failed.');
                throw new Exception("Olap: Data compilation failed.");
                break;
            }
            $params_place [] = '?';
            $params_values[] = $next_val;
        }
        $procedure_name = $this->prefix_fact . $fact_name;
        $params_place   = implode(',', $params_place);
        
        return array(
            $procedure_name. "( " . $params_place . " )",
            $params_values
        );
    }

    private function cube_measures( $fact_name )
    {
        $c = $this->cube_get( $fact_name );
        if( empty($c['measures']) )
        {
            throw new Exception("Olap: No measures in the selected cube.");
        }
        $result = array();
        foreach( $c['measures'] as $name => $value )
        {
            if( is_array($value) )
            {
                $result = array_merge($result, $value);
            }
            else
            {
                $result[] = $value;
            }
        }
        return $result;
    }
    
    private function cube_dimensions( $fact_name )
    {
        $c = $this->cube_get( $fact_name );
        $result = array();
        foreach( $c['dimensions'] as $name => $value )
        {
            if( is_array($value) )
            {
                $result = array_merge($result, $value);
            }
            else
            {
                $result[] = $value;
            }
        }
        return $result;
    }

    /**
     * Returns the config info of a cube.
     * 
     * @param  string $fact_name
     * @return array
     */
    private function cube_get( $fact_name )
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
}