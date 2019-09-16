<?php

namespace TeaBot\API;

use DB;
use PDO;
use Exception;

class GetGroupMessages
{
	/**
	 * @var \PDO
	 */
	private $pdo;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->pdo = DB::pdo();
	}

	/**
	 * @return void
	 */
	public function dispatch(): void
	{
		header("Content-Type: application/json");

		if (isset($_GET["limit"]) && is_numeric($_GET["limit"]) && ($_GET["limit"] >= 0)) {
			$limit = (int)$_GET["limit"];
		} else {
			$limit = 30;
		}

		if (isset($_GET["offset"]) && is_numeric($_GET["offset"]) && ($_GET["offset"] >= 0)) {
			$offset = (int)$_GET["offset"];
		} else {
			$offset = 0;
		}

		if (isset($_GET["order_by"]) && is_string($_GET["order_by"])) {
			$orderBy = strtolower($_GET["order_by"]);
		} else {
			$orderBy = "id";
		}

		if (isset($_GET["order_type"]) && is_string($_GET["order_type"])) {
			$orderType = strtolower($_GET["order_type"]);
		} else {
			$orderType = "asc";
		}

		$allowedField = ["id", "group_id", "user_id", "tmsg_id", "reply_to_tmsg_id", "msg_type", "text", "text_entities", "file", "is_edited", "tmsg_datetime", "created_at"];

		if (!in_array($orderBy, $allowedField)) {
			throw new Exception("Invalid field {$orderBy}");
			return;
		}

		if (($orderType !== "asc") && ($orderType !== "desc")) {
			throw new Exception("Invalid order type {$orderType}");
			return;
		}

		print "{\"success\":true,\"param\":{\"limit\":{$limit},\"offset\":{$offset}},\"data\":[";

		$st = $this->pdo->prepare("SELECT * FROM `groups_messages` ORDER BY {$orderBy} {$orderType} LIMIT {$limit} OFFSET {$offset};");
		$st->execute();

		$i = 0;
		while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
			print ($i ? "," : "").json_encode($r, JSON_UNESCAPED_SLASHES);
			flush();
			$i++;
		}

		print "]}";
	}
}
