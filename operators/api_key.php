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

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Operator for a set of API keys
 */
class api_key implements api_key_interface
{
    /** @var \phpbb\cache\driver\driver_interface */
    protected $cache;

    /** @var ContainerInterface */
    protected $container;

    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

    /** @var \phpbb\extension\manager */
    protected $extension_manager;

    /** @var \phpbb\user */
    protected $user;

    /** @var string */
    protected $api_keys_table;

    /**
     * Constructor
     *
     * @param \phpbb\cache\driver\driver_interface $cache                    Cache driver interface
     * @param ContainerInterface                   $container                Service container interface
     * @param \phpbb\db\driver\driver_interface    $db                       Database connection
     * @param \phpbb\extension\manager             $extension_manager        Extension manager object
     * @param \phpbb\user                          $user                     User object
     * @param string                               $api_keys_table           Table name
     * @access public
     */
    public function __construct(\phpbb\cache\driver\driver_interface $cache, ContainerInterface $container, \phpbb\db\driver\driver_interface $db, \phpbb\extension\manager $extension_manager, \phpbb\user $user, $api_keys_table)
    {
        $this->cache = $cache;
        $this->container = $container;
        $this->db = $db;
        $this->extension_manager = $extension_manager;
        $this->user = $user;
        $this->api_keys_table = $api_keys_table;
    }

    /**
     * Get all API keys
     *
     * @return array Array of API key data entities
     * @access public
     */
    public function get_api_keys()
    {
        $entities = array();

        // Load all API keys data from the database
        $sql = 'SELECT api_key_id, api_key_name, api_key_value, api_key_allowed_ips, api_key_perm_register, api_key_perm_login, api_key_perm_manage
			FROM ' . $this->api_keys_table . '
			ORDER BY api_key_id ASC';
        $result = $this->db->sql_query($sql);

        while ($row = $this->db->sql_fetchrow($result))
        {
            // Import each API key row into an entity
            $entities[] = $this->container->get('snailpaste.phpbbexternalintegration.entity')->import($row);
        }
        $this->db->sql_freeresult($result);

        // Return all API key entities
        return $entities;
    }

    /**
     * Add an API key
     *
     * @param \snailpaste\phpbbexternalintegration\entity\api_key_interface $entity API key entity with new data to insert
     * @return \snailpaste\phpbbexternalintegration\entity\api_key_interface Added API key entity
     * @throws \snailpaste\phpbbexternalintegration\exception\out_of_bounds
     * @access public
     */
    public function add_api_key($entity)
    {
        // Insert the API key data to the database
        $entity->insert();

        // Get the newly inserted API key's identifier
        $api_key_id = $entity->get_id();

        // Reload the data to return a fresh API key entity
        return $entity->load($api_key_id);
    }

    /**
     * Delete an API key
     *
     * @param int $api_key_id The API key identifier to delete
     * @return bool True if row was deleted, false otherwise
     * @throws \snailpaste\phpbbexternalintegration\exception\out_of_bounds
     * @access public
     */
    public function delete_api_key($api_key_id)
    {
        // Delete the API key from the database
        $sql = 'DELETE FROM ' . $this->api_keys_table . '
			WHERE api_key_id = ' . (int) $api_key_id;
        $this->db->sql_query($sql);

        // Return true/false if a API key was deleted
        return (bool) $this->db->sql_affectedrows();
    }

    /**
     * Get an API key entity by using the API key value
     *
     * @param string $api_key_value The API key value
     * @return \snailpaste\phpbbexternalintegration\entity\api_key_interface The API key entity
     * @access public
     */
    public function get_api_key_by_value($api_key_value)
    {
        return $this->container->get('snailpaste.phpbbexternalintegration.entity')->load(0, $api_key_value);
    }

    /**
     * Get an API key entity by using the API key id
     *
     * @param int $api_key_id The API key identifier
     * @return \snailpaste\phpbbexternalintegration\entity\api_key_interface The API key entity
     * @access public
     */
    public function get_api_key($api_key_id)
    {
        return $this->container->get('snailpaste.phpbbexternalintegration.entity')->load($api_key_id);
    }
}
