<?php
namespace Olap;

include_once 'olap_cube.php';
include_once 'olap_measure.php';
include_once 'olap_dimension.php';

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Like Cubes toolkit
* 
* @see https://en.wikipedia.org/wiki/Cubes_(OLAP_server)
* @param  string $query
* @return array
*/
namespace Olap\olap_query;

class olap_query
{
    private $sql_params = array(
                'select' => array(),
                'where_in' => array(),
                'group_by' => array(),
                'order_by' => array()
            );
            
    function __construct( $db )
    {
        $this->db = $db;
        $config = get_instance()->config->item('olap');
        $this->prefix           = $config['db_tables_prefix'];
        $this->prefix_fact      = $config['prefix_fact'];
        $this->prefix_dimension = $config['prefix_dimension'];
        $this->default_pagesize = $config['default_pagesize'];
    }
    private function _merge_sql_param( $paramname, $info )
    {
        $this->sql_params[$paramname] = array_merge( $this->sql_params[$paramname], $info );
    }
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
     * Parses a query
     * 
     * @see http://es.slideshare.net/Stiivi/cubes-7781602
     * @param  string $query
     * @return array
     */
    public function parse( $query )
    {
        $result = array();
        $divide = explode( '?', trim($query) );
        $parts  = explode( '/', $divide[0]);
        parse_str( $divide[1], $params );
        
        $fact   = array_shift( $parts );
        $action = array_shift( $parts );
        
        if( !empty($params['cut']) )
        {
            $cut_params = explode('|', $params['cut']);
            $params['cut'] = array();
            foreach( $cut_params as $cp )
            {
                $cp   = explode(':', $cp);
                $params['cut'][] = array(
                    'dimension' => $cp[0],
                    'path'      => explode(',', $cp[1])
                );
            }
        }
        if( !empty($params['order']) )
        {
            $orders = explode(',', $params['order']);
             $params['order'] = array();
            foreach( $orders as $order_data )
            {
                $od = explode(':', $order_data);
                if( empty($od[1]) )
                {
                    $od[1] = 'asc';
                }
                $params['order'][] = array('name' => $od[0], 'order' => strtoupper( $od[1] ) );
            }
        }
        $limit = array(
            'page'     => 1,
            'pagesize' => $this->default_pagesize
        );
        if( !empty($params['page']) )
        {
            $limit['page'] = abs($params['page']);
        }
        if( !empty($params['pagesize']) )
        {
            $limit['pagesize'] = abs($params['pagesize']);
        }
        $params['limit'] = $limit;
        
        return array(
            'fact'    => $fact,
            'action'  => $action,
            'params'  => $params
        );
    }
    public function aggregate( $cube )
    {
        $t_fact = $this->prefix_fact.$cube->fact;
        $select = array();
        // COUNT
        $select[] = "count(".$t_fact.") as ".$cube->fact()."_count";
        foreach( $cube->measures() as $m )
        {
            // SUM
            $select[] = "sum(".$m->first_field($t_fact).") as ".$m->first_field()."_sum";
        }
        $this->select( $select );
        foreach( $cube->dimensions() as $dimension )
        {
            $this->select  ( $dimension->fields( $t_fact ) );
            $this->group_by( (array) $dimension->first_field( $t_fact ) );
        }
    }
    public function select_all( $cube )
    {
        $t_fact = $this->prefix_fact.$cube->fact;
        
        $this->select( $cube->get_all_fields() );
        $this->group_by( $cube->get_all_fields() );/*
        
        foreach( $cube->dimensions() as $dimension )
        {
            $this->select  ( $dimension->fields( $t_fact ) );
            $this->group_by( (array) $dimension->first_field( $t_fact ) );
        }
        $this->select( $cube->measures_fields( $t_fact ) );
        $this->group_by( $cube->measures_fields( $t_fact ) );*/
    }
    public function cut( $cube, $cut )
    {
        $t_fact = $this->prefix_fact.$cube->fact;
        $group_bys = array();
        $where_ins = array();
        foreach( $cut as $cp )
        {
            if( $dimension = $cube->dimension( $cp['dimension'] ) )
            {
                $h = $dimension->hierarchy();
                if( empty($h) )
                {
                    continue;
                }
                foreach( $cp['path'] as $c => $step )
                {
                    if( empty($step) )
                    {
                        continue;
                    }
                    $level = $h[ $c ];
                    $where_ins[] = array( $level->first_field( $t_fact ), $step );
                }
                // We can keep the last field of the hierarchy to group by
                $group_bys[] = $level->first_field( $t_fact );
            }
            // It might be a measure to cut by!
            else if( $measure = $cube->measure( $cp['dimension'] ) )
            {
                $where_ins[] = array( $measure->first_field( $t_fact ), $cp['path'] );
            }
        }
        
        $this->where_in( $where_ins );
        $this->group_by( $group_bys );
    }
    public function order( $cube, $order_param )
    {
        $t_fact = $this->prefix_fact.$cube->fact;
        $order_bys = array();
        foreach( $order_param as $order )
        {
            if( $dimension = $cube->dimension( $order['name'] ) )
            {
                // It is a dimension
                $order_bys[] =  $dimension->first_field( $t_fact ) . " " . $order['order'];
            }
            else if( $measure = $cube->measure( $order['name'] ) )
            {
                $order_bys[] = $measure->first_field( $t_fact ) . " " . $order['order'];
            }
            else
            {
                foreach( $cube->order() as $cube_order )
                {
                    $order_bys[] = $cube_order['name'] . " " . $cube_order['order'];
                }
            }
        }
        $this->order_by( $order_bys );
    }
    public function limit( $limit )
    {
        $start = ( $limit['page'] - 1 ) * $limit['pagesize'];
        $end   = $start + $limit['pagesize'];

        $this->sql_params['limit'] = array(
            'limit'  => $end,
            'offset' => $start
        );
    }
    public function result( $cube )
    {
        $t_fact = $this->prefix_fact.$cube->fact;
        
        extract( $this->sql_params );
        
        if( !empty($select) )
        {
            foreach( $select as $s )
            {
                $this->db->select( $s );
            }
        }
        if( !empty($where_in) )
        {
            foreach( $where_in as $wi )
            {
                $this->db->where_in( $wi[0], $wi[1] );
            }
        }
        if( !empty($group_by) )
        {
            $this->db->group_by( $group_by );
        }
        if( !empty($order_by) )
        {
            $this->db->order_by( implode(',', $order_by) );
        }
        if( !empty($limit) )
        {
            $this->db->offset( $limit['offset'] );
            $this->db->limit( $limit['limit'] );
        }
        
        $this->db->from( $t_fact );
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
    public function procedure( $cube, $args )
    {
        $params_place = array();
        $fields = $cube->get_procedure_fields();
        if( count($fields) != count($args) )
        {
            log_message('debug', 'Olap: Data compilation failed (wrong parameters).');
            throw new Exception("Olap: Data compilation failed (wrong parameters).");
        }
        $procedure_name = $cube->fact( $this->prefix_fact );
        $procedure = $this->make_procedure( $procedure_name, $args );
        return $this->db->query('SELECT '.$procedure.";", $args);
    }
    private function make_procedure( $procedure_name, $arguments )
    {
        for( $i = count( $arguments ); $i; --$i ) { $params_place [] = '?'; }
        return $procedure_name . "( " . implode(',', $params_place) . " )";
    }
}