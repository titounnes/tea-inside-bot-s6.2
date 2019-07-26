<?php

namespace TeaBot\Loggers;

use DB;
use PDO;
use TeaBot\LoggerFoundation;
use TeaBot\Contracts\LoggerInterface;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot\Loggers
 * @version 6.2.0
 */
final class PrivateLogger extends LoggerFoundation implements LoggerInterface
{
	/**
	 * @return void
	 */
	public function run(): void
	{
		$this->saveUserInfo();
	}

	/**
	 * @return void
	 */
	public function saveUserInfo(): void
	{
		$createHistory = false;
		$st = $this->pdo->prepare("SELECT `username`, `first_name`, `last_name`, `photo` FROM `users` WHERE `user_id` = :user_id LIMIT 1;");
		$st->execute([":user_id" => $this->data["user_id"]]);
		$data = [
			":user_id" => $this->data["user_id"],
			":username" => $this->data["username"],
			":first_name" => $this->data["first_name"],
			":last_name" => $this->data["last_name"],
			":photo" => null,
			":created_at" => date("Y-m-d H:i:s")
		];

		if ($r = $st->fetch(PDO::FETCH_ASSOC)) {
			$this->pdo->prepare("UPDATE `users` SET `private_msg_count` = `private_msg_count` + 1 WHERE `user_id` = :user_id LIMIT 1;")->execute([":user_id" => $data[":user_id"]]);
			if (
				($this->data["username"] !== $r["username"]) ||
				($this->data["first_name"] !== $r["first_name"]) ||
				($this->data["last_name"] !== $r["last_name"])
			) {
				$createHistory = true;
			}
		} else {
			$data[":is_bot"] = ($this->data["is_bot"] ? '1' : '0');
			$data[":group_msg_count"] = 0;
			$this->pdo->prepare("INSERT INTO `users` (`user_id`, `username`, `first_name`, `last_name`, `photo`, `is_bot`, `group_msg_count`, `private_msg_count`, `created_at`, `updated_at`) VALUES (:user_id, :username, :first_name, :last_name, :photo, :is_bot, :group_msg_count, 1, :created_at, NULL);")->execute($data);
			unset($data[":is_bot"], $data[":group_msg_count"]);
			$data[":user_id"] = $this->pdo->lastInsertId();
			$createHistory = true;
		}

		if ($createHistory) {
			$this->pdo->prepare("INSERT INTO `users_history` (`user_id`, `username`, `first_name`, `last_name`, `photo`, `created_at`) VALUES (:user_id, :username, :first_name, :last_name, :photo, :created_at);")->execute($data);
		}
	}

	/**
	 * @return void
	 */
	public function logText(): void
	{
		
	}

	/**
	 * @return void
	 */
	public function logPhoto(): void
	{
		
	}
}