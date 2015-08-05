<?php
namespace Olap;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class olap_measure
{
    function __construct( $cube, $data )
    {
        $this->cube = $cube;
        $this->data = $data;
    }
    function first_field( $table = '' )
    {
        $prefix = !empty($table) ? $table . '.' : '';
        return $prefix . $this->data;
    }
    function fields( $table = '' )
    {
        return (array) $this->first_field( $table );
    }
}