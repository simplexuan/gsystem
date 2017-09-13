<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2006 - 2011 EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Memcached Caching Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link
 */

class CI_Cache_memcached extends CI_Driver {

	private $_memcached;	// Holds the memcached object

	protected $_memcache_conf 	= array(
					'default' => array(
						'default_host'		=> '127.0.0.1',
						'default_port'		=> 11211,
						'default_weight'	=> 1
					)
				);


    
    // ------------------------------------------------------------------------
    protected function _filter_key($key){
        return base64_encode($key);
    }
	/**
	 * Cache error message
	 *
	 * @return 	mixed		message
	 */
    public function error_message() {
        return $this->_memcached->getResultMessage();
    }
	/**
	 * Cache error code
	 *
	 * @return 	mixed		code
	 */
    public function error_code() {
        return $this->_memcached->getResultCode();
    }

    // ------------------------------------------------------------------------

	/**
	 * Fetch from cache
	 *
	 * @param 	mixed		unique key id
	 * @return 	mixed		data on success/false on failure
	 */
	public function get($id)
	{
        $id = $this->_filter_key($id);
        return $data =  $this->_memcached->get($id);
	}
    function _de_filter_key($id){
         return base64_decode($id);
    }
	/**
	 * Fetch Multi
	 *
	 * @param 	mixed		array
	 * @return 	mixed		data on success/false on failure
	 */
    public function getMulti($param)
    {
        foreach($param as $k=>$id){
            $param[$k] = $this->_filter_key($id);
        }
        if (get_class($this->_memcached) == 'Memcached')
        {
            $results = $this->_memcached->getMulti($param);
            $new_results = array();
            foreach ($results as $key=>$v){
                $new_results[$this->_de_filter_key($key)] = $v;
            }
            unset($results);
            
            return $new_results;
        }

        return FALSE;
    }

	// ------------------------------------------------------------------------

	/**
	 * Save
	 *
	 * @param 	string		unique identifier
	 * @param 	mixed		data being cached
	 * @param 	int			time to live
	 * @return 	boolean 	true on success, false on failure
	 */
	public function save($id, $data, $ttl = 0)
	{
        $raw = $id;
        $id = $this->_filter_key($id);
        //log_message('DEBUG', sprintf('%s memcached real key:%s raw key:%s', __FUNCTION__, $id, $raw));
		if (get_class($this->_memcached) == 'Memcached')
		{
			return $this->_memcached->set($id, $data, $ttl);
		}
		else if (get_class($this->_memcached) == 'Memcache')
		{
			return $this->_memcached->set($id, array($data, time(), $ttl), 0, $ttl);
		}

		return FALSE;
	}
	public function replace($id, $data, $ttl = 60)
	{		
        $raw = $id;
        $id = $this->_filter_key($id);
        //log_message('DEBUG', sprintf('%s memcached real key:%s raw key:%s', __FUNCTION__, $id, $raw));
		$result = $this->_memcached->replace($id, $data, $ttl);
		if($result === FALSE)
		{
			$result = $this->replace($id, $data, $ttl);
		}
		
		return $result;
	}

	/**
	 * Save Multi
	 *
	 * @param 	mixed		data being cached
	 * @param 	int			time to live
	 * @return 	boolean 	true on success, false on failure
	 */
    public function setMulti($param, $ttl = 0)
    {
        $new_param = array();
        foreach($param as $id=>$v){
            $new_id = $this->_filter_key($id);
            //log_message('DEBUG', sprintf('%s memcached real key:%s raw id:%s', __FUNCTION__, $new_id, $id));
            $new_param[$new_id] = $v;
        }
        unset($param);
        if (get_class($this->_memcached) == 'Memcached')
        {
            return $this->_memcached->setMulti($new_param, $ttl);
        }

        return FALSE;
    }

	// ------------------------------------------------------------------------

	/**
	 * Delete from Cache
	 *
	 * @param 	mixed		key to be deleted.
	 * @return 	boolean 	true on success, false on failure
	 */
	public function del($id)
	{
        $raw = $id;
        $id = $this->_filter_key($id);
        //log_message('DEBUG', sprintf('%s memcached real key:%s raw key:%s', __FUNCTION__, $id, $raw));
		return $this->_memcached->delete($id, 0 );
	}
    /**
     * delMulti 
     * 
     * @param mixed $param 
     * @access public
     * @return mixed
     */
    function delMulti($param)
    {
        foreach($param as $k=>$id){
            $param[$k] = $this->_filter_key($id);
        }
        return $this->_memcached->deleteMulti($param);
    }

    /**
     * add 
     * 
     * @param mixed $id 
     * @access public
     * @return mixed
     */
	public function add($id, $data=1, $ttl=0)
	{
        $id = $this->_filter_key($id);
		return $this->_memcached->add($id,$data, $ttl);
	}
	// ------------------------------------------------------------------------

	/**
	 * Clean the Cache
	 *
	 * @return 	boolean		false on failure/true on success
	 */
	public function clean()
	{
		return $this->_memcached->flush();
	}

	// ------------------------------------------------------------------------

	/**
	 * Cache Info
	 *
	 * @param 	null		type not supported in memcached
	 * @return 	mixed 		array on success, false on failure
	 */
	public function cache_info($type = NULL)
	{
		return $this->_memcached->getStats();
	}

	// ------------------------------------------------------------------------

	/**
	 * Get Cache Metadata
	 *
	 * @param 	mixed		key to get cache metadata on
	 * @return 	mixed		FALSE on failure, array on success.
	 */
	public function get_metadata($id)
	{
        $id = $this->_filter_key($id);
		$stored = $this->_memcached->get($id);

		if (count($stored) !== 3)
		{
			return FALSE;
		}

		list($data, $time, $ttl) = $stored;

		return array(
			'expire'	=> $time + $ttl,
			'mtime'		=> $time,
			'data'		=> $data
		);
	}

	// ------------------------------------------------------------------------

	/**
	 * Setup memcached.
	 */
	public function _setup_memcached()
	{
		// Try to load memcached server info from the config file.
		$CI =& get_instance();
		if ($CI->config->load('memcached', TRUE, TRUE))
		{
			if (is_array($CI->config->config['memcached']))
			{
				$this->_memcache_conf = NULL;

				foreach ($CI->config->config['memcached'] as $name => $conf)
				{
					$this->_memcache_conf[$name] = $conf;
				}
			}
		}
		$this->_memcached = new Memcached();

        $cache_servers = array();
        foreach ($this->_memcache_conf['memcached']['servers'] as $name => $cache_server)
        {

            if ( ! array_key_exists('host', $cache_server))
            {
                $cache_server['host'] = $this->_memcache_conf['memcached']['servers']['default']['host'];
            }

            if ( ! array_key_exists('port', $cache_server))
            {
                $cache_server['port'] = $this->_memcache_conf['memcached']['servers']['default']['port'];
            }

            if ( ! array_key_exists('weight', $cache_server))
            {
                $cache_server['weight'] = $this->_memcache_conf['memcached']['servers']['default']['weight'];
            }
            $cache_servers[] = array_values($cache_server);

        }
        $this->_memcached->addServers($cache_servers);
        //if ($this->_memcache_conf['memcached']['config']['prefix']){
        //    $this->_memcached->setOption(Memcached::OPT_PREFIX_KEY, $this->_memcache_conf['memcached']['config']['prefix']);
        //}
	}

	// ------------------------------------------------------------------------


	/**
	 * Is supported
	 *
	 * Returns FALSE if memcached is not supported on the system.
	 * If it is, we setup the memcached object & return TRUE
	 */
	public function is_supported()
	{
		if ( ! extension_loaded('memcached'))
		{
			log_message('error', 'The Memcached Extension must be loaded to use Memcached Cache.');

			return FALSE;
		}

		$this->_setup_memcached();
		return TRUE;
	}

	// ------------------------------------------------------------------------

}
// End Class

/* End of file Cache_memcached.php */
/* Location: ./system/libraries/Cache/drivers/Cache_memcached.php */
