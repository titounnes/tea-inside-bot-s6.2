<?php

namespace TeaBot;

use ArrayAccess;
use TeaBot\Lang;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot
 * @version 6.2.0
 */
final class Data implements ArrayAccess
{
	/**
	 * @var array
	 */
	public $in;

	/**
	 * @var array
	 */
	public $container;

	/**
	 * @param array $data
	 *
	 * Constructor.
	 */
	public function __construct(array $data)
	{
		$this->in = $data;
		$this->container["in"] = &$this->in;
		if (isset($this->in["message"]["text"])) {

			if (isset($this->in["message"]["from"]["username"])) {
				$this->container["username"] = &$this->in["message"]["from"]["username"];
			} else {
				$this->container["username"] = null;
			}

			if (isset($this->in["message"]["from"]["language_code"])) {
				$this->container["lang"] = &$this->in["message"]["from"]["language_code"];
				Lang::init($this->container["lang"]);
			} else {
				$this->container["lang"] = null;
				Lang::init("en");
			}

			$this->container["update_id"]	= &$this->in["update_id"];
			$this->container["text"]		= &$this->in["message"]["text"];
			$this->container["msg_id"]		= &$this->in["message"]["message_id"];
			$this->container["chat_id"]		= &$this->in["message"]["chat"]["id"];
			$this->container["chat_title"]	= &$this->in["message"]["chat"]["title"];
			$this->container["user_id"]		= &$this->in["message"]["from"]["id"];
			$this->container["is_bot"]		= &$this->in["message"]["from"]["is_bot"];
			$this->container["first_name"]	= &$this->in["message"]["from"]["first_name"];
			$this->container["date"]		= &$this->in["message"]["date"];
			$this->container["msg_type"]	= "text";

			if (isset($this->in["message"]["from"]["last_name"])) {
				$this->container["last_name"] = &$this->in["message"]["from"]["last_name"];
			} else {
				$this->container["last_name"] = null;
			}

			if (isset($this->in["message"]["entities"])) {
				$this->container["entities"] = &$this->in["message"]["entities"];
			} else {
				$this->container["entities"] = null;
			}

			if ($this->in["message"]["chat"]["type"] === "private") {
				$this->container["chat_type"] = "private";
			} else {
				$this->container["chat_type"] = "group";
			}
		}
	}

	/**
	 * @param mixed $key
	 * @return &mixed
	 */
	public function &offsetGet($key)
	{
		if (!array_key_exists($key, $this->container)) {
			$this->container[$key] = null;
		}
		return $this->container[$key];
	}

	/**
	 * @param mixed $key
	 * @param mixed &$data
	 * @return void
	 */
	public function offsetSet($key, $data)
	{
		$this->container[$key] = $data;
	}

	/**
	 * @param mixed $key
	 * @return bool
	 */
	public function offsetExists($key): bool
	{
		return isset($this->container[$key]);
	}

	/**
	 * @param mixed $key
	 * @return void
	 */
	public function offsetUnset($key)
	{
		unset($this->container[$key]);
	}
}
