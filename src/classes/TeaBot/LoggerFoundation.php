<?php

namespace TeaBot;

use DB;
use PDO;
use Error;
use Exception;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
 * @license MIT
 * @package \TeaBot
 * @version 6.2.0
 */
abstract class LoggerFoundation
{
    /**
     * @var \TeaBot\Data
     */
    protected $data;

    /**
     * @var \PDO
     */

    /**
     * @param \TeaBot\Data &$data
     *
     * Constructor.
     */
    public function __construct(Data &$data)
    {
        $this->data = &$data;
        $this->pdo = DB::pdo();
    }

    /**
     * @return void
     */
    abstract public function run(): void;

    /**
     * @param string $type
     * @param string $hash
     * @return void
     */
    public static function flock(string $type, string $hash): void
    {
        is_dir("/tmp/telegram_lock") or mkdir("/tmp/telegram_lock");
        is_dir("/tmp/telegram_lock/{$type}") or mkdir("/tmp/telegram_lock/{$type}");
        file_put_contents("/tmp/telegram_lock/{$type}/{$hash}", time());
    }

    /**
     * @param string $type
     * @param string $hash
     * @return void
     */
    public static function funlock(string $type, string $hash): void
    {
        @unlink("/tmp/telegram_lock/{$type}/{$hash}");
    }

    /**
     * @param string $type
     * @param string $hash
     * @return bool
     */
    public static function f_is_locked(string $type, string $hash): bool
    {
        return file_exists("/tmp/telegram_lock/{$type}/{$hash}");
    }

    /**
     * @param string $telegramFileId
     * @param bool   $increaseHitCounter
     * @return ?int
     */
    public static function fileResolve(?string $telegramFileId, bool $increaseHitCounter = false): ?int
    {
        if (is_null($telegramFileId)) {
            return null;
        }

        $pdo = DB::pdo();
        $st = $pdo->prepare("SELECT `id` FROM `files` WHERE `telegram_file_id` = :telegram_file_id LIMIT 1;");
        $st->execute([":telegram_file_id" => $telegramFileId]);
        if ($r = $st->fetch(PDO::FETCH_NUM)) {
            if ($increaseHitCounter) {
                $fileId = $ret = (int)$r[0];
                goto increase_hit_counter;
            }
            return (int)$r[0];
        }

        $o = json_decode(Exe::getFile(["file_id" => $telegramFileId])["out"], true);
        if (isset($o["result"]["file_path"])) {

            $o = $o["result"];

            // Create required directories.
            is_dir(STORAGE_PATH) or mkdir(STORAGE_PATH);
            is_dir(STORAGE_PATH."/telegram") or mkdir(STORAGE_PATH."/telegram");
            is_dir(STORAGE_PATH."/telegram/files") or mkdir(STORAGE_PATH."/telegram/files");
            is_dir("/tmp/telegram_download") or mkdir("/tmp/telegram_download");

            // // Create .gitignore at storage path.
            // file_exists(STORAGE_PATH."/telegram/.gitignore") or
            // file_get_contents(STORAGE_PATH."/telegram/.gitignore", "*\n!.gitignore\n");

            // Get file extension.
            $ext = explode(".", $o["file_path"]);
            if (count($ext) > 1) {
                $ext = strtolower(end($ext));
            } else {
                $ext = null;
            }

            // Prepare temporary file handler.
            $tmpFile = "/tmp/telegram_download/".time()."_".sha1($telegramFileId)."_".rand(100000, 999999).
                (isset($ext) ? ".".$ext : "");
            $handle = fopen($tmpFile, "wb+");
            $bufferSize = 4096;
            $writtenBytes = 0;

            // Download the file.
            $ch = curl_init("https://api.telegram.org/file/bot".BOT_TOKEN."/".$o["file_path"]);
            curl_setopt_array($ch,
                [
                    CURLOPT_VERBOSE => 0,
                    CURLOPT_RETURNTRANSFER => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_WRITEFUNCTION => function ($ch, $str) use (&$handle, &$writtenBytes, $bufferSize) {
                        $bytes = fwrite($handle, $str);
                        $writtenBytes += $bytes;
                        if ($writtenBytes >= $bufferSize) {
                            fflush($handle);
                        }
                        return $bytes;
                    }
                ]
            );
            curl_exec($ch);
            curl_close($ch);
            fclose($handle);

            // Calculate file checksum.
            $sha1_hash = sha1_file($tmpFile, true);
            $md5_hash = md5_file($tmpFile, true);

            // Check whether there is the same file in storage by matching its absolute hash.
            $st = $pdo->prepare("SELECT `id` FROM `files` WHERE `md5_sum` = :md5_sum AND `sha1_sum` = :sha1_sum LIMIT 1;");
            $st->execute(
                [
                    ":md5_sum" => $md5_hash,
                    ":sha1_sum" => $sha1_hash,
                ]
            );
            if ($r = $st->fetch(PDO::FETCH_NUM)) {
                // Increase hit counter.
                $pdo->prepare("UPDATE `files` SET `telegram_file_id` = :telegram_file_id, `hit_count` = `hit_count` + 1, `updated_at` = :updated_at WHERE `id` = :id LIMIT 1;")->execute(
                        [
                            ":telegram_file_id" => $telegramFileId,
                            ":id" => $r[0],
                            ":updated_at" => date("Y-m-d H:i:s")
                        ]
                    );

                // Delete temporary file.
                unlink($tmpFile);

                if ($increaseHitCounter) {
                    $fileId = $ret = (int)$r[0];
                    goto increase_hit_counter;
                }

                return (int)$r[0];
            }

            // Prepare target filename.
            $targetFile = STORAGE_PATH."/telegram/files/".
                bin2hex($md5_hash)."_".bin2hex($sha1_hash).(isset($ext) ? ".".$ext : "");

            // Move downloaded file to storage directory.
            rename($tmpFile, $targetFile);

            // Insert metadata to database.
            $pdo->prepare("INSERT INTO `files` (`telegram_file_id`, `md5_sum`, `sha1_sum`, `file_type`, `extension`, `size`, `hit_count`, `created_at`) VALUES (:telegram_file_id, :md5_sum, :sha1_sum, :file_type, :extension, :size, 1, :created_at);")
                ->execute(
                    [
                        ":telegram_file_id" => $telegramFileId,
                        ":md5_sum" => $md5_hash,
                        ":sha1_sum" => $sha1_hash,
                        ":file_type" => "photo",
                        ":extension" => $ext,
                        ":size" => filesize($targetFile),
                        ":created_at" => date("Y-m-d H:i:s")
                    ]
                );
            return $pdo->lastInsertId();
        }

        // Couldn't get the file_path (Error from Telegram API)
        return null;

        increase_hit_counter:
        $pdo->prepare("UPDATE `files` SET `hit_count` = `hit_count` + 1, `updated_at` = :updated_at WHERE `id` = :id LIMIT 1;")->execute(
                    [
                        ":id" => $fileId,
                        ":updated_at" => date("Y-m-d H:i:s")
                    ]
                );
        return $ret;
    }

    /**
     * @param string $userId
     * @return ?int
     */
    public static function getLatestUserPhoto(string $userId): ?int
    {
        $o = Exe::getUserProfilePhotos(
            [
                "user_id" => $userId,
                "offset" => 0,
                "limit" => 1
            ]
        );
        $json = json_decode($o["out"], true);
        if (isset($json["result"]["photos"][0])) {
            $c = count($json["result"]["photos"][0]);
            if ($c) {
                $p = $json["result"]["photos"][0][$c - 1];
                if (isset($p["file_id"])) {
                    return self::fileResolve($p["file_id"]);
                }
            }
        }
        return null;
    }

    /**
     * @param mixed $parData Must be accessible as array.
     * @param int    $logType
     * @return void
     *
     *
     * $logType description
     * 0 = No message log.
     * 1 = Group log.
     * 2 = Private log.
     */
    public function userLogger($parData, $logType = 0): void
    {
        $hash = sha1($parData["user_id"]);
        $t = 0;
        while (self::f_is_locked("user", $hash)) {
            if ($t >= 30) {
                self::funlock("user", $hash);
                break;
            }
            sleep(1);
            $t++;
        }

        self::flock("user", $hash);

        $e = null;
        try {
            self::unsafeUserLogger($parData, $logType);    
        } catch (Exception $e) {
        } catch (Error $e) {
        }

        self::funlock("user", $hash);
        if ($e) throw $e;
    }

    /**
     * @param mixed $parData Must be accessible as array.
     * @param int    $logType
     * @return void
     */
    private static function unsafeUserLogger($parData, $logType = 0): void
    {
        $pdo = DB::pdo();
        $createUserHistory = false;
        $data = [
            ":user_id" => $parData["user_id"],
            ":username" => $parData["username"],
            ":first_name" => $parData["first_name"],
            ":last_name" => $parData["last_name"],
            ":photo" => null,
            ":created_at" => date("Y-m-d H:i:s")
        ];

        /**
         * Retrieve user data from database.
         */
        $st = $pdo->prepare("SELECT `id`, `username`, `first_name`, `last_name`, `photo`, `is_bot`, `group_msg_count`, `private_msg_count` FROM `users` WHERE `user_id` = :user_id LIMIT 1;");
        $st->execute([":user_id" => $parData["user_id"]]);

        if ($r = $st->fetch(PDO::FETCH_ASSOC)) {

            /**
             * User has been recorded in database.
             */
            $exeData = [":id" => (int)$r["id"]];

            $noMsgLog = false;
            if ($logType == 1) {
                $cc = $r["group_msg_count"] = ((int)$r["group_msg_count"] + 1);
                $exeData[":group_msg_count"] = $r["group_msg_count"];
                $additionalQuery = ", `group_msg_count` = :group_msg_count";
            } else if ($logType == 2) {
                $cc = $r["private_msg_count"] = ((int)$r["private_msg_count"] + 1);
                $exeData[":private_msg_count"] = $r["private_msg_count"];
                $additionalQuery = ", `private_msg_count` = :private_msg_count";
            } else {
                $noMsgLog = true;
                $additionalQuery = "";
            }

            $photoChange = $gotAdditonalPhoto = false;
            if ((!$noMsgLog) && (($cc % 5) == 0)) {
                $gotAdditonalPhoto = true;
                $exeData[":photo"] = self::getLatestUserPhoto($parData["user_id"]);
                $additionalQuery .= ", `photo` = :photo";
                if ($exeData[":photo"] != $r["photo"]) {
                    $photoChange = true;
                }
            }

            // Check whether there is a change on user info.
            if ($photoChange ||
                ($parData["username"] !== $r["username"]) ||
                ($parData["first_name"] !== $r["first_name"]) ||
                ($parData["last_name"] !== $r["last_name"])) {

                if (!$gotAdditonalPhoto) {
                    $exeData[":photo"] = self::getLatestUserPhoto($parData["user_id"]);
                    $additionalQuery .= ", `photo` = :photo";
                }

                $data[":photo"] = $exeData[":photo"];

                // Create user history if there is a change on user info.
                $createUserHistory = true;

                $exeData[":username"] = $parData["username"];
                $exeData[":first_name"] = $parData["first_name"];
                $exeData[":last_name"] = $parData["last_name"];

                $pdo->prepare("UPDATE `users` SET `username` = :username, `first_name` = :first_name, `last_name` = :last_name {$additionalQuery} WHERE `id` = :id LIMIT 1;")
                ->execute($exeData);

            } else {
                if (!$noMsgLog) {
                    $additionalQuery[0] = " ";
                    $pdo->prepare("UPDATE `users` SET {$additionalQuery} WHERE `id` = :id LIMIT 1;")->execute($exeData);
                }
            }

        } else {

            /**
             * User has not been stored in database.
             */
            $data[":is_bot"] = ($parData["is_bot"] ? '1' : '0');
            $data[":photo"] = self::getLatestUserPhoto($parData["user_id"]);

            if ($logType == 1) {
                $u = 1;
                $v = 0;
            } else if ($logType == 2) {
                $u = 0;
                $v = 1;
            } else {
                $u = $v = 0;
            }

            $pdo->prepare("INSERT INTO `users` (`user_id`, `username`, `first_name`, `last_name`, `photo`, `is_bot`, `group_msg_count`, `private_msg_count`, `created_at`) VALUES (:user_id, :username, :first_name, :last_name, :photo, :is_bot, {$u}, {$v}, :created_at);")->execute($data);
            unset($data[":is_bot"]);
            $createUserHistory = true;
        }

        if ($createUserHistory) {
            $pdo->prepare("INSERT INTO `users_history` (`user_id`, `username`, `first_name`, `last_name`, `photo`, `created_at`) VALUES (:user_id, :username, :first_name, :last_name, :photo, :created_at);")->execute($data);
        }
    }
}
