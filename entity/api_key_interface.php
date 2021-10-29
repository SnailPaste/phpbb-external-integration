<?php
/**
 *
 * phpBB External Integration. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2021, Scott Wichser, https://github.com/blast007
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace snailpaste\phpbbexternalintegration\entity;

/**
 * Interface for an API key
 *
 * This describes all of the methods we have for an API key
 */
interface api_key_interface
{
    /**
     * Load the data from the database for an API key
     *
     * @param int $id API key identifier
     * @param string $api_key API key
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\out_of_bounds
     */
    public function load($id = 0, $api_key = '');

    /**
     * Import data for an API key
     *
     * Used when the data is already loaded externally.
     * Any existing data for this API key is over-written.
     * All data is validated and an exception is thrown if any data is invalid.
     *
     * @param array $data Data array, typically from the database
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\base
     */
    public function import($data);

    /**
     * Insert the API key data for the first time
     *
     * Will throw an exception if the API key was already inserted (call save() instead)
     *
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\out_of_bounds
     */
    public function insert();

    /**
     * Save the current data to the database
     *
     * This must be called before closing or any changes will not be saved!
     * If adding an API key (saving for the first time), you must call insert() or an exeception will be thrown
     *
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\out_of_bounds
     */
    public function save();

    /**
     * Get id
     *
     * @return int API key identifier
     * @access public
     */
    public function get_id();

    /**
     * Get name
     *
     * @return string name
     * @access public
     */
    public function get_name();

    /**
     * Set name
     *
     * @param string $name
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\unexpected_value
     */
    public function set_name($name);

    /**
     * Get key
     *
     * @return string key
     * @access public
     */
    public function get_key();

    /**
     * Set key
     *
     * @param string $key key
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\unexpected_value
     */
    public function set_key($key);

    /**
     * Get allowed IPs
     *
     * @return string allowed_ips
     * @access public
     */
    public function get_allowed_ips();

    /**
     * Set allowed IPs
     *
     * @param string $allowed_ips allowed IPs
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\unexpected_value
     */
    public function set_allowed_ips($allowed_ips);

    /**
     * Get registration permission
     *
     * @return bool perm_register
     * @access public
     */
    public function register_allowed();

    /**
     * Set registration permission
     *
     * @param bool $perm_register registration permission
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\unexpected_value
     */
    public function set_perm_register($perm_register);

    /**
     * Get login permission
     *
     * @return bool perm_register
     * @access public
     */
    public function login_allowed();

    /**
     * Set registration permission
     *
     * @param bool $perm_login login permission
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\unexpected_value
     */
    public function set_perm_login($perm_login);
}