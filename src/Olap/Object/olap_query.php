<?php
namespace Olap\Object;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Like Cubes toolkit
* @see https://en.wikipedia.org/wiki/Cubes_(OLAP_server)
* 
* The query object builds database queries from a set of commands
* generated when parsing the query string.
*/
class olap_query
{
    /**
     * Set of parameters that will be
     * used by Active Record
     * @var $sql_params
     */
    private $sql_params = array(
                'select' => array(),
                'where_in' => array(),
                'group_by' => array(),
                'order_by' => array()
            );
    /**
     * The olap cube object
     * @var $cube
     */
    private $cube;
    /**
     * The constructor reads the config file and stores
     * the data in the class instance.
     * 
     * $db is a CI database object.
     *
     * @param database $db
     */
    function __construct( $db )
    {
        $this->db = $db;
        $config = get_instance()->config->item('olap');
        $this->prefix           = $config['db_tables_prefix'];
        $this->prefix_fact      = $config['prefix_fact'];
        $this->prefix_dimension = $config['prefix_dimension'];
    }
    /**
     * Sets the cube to work with
     * @param \Olap\Object\olap_cube
     */
    public function set_cube( $cube )
    {
        $this->cube = $cube;
    }
    /**
     * Builds a query from a data array, mainly
     * created with \olap_query_parser
     * @param array $query_data
     */
    public function build( $query_data )
    {
        if( empty($this->cube) )
        {
            throw new Exception( "Olap library: Can't build query, no cube is set. Please run set_cube() first." );
        }
        switch ( $query_data['action'] )
        {
            case 'aggregate':
                $this->aggregate();
            break;
            case 'count':
                $this->count( $query_data['params']['count'] );
            break;
            case 'all':
                $this->select_all();
            break;
        }
        if( !empty( $query_data['params']['cut'] ) )
        {
            $this->cut( $query_data['params']['cut'] );
        }
        if( !empty( $query_data['params']['order'] ) )
        {
            $this->order( $query_data['params']['order'] );
        }
        $this->limit( $query_data['params']['limit'] );
    }
    /**
     * Adds the parameters into its place in the sql parameters array
     * @param string $paramname
     * @param array $info
     */
    private function _merge_sql_param( $paramname, $info )
    {
        $this->sql_params[$paramname] = array_merge( $this->sql_params[$paramname], $info );
    }
    /**
     * Aliases for each sql parameters setters.
     * The method name is used for this.
     * 
     * @method select
     * @method where_in
     * @method group_by
     * @method order_by
     * 
     * @param array $info
     */
    private function select( $info )
    {
        $this->_merge_sql_param( __FUNCTION__, $info );
    }
    private function where_in( $info )
    {
        $this->_merge_sql_param( __FUNCTION__, $info );
    }
    private function group_by( $info )
    {
        $this->_merge_sql_param( __FUNCTION__, $info );
    }
    private function order_by( $info )
    {
        $this->_merge_sql_param( __FUNCTION__, $info );
    }
    /**
     * This methods adds aggregate functions to sql 'select'.
     * A total amount count is always added. Then a sumatory of all measures.
     */
    public function aggregate()
    {
        $cube = $this->cube;
        $t_fact = $cube->current_view();
        $select = array();
        // COUNT
        $select[] = "count(".$t_fact.") as ".$cube->fact()."_count";
        foreach( $cube->measures() as $m )
        {
            // SUM
            $select[] = "sum(".$m->first_field($t_fact).") as ".$m->first_field()."_sum";
        }
        $this->select( $select );
    }
    /**
     * Adds a count function to sql 'select'.
     * It will count the specified fields, as opposed to
     * aggregate.
     * @param array $fields
     */
    public function count( $fields )
    {
        $cube = $this->cube;
        $t_fact = $cube->current_view();
        $fields = explode('|',$fields);
        foreach( $fields as $fld )
        {
            $select = array("count(".$t_fact.".".$fld.") as ".$fld."_count");
            $this->select( $select );
        }
    }
    /**
     * Adds all measures and dimensions fields to 
     * sql 'select' and 'group_by'.
     */
    public function select_all()
    {
        $cube = $this->cube;
        $t_fact = $cube->current_view();
        $this->select( $cube->get_all_fields() );
        $this->group_by( $cube->get_all_fields() );
    }
    /**
     * OLAP cut command. It fills the sql parameters according
     * to the specified cut. It allows to cut by measure, dimension
     * or other fields. Some require as well to group by (for aggregation).
     * 
     * When cutting, values are as well supported.
     *
     * @param array $cut
     */
    public function cut( $cut )
    {
        $cube = $this->cube;
        $t_fact = $cube->current_view();
        $group_bys = array();
        $where_ins = array();
        foreach( $cut as $cp )
        {
            if( $dimension = $cube->dimension( $cp['dimension'] ) )
            {
                $h = $dimension->hierarchy();
                if( empty($h) )
                {
                    $h = array( $dimension );
                }
                $last_field = $dimension->first_field( $t_fact );
                foreach( $cp['path'] as $c => $step )
                {
                    if( empty($step) )
                    {
                        continue;
                    }
                    $level = $h[ $c ];
                    $last_field = $level->first_field( $t_fact );
                    $where_ins[] = array( $last_field, $step );
                }
                // We can keep the last field of the hierarchy to group by
                $group_bys[] = $last_field;
                
                // We add the cut as info to select
                $this->select( (array) $last_field );
            }
            // It might be a measure to cut by!
            else if( $measure = $cube->measure( $cp['dimension'] ) && !empty($cp['path']) )
            {
                $where_ins[] = array( $measure->first_field( $t_fact ), $cp['path'] );
            }
            else if( $measure = $cube->measure( $cp['dimension'] ) )
            {
                $select[] = $this->select( (array) $measure->first_field( $t_fact ) );
                $group_bys[] = $measure->first_field( $t_fact );
            }
        }
        
        $this->where_in( $where_ins );
        $this->group_by( $group_bys );
    }
    /**
     * If specified, will add the parameters to the sql 'order by'.
     * If not specified or empty, it will use the cube's default order
     * parameters, specified in the config file.
     * 
     * The data can be sorted by measures and dimensions.
     * 
     * If not specified, the direction of the order will be
     * by default DESC (descending).
     * 
     * @param array $order_param
     */
    public function order( $order_param = array() )
    {
        $cube = $this->cube;
        $t_fact = $cube->current_view();
        if( empty($order_param) )
        {
            foreach( $cube->order() as $cube_order )
            {
                $this->order_by( (array) ($cube_order['name'] . " " . $cube_order['order']) );
                $this->group_by( (array) $cube_order['name'] );
            }
        }
        foreach( $order_param as $order )
        {
            if( $dimension = $cube->dimension( $order['name'] ) )
            {
                // It is a dimension
                $field = $dimension->first_field( $t_fact );
                $this->order_by( (array) ($field . " " . $order['order']) );
                $this->group_by( (array) $field );
            }
            else if( $measure = $cube->measure( $order['name'] ) )
            {
                $field = $measure->first_field( $t_fact );
                $this->order_by( (array) ($field . " " . $order['order']) );
                $this->group_by( (array) $field );
            }
        }
    }
    /**
     * Sets ActiveRecord limit and offset command's parameters.
     * For pagination purposes.
     * @param array $limit
     */
    public function limit( $limit )
    {
        $start = ( $limit['page'] - 1 ) * $limit['pagesize'];
        $end   = $start + $limit['pagesize'];

        $this->sql_params['limit'] = array(
            'limit'  => $end,
            'offset' => $start
        );
    }
    /**
     * Compiles the query into ActiveRecord, then gets the
     * result and returns it.
     * Returns FALSE if the query fails.
     * @return array|boolean
     */
    public function result()
    {
        $this->compile_query();
        // Get the data
        $query = $this->db->get();
        if( !$query )
        {
            return FALSE;
        }
        if ($query->num_rows() > 0)
        {
            return $query->result_array();
        }
        return array();
    }
    /**
     * Compiles the query step by step,
     * based on the available parameters.
     */
    private function compile_query()
    {
        $t_fact = $this->cube->current_view();
        
        extract( $this->sql_params );
        
        if( !empty($select) )
        {
            $this->compile_select( $select );
        }
        if( !empty($where_in) )
        {
            $this->compile_where_in( $where_in );
        }
        if( !empty($group_by) )
        {
            $this->compile_group_by( $group_by );
        }
        if( !empty($order_by) )
        {
            $this->compile_order_by( $order_by );
        }
        if( !empty($limit) )
        {
            $this->compile_limit( $limit );
        }
        $this->db->from( $t_fact );
    }
    /**
     * Compilation steps encapsulated
     * 
     * @method compile_select
     * @method compile_where_in
     * @method compile_group_by
     * @method compile_order_by
     * @method compile_limit
     * 
     * @param array $data
     */
    private function compile_select( $data )
    {
        foreach( $data as $params )
        {
            $this->db->select( $params );
        }
    }
    private function compile_where_in( $data )
    {
        foreach( $data as $wi )
        {
            $this->db->where_in( $wi[0], $wi[1] );
        }
    }
    private function compile_group_by( $data )
    {
        $this->db->group_by( $data );
    }
    private function compile_order_by( $data )
    {
        $this->db->order_by( implode(',', $data) );
    }
    private function compile_limit( $data )
    {
        $this->db->offset( $data['offset'] );
        $this->db->limit( $data['limit'] );
    }
    /**
     * Instead of running the query it gets the
     * query string. For debugging purposes.
     * @return string
     */
    public function get_compiled_query()
    {
        $this->compile_query();
        $r = $this->db->get_compiled_select();
        $this->db->reset_query();
        return $r;
    }
    /**
     * Runs the current cube procedure with the specified
     * arguments.
     * @param array $arguments
     * @return string
     */
    public function procedure( $arguments )
    {
        $cube = $this->cube;
        $fields = $cube->get_procedure_fields();
        if( count($fields) != count($arguments) )
        {
            throw new Exception("Olap: Data compilation failed (wrong parameters).");
        }
        $procedure_name = $cube->current_view();
        $procedure = $this->make_procedure( $procedure_name, count($fields) );
        return $this->db->query('SELECT '.$procedure.";", $arguments);
    }
    /**
     * Generates a string to run the procedure in
     * ActiveRecord.
     */
    private function make_procedure( $procedure_name, $count )
    {
        return $procedure_name . "( " . implode(',', array_fill( 0, $count, '?' ) ) . " )";
    }
}