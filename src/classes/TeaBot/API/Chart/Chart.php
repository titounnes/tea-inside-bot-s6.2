<?php

namespace TeaBot\API\Chart;

use DB;
use PDO;

class Chart
{

	/**
	 * @param float $timeZone
	 * @return array
	 */
	public static function rTimezone(float $timeZone): array
	{
		$r["sec"] = ((int)($timeZone * 3600));
		$r["hz"] = sprintf(
			"%s%02d:%02d",
			$timeZone >= 0 ? "+" : "-",
			abs($r["sec"]),
			abs((($r["sec"] * 3600) % 3600) / 60)
		);
		return $r;
	}

	/**
	 * @param string $startDate
	 * @param string $endDate
	 * @param float  $timeZone
	 */
	public static function messages(string $startDate, string $endDate, float $timeZone = 7)
	{
		$pdo = DB::pdo();
		$tz = self::rTimezone($timeZone);
		$st = $pdo->prepare("
			SELECT 1 as `k`, COUNT(1) as `messages`, DATE(CONVERT_TZ(`tmsg_datetime`, '+00:00','{$tz["hz"]}')) as `date` FROM `groups_messages`
			WHERE `group_id` = -1001162202776 AND
			`tmsg_datetime` >= :start_date AND
			`tmsg_datetime` <= :end_date
			GROUP BY `date`

			UNION 

			SELECT 2 as `k`, COUNT(1) as `messages`, DATE(CONVERT_TZ(`tmsg_datetime`, '+00:00','{$tz["hz"]}')) as `date` FROM `groups_messages`
			WHERE `group_id` = -1001120283944 AND
			`tmsg_datetime` >= :start_date AND
			`tmsg_datetime` <= :end_date
			GROUP BY `date`
		");
		if (strlen($startDate) <= 7) {
			$startDate .= " 00:00:00";
		}
		if (strlen($endDate) <= 7) {
			$endDate .= " 23:59:59";
		}
		$st->execute(
			[
				":start_date" => date("Y-m-d H:i:s", strtotime($startDate) - $tz["sec"]),
				":end_date" => date("Y-m-d H:i:s", strtotime($endDate) - $tz["sec"])
			]
		);
		$res = [
			"labels" => [],
			"datasets" => [
				[
					"label" => "Koding Teh",
					"data" => [],
					"backgroundColor" => "red",
					"borderColor" => "red",
					"borderWidth" => 3,
					"fill" => false
				],
				[
					"label" => "Tea Inside Indonesia",
					"data" => [],
					"backgroundColor" => "green",
					"borderColor" => "green",
					"borderWidth" => 3,
					"fill" => false
				]
			]
		];
		$r = $st->fetchAll(PDO::FETCH_ASSOC);
		foreach ($r as $k => $v) {
			if ($v["k"] == 1) {
				$res["labels"][] = date("d M Y", strtotime($v["date"]));
				$res["datasets"][0]["data"][] = $v["messages"];
			} else {
				$res["datasets"][1]["data"][] = $v["messages"];
			}
		}
		echo json_encode($res);
		DB::close();
	}

	/**
	 * @param string $startDate
	 * @param string $endDate
	 * @param float  $timeZone
	 */
	public static function userStats(string $startDate, string $endDate, float $timeZone = 7)
	{
		$pdo = DB::pdo();
		$tz = self::rTimezone($timeZone);
		$st = $pdo->prepare("
			SELECT * FROM (SELECT
				1 as `k`,
				`a`.`user_id`,
				CONCAT(`b`.`first_name`,
				  CASE WHEN `b`.`last_name` IS NULL THEN ''
				  ELSE CONCAT(' ', `b`.`last_name`) END
				) AS `name`,
				`b`.`username`,
				LOWER(CONCAT(HEX(`c`.`md5_sum`), '_',
				   HEX(`c`.`sha1_sum`), '.', `c`.`extension`)
				) AS `photo`,
				COUNT(1) as `messages`
			FROM `groups_messages` AS `a`
			INNER JOIN `users` AS `b`
			ON `b`.`user_id` = `a`.`user_id`
			LEFT JOIN `files` AS `c`
			ON `c`.`id` = `b`.`photo`
			WHERE `group_id` = -1001162202776
			AND `tmsg_datetime` >= :start_date
			AND `tmsg_datetime` <= :end_date
			GROUP BY `a`.`user_id`
			ORDER BY `messages` DESC, `name` ASC LIMIT 50) x

			UNION

			SELECT * FROM (SELECT
				2 as `k`,
				`a`.`user_id`,
				CONCAT(`b`.`first_name`,
				  CASE WHEN `b`.`last_name` IS NULL THEN ''
				  ELSE CONCAT(' ', `b`.`last_name`) END
				) AS `name`,
				`b`.`username`,
				LOWER(CONCAT(HEX(`c`.`md5_sum`), '_',
				   HEX(`c`.`sha1_sum`), '.', `c`.`extension`)
				) AS `photo`,
				COUNT(1) as `messages`
			FROM `groups_messages` AS `a`
			INNER JOIN `users` AS `b`
			ON `b`.`user_id` = `a`.`user_id`
			LEFT JOIN `files` AS `c`
			ON `c`.`id` = `b`.`photo`
			WHERE `group_id` = -1001120283944
			AND `tmsg_datetime` >= :start_date
			AND `tmsg_datetime` <= :end_date
			GROUP BY `a`.`user_id`
			ORDER BY `messages` DESC, `name` ASC LIMIT 50) y;
		");
		$st->execute(
			[
				":start_date" => date("Y-m-d 00:00:00", strtotime($startDate) - $tz["sec"]),
				":end_date" => date("Y-m-d 23:59:59", strtotime($endDate) - $tz["sec"])
			]
		);
		echo json_encode($st->fetchAll(PDO::FETCH_NUM));
		DB::close();
	}
}
