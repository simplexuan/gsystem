<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Security Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/helpers/encrypt_helper.html
 */

// ------------------------------------------------------------------------

/**
 * encrypt
 *
 * @access	public
 * @param	string
 * @param	bool	whether or not the content is an image file
 * @return	string
 */
if ( ! function_exists('encrypt'))
{
	function encrypt($str, $key)
	{
		$CI =& get_instance();
        $CI->load->library('encrypt');
		return $CI->encrypt->encode($str, $key);
	}
}

if ( ! function_exists('decrypt'))
{
	function decrypt($str, $key)
	{
		$CI =& get_instance();
        $CI->load->library('encrypt');
		return $CI->encrypt->decode($str, $key);
	}
}
if ( ! function_exists('password'))
{
	function password($str, $salt)
    {
        return md5($str . md5($salt));
    }
}


/* End of file encrypt_helper.php */
/* Location: ./system/helpers/encrypt_helper.php */
/*  vim: set ts=4 sw=4 sts=4 tw=100 noet: */
