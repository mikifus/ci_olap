<?php
namespace Olap\Object;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
* Parses an OLAP query so an array of parameters is
* generated to be used by the \olap_query class
*/
class olap_query_parser
{
    /**
     * The default pagesize is set in the config file.
     * This parameter must be set depending on
     * resources availability.
     * A parameter in the query can be added to
     * use another page size.
     * @var $default_pagesize
     */
    private $default_pagesize = 10;
    /**
     * It takes the configuration from the config
     * file.
     */
    function __construct()
    {
        $config = get_instance()->config->item('olap');
        $this->default_pagesize = $config['default_pagesize'];
    }
    /**
     * Parses a query step by step
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
        
        $view   = array_shift( $parts );
        $action = array_shift( $parts );
        
        $parameters = array();
        
        if( !empty($params['select']) )
        {
            $parameters['select'] = $this->parse_select( $params['select'] );
        }
        if( !empty($params['cut']) )
        {
            $parameters['cut'] = $this->parse_cut( $params['cut'] );
        }
        if( !empty($params['order']) )
        {
            $parameters['order'] = $this->parse_order( $params['order'] );
        }
        if( !empty($params['count']) )
        {
            $parameters['count'] = $this->parse_count( $params['count'] );
        }
        
        $parameters['limit'] = $this->parse_limit( $params['page'], $params['pagesize'] );
        
        return array(
            'view'    => $view,
            'action'  => $action,
            'params'  => $parameters
        );
    }
    /**
     * Select parameter parsing
     * @param array $sel_data
     * @return array
     */
    private function parse_select( $sel_data )
    {
        $sel_params = explode('|', $sel_data);
        return $sel_params;
    }
    /**
     * Cut parameter parsing
     * @param array $cut_data
     * @return array
     */
    private function parse_cut( $cut_data )
    {
        $cut_params = explode('|', $cut_data);
        $cut = array();
        foreach( $cut_params as $cp )
        {
            $cp   = explode(':', $cp);
            $path = isset($cp[1]) ? explode(',', $cp[1]) : array();
            $cut[] = array(
                'dimension' => $cp[0],
                'path'      => $path
            );
        }
        return $cut;
    }
    /**
     * Count parameter parsing
     * @param array $count_data
     * @return array
     */
    private function parse_count( $count_data )
    {
        return $count_data;
    }
    /**
     * Order parameter parsing
     * @param array $order_data
     * @return array
     */
    private function parse_order( $order_data )
    {
        $orders = explode('|', $order_data);
        $order = array();
        foreach( $orders as $order_data )
        {
            $od = explode(':', $order_data);
            if( empty($od[1]) )
            {
                // DESC is default for pagination to the past
                $od[1] = 'desc';
            }
            $order[] = array('name' => $od[0], 'order' => strtoupper( $od[1] ) );
        }
        return $order;
    }
    /**
     * Limit parameter parsing
     * @param int $page
     * @param int $pagesize
     * @return array
     */
    private function parse_limit( $page = 1, $pagesize = 0 )
    {
        // Default values
        $limit = array(
            'page'     => 1,
            'pagesize' => $this->default_pagesize
        );
        if( !empty($page) )
        {
            $limit['page'] = abs($page);
        }
        if( !empty($pagesize) )
        {
            $limit['pagesize'] = abs($pagesize);
        }
        return $limit;
    }
}