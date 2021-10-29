<?php
/**
 *
 * phpBB External Integration. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2021, Scott Wichser, https://github.com/blast007
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace snailpaste\phpbbexternalintegration\operators;

/**
 * Interface for our API keys operator
 *page
 * This describes all of the methods we'll have for working with a set of API keys
 */
interface api_key_interface
{
    /**
     * Get all API keys
     *
     * @return array Array of API key data entities
     * @access public
     */
    public function get_api_keys();

    /**
     * Add an API key
     *
     * @param \snailpaste\phpbbexternalintegration\entity\api_key_interface $entity API key entity with new data to insert
     * @return api_key_interface Added API key entity
     * @throws \snailpaste\phpbbexternalintegration\exception\out_of_bounds
     * @access public
     */
    public function add_api_key($entity);

    /**
     * Delete an API key
     *
     * @param int $api_key_id The API key identifier to delete
     * @return bool True if row was deleted, false otherwise
     * @throws \snailpaste\phpbbexternalintegration\exception\out_of_bounds
     * @access public
     */
    public function delete_api_key(int $api_key_id);

    /**
     * Get an API key entity by using the API key value
     *
     * @param string $api_key_value The API key value
     * @return api_key_interface The API key entity
     * @access public
     */
    public function get_api_key_by_value($api_key_value);

    /**
     * Get an API key entity by using the API key id
     *
     * @param string $api_key_id The API key identifier
     * @return api_key_interface The API key entity
     * @access public
     */
    public function get_api_key($api_key_id);
}
