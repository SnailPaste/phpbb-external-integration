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
 * Entity for an API key
 */
class api_key implements api_key_interface
{
    /**
     * Data for this entity
     *
     * @var array
     *	api_key_id
     *	api_key_name
     *	api_key_value
     *	api_key_allowed_ips
     *	api_key_perm_register
     *	api_key_perm_login
     *	api_key_perm_manage
     * @access protected
     */
    protected $data;

    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\event\dispatcher_interface */
    protected $dispatcher;

    /** @var \phpbb\textformatter\s9e\utils */
    protected $text_formatter_utils;

    /**
     * The database table the API key data is stored in
     *
     * @var string
     */
    protected $api_keys_table;

    /**
     * Constructor
     *
     * @param \phpbb\db\driver\driver_interface   $db                    Database object
     * @param \phpbb\config\config                $config                Config object
     * @param \phpbb\event\dispatcher_interface   $phpbb_dispatcher      Event dispatcher
     * @param string                              $api_keys_table        Name of the table used to store API key data
     * @param \phpbb\textformatter\s9e\utils      $text_formatter_utils  Text manipulation utilities
     * @access public
     */
    public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\config\config $config, \phpbb\event\dispatcher_interface $phpbb_dispatcher, $api_keys_table, \phpbb\textformatter\s9e\utils $text_formatter_utils)
    {
        $this->db = $db;
        $this->config = $config;
        $this->dispatcher = $phpbb_dispatcher;
        $this->api_keys_table = $api_keys_table;
        $this->text_formatter_utils = $text_formatter_utils;
    }

    /**
     * Load the data from the database for an API key
     *
     * @param int $api_key_id API key identifier
     * @param string $api_key_value API key value
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\out_of_bounds
     */
    public function load($api_key_id = 0, $api_key_value = '')
    {
        // Load by id if provided, otherwise default to load by API key
        $sql_where = ($api_key_id <> 0) ? 'api_key_id = ' . (int) $api_key_id : "api_key_value = '" . $this->db->sql_escape($api_key_value) . "'";

        // Get API key from the database
        $sql = 'SELECT api_key_id, api_key_name, api_key_value, api_key_allowed_ips, api_key_perm_register, api_key_perm_login, api_key_perm_manage
			FROM ' . $this->api_keys_table . '
			WHERE ' . $sql_where;
        $result = $this->db->sql_query($sql);
        $this->data = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if ($this->data === false)
        {
            // The APIU key does not exist
            throw new \snailpaste\phpbbexternalintegration\exception\out_of_bounds('api_key_id');
        }

        return $this;
    }

    /**
     * Import data for an API key
     *
     * Used when the data is already loaded externally.
     * Any existing data on this API key is over-written.
     * All data is validated and an exception is thrown if any data is invalid.
     *
     * @param array $data Data array, typically from the database
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\base
     */
    public function import($data)
    {
        // Clear out any saved data
        $this->data = array();

        // All of our fields
        $fields = array(
            // column						=> data type (see settype())
            'api_key_id'					=> 'integer',
            'api_key_name'					=> 'set_name', // call set_order()
            'api_key_value'					=> 'set_key', // call set_title()
            'api_key_allowed_ips'			=> 'set_allowed_ips', // call set_description()
            'api_key_perm_register'			=> 'set_perm_register',
            'api_key_perm_login'			=> 'set_perm_login',
            'api_key_perm_manage'			=> 'set_perm_manage',
        );

        // Go through the basic fields and set them to our data array
        foreach ($fields as $field => $type)
        {
            // If the data wasn't sent to us, throw an exception
            if (!isset($data[$field]))
            {
                throw new \snailpaste\phpbbexternalintegration\exception\invalid_argument(array($field, 'FIELD_MISSING'));
            }

            // If the type is a method on this class, call it
            if (method_exists($this, $type))
            {
                $this->$type($data[$field]);
            }
            else
            {
                // settype passes values by reference
                $value = $data[$field];

                // We're using settype to enforce data types
                settype($value, $type);

                $this->data[$field] = $value;
            }
        }

        // Some fields must be unsigned (>= 0)
        $validate_unsigned = array(
            'api_key_id',
        );

        foreach ($validate_unsigned as $field)
        {
            // If the data is less than 0, it's not unsigned and we'll throw an exception
            if ($this->data[$field] < 0)
            {
                throw new \snailpaste\phpbbexternalintegration\exception\out_of_bounds($field);
            }
        }

        return $this;
    }

    /**
     * Insert the API key data for the first time
     *
     * Will throw an exception if the API key was already inserted (call save() instead)
     *
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\out_of_bounds
     */
    public function insert()
    {
        if (!empty($this->data['api_key_id']))
        {
            // The API key already exists
            throw new \snailpaste\phpbbexternalintegration\exception\out_of_bounds('api_key_id');
        }

        // Insert the API key data to the database
        $sql = 'INSERT INTO ' . $this->api_keys_table . ' ' . $this->db->sql_build_array('INSERT', $this->data);
        $this->db->sql_query($sql);

        // Set the api_key_id using the id created by the SQL insert
        $this->data['api_key_id'] = (int) $this->db->sql_nextid();

        return $this;
    }

    /**
     * Save the current settings to the database
     *
     * This must be called before closing or any changes will not be saved!
     * If adding an API key (saving for the first time), you must call insert() or an exeception will be thrown
     *
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\out_of_bounds
     */
    public function save()
    {
        if (empty($this->data['api_key_id']))
        {
            // The API key does not exist
            throw new \snailpaste\phpbbexternalintegration\exception\out_of_bounds('api_key_id');
        }

        // Copy the data array, filtering out the api_key_id identifier
        // so we do not attempt to update the row's identity column.
        $sql_array = array_diff_key($this->data, array('api_key_id' => null));

        // Update the API key data in the database
        $sql = 'UPDATE ' . $this->api_keys_table . '
			SET ' . $this->db->sql_build_array('UPDATE', $sql_array) . '
			WHERE api_keys_id = ' . (int) $this->get_id();
        $this->db->sql_query($sql);

        return $this;
    }

    /**
     * Get id
     *
     * @return int API key identifier
     * @access public
     */
    public function get_id()
    {
        return isset($this->data['api_key_id']) ? (int) $this->data['api_key_id'] : 0;
    }

    /**
     * Get name
     *
     * @return string Name
     * @access public
     */
    public function get_name()
    {
        return isset($this->data['api_key_name']) ? (string) $this->data['api_key_name'] : '';
    }

    /**
     * Set name
     *
     * @param string $name
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\unexpected_value
     */
    public function set_name($name)
    {
        // Enforce a string
        $name = (string) $name;

        // Title is a required field
        if ($name === '')
        {
            throw new \snailpaste\phpbbexternalintegration\exception\unexpected_value(array('name', 'FIELD_MISSING'));
        }

        // We limit the title length to 200 characters
        if (truncate_string($name, 200) !== $name)
        {
            throw new \snailpaste\phpbbexternalintegration\exception\unexpected_value(array('title', 'TOO_LONG'));
        }

        // Set the key name on our data array
        $this->data['api_key_name'] = $name;

        return $this;
    }

    /**
     * Get API key
     *
     * @return string API Key
     * @access public
     */
    public function get_key()
    {
        return isset($this->data['api_key_value']) ? (string) $this->data['api_key_value'] : '';
    }

    /**
     * Set API key
     *
     * @param string $key
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\unexpected_value
     */
    public function set_key($key)
    {
        // Enforce a string
        $key = (string) $key;

        // Title is a required field
        if ($key === '')
        {
            throw new \snailpaste\phpbbexternalintegration\exception\unexpected_value(array('key', 'FIELD_MISSING'));
        }

        // We limit the title length to 250 characters
        if (truncate_string($key, 250) !== $key)
        {
            throw new \snailpaste\phpbbexternalintegration\exception\unexpected_value(array('key', 'TOO_LONG'));
        }

        // Set the key value on our data array
        $this->data['api_key_value'] = $key;

        return $this;
    }

    /**
     * Get the list of allowed IP addresses (with CIDR support) that are allowed to use this API key
     *
     * @return string Allowed IPs
     * @access public
     */
    public function get_allowed_ips()
    {
        return isset($this->data['api_key_allowed_ips']) ? (string) $this->data['api_key_allowed_ips'] : '';
    }

    /**
     * Set the list of allowed IP addresses (with CIDR support) that are allowed to use this API key
     *
     * @param string $allowed_ips
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\unexpected_value
     */
    public function set_allowed_ips($allowed_ips)
    {
        // Enforce a string
        $allowed_ips = (string) $allowed_ips;

        // Title is a required field
        if ($allowed_ips === '')
        {
            throw new \snailpaste\phpbbexternalintegration\exception\unexpected_value(array('allowed_ips', 'FIELD_MISSING'));
        }

        // We limit the title length to 16384 characters
        if (truncate_string($allowed_ips, 16384) !== $allowed_ips)
        {
            throw new \snailpaste\phpbbexternalintegration\exception\unexpected_value(array('key', 'TOO_LONG'));
        }

        // Set the allowed IPs on our data array
        $this->data['api_key_allowed_ips'] = $allowed_ips;

        return $this;
    }

    /**
     * Check if the account registration permission is granted
     *
     * @return bool allow html
     * @access public
     */
    public function register_allowed()
    {
        return isset($this->data['api_key_perm_register']) ? (bool) $this->data['api_key_perm_register'] : false;
    }

    /**
     * Enable or disable the account registration permission
     *
     * @param bool $allow_register
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\unexpected_value
     */
    public function set_perm_register($allow_register)
    {
        // Enforce a bool
        $allow_register = (bool) $allow_register;

        // Set the permission for registration on our data array
        $this->data['api_key_perm_register'] = $allow_register;

        return $this;
    }

    /**
     * Check if the login permission is granted
     *
     * @return bool allow html
     * @access public
     */
    public function login_allowed()
    {
        return isset($this->data['api_key_perm_login']) ? (bool) $this->data['api_key_perm_login'] : false;
    }

    /**
     * Enable or disable the login permission
     *
     * @param bool $allow_login
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\unexpected_value
     */
    public function set_perm_login($allow_login)
    {
        // Enforce a bool
        $allow_login = (bool) $allow_login;

        // Set the permission for login on our data array
        $this->data['api_key_perm_login'] = $allow_login;

        return $this;
    }

    /**
     * Check if the account management permission is granted
     *
     * @return bool allow html
     * @access public
     */
    public function manage_allowed()
    {
        return isset($this->data['api_key_perm_manage']) ? (bool) $this->data['api_key_perm_manage'] : false;
    }

    /**
     * Enable or disable the account management permission
     *
     * @param bool $allow_manage
     * @return api_key_interface $this object for chaining calls; load()->set()->save()
     * @access public
     * @throws \snailpaste\phpbbexternalintegration\exception\unexpected_value
     */
    public function set_perm_manage($allow_manage)
    {
        // Enforce a bool
        $allow_manage = (bool) $allow_manage;

        // Set the permission for account management on our data array
        $this->data['api_key_perm_manage'] = $allow_manage;

        return $this;
    }
}