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
     * Gets the CI instance.
     * Gets the db.
     */
    public function __construct()
    {
        $this->_CI =& get_instance();
        $this->db = $this->_CI->db;
    }
}