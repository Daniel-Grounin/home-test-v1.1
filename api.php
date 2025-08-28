<?php

define("a328763fe27bba", "TRUE");

#region start
require_once("config.php");
require_once("../home-test/modules/mysql.php");
header("Content-Type: application/json; charset=utf-8");

$data = $_GET["data"] ?? null;

// Resolve current user: prefer auth cookie, fallback to POST 'username'
function api_current_user(): ?string
{
	$token = $_COOKIE['auth_token'] ?? '';
	if ($token !== '') {
		$rows = mysql_fetch_array(
			'SELECT username FROM users WHERE api_token=? AND api_token_expires_at > NOW() LIMIT 1',
			[$token]
		);
		if (!empty($rows[0]['username'])) return $rows[0]['username'];
		if (!empty($rows[0][0]))         return $rows[0][0]; // if your wrapper returns numeric keys
	}
	// dev fallback (front-end still posts username)
	return $_POST['username'] ?? null;
}

// Parse "N" or "offset,limit" into safe ints
function api_parse_limit(string $raw, int $default = 20): array
{
	$raw = trim($raw);
	if ($raw === '') return [0, $default];
	if (preg_match('~^\s*(\d+)\s*,\s*(\d+)\s*$~', $raw, $m)) {
		return [(int)$m[1], (int)$m[2]];
	}
	$n = (int)$raw;
	return [0, $n > 0 ? $n : $default];
}



$globals["_GET_DATA"] = $data;

#endregion start

switch ($data) {

	case "get_chats":
		#region get_chats
		$username = $_POST["username"] ?? null;

		if (!$username) {
			error_log("ERROR 547389478934729837493287649827634");
			echo json_encode(false);
			die();
		}

		// minimal: sanitize limit
		$limit = (int)($_POST["limit"] ?? 6);
		if ($limit <= 0) $limit = 6;

		// minimal: use MAX(id) and join on m.id (no UNIONs, no other changes)
		$query = "
       SELECT *
		FROM (
		-- A) latest message per contact for this user
		SELECT
			m.contact_id,
			m.msg_type,
			m.msg_body,
			m.msg_datetime,
			COALESCE(c.contact_name, m.contact_id) AS contact_name,
			COALESCE(c.profile_picture_url, './profile_pics/unknown.jpg') AS profile_picture_url
		FROM messages m
		INNER JOIN (
			SELECT contact_id, MAX(row_id) AS latest_row
			FROM messages
			WHERE belongs_to_username = ?
			GROUP BY contact_id
		) latest
			ON latest.contact_id = m.contact_id
		AND latest.latest_row = m.row_id
		LEFT JOIN contacts c
			ON c.belongs_to_username = ?
		AND CAST(c.contact_id AS CHAR) = m.contact_id
		WHERE m.belongs_to_username = ?

		UNION ALL

		-- B) contacts with no messages yet for this user
		SELECT
			c.contact_id,
			NULL AS msg_type,
			NULL AS msg_body,
			NULL AS msg_datetime,
			COALESCE(c.contact_name, c.contact_id) AS contact_name,
			COALESCE(c.profile_picture_url, './profile_pics/unknown.jpg') AS profile_picture_url
		FROM contacts c
		LEFT JOIN messages m
			ON m.belongs_to_username = c.belongs_to_username
		AND m.contact_id = CAST(c.contact_id AS CHAR)
		WHERE c.belongs_to_username = ?
			AND m.row_id IS NULL
		) x
		ORDER BY x.msg_datetime IS NULL, x.msg_datetime DESC, x.contact_name
    ";

		$results = mysql_fetch_array($query, [$username, $username, $username, $username]);
		echo json_encode($results);
		die();

		#endregion get_chats
		break;

	case "get_msgs":
		$username   = api_current_user();
		$contact_id = $_POST["contact_id"] ?? null;
		if (!$username || !$contact_id) {
			echo json_encode([]);
			die();
		}

		$limitRaw = $_POST["limit"] ?? "20";
		[$off, $lim] = api_parse_limit($limitRaw, 20);

		$query = "
			SELECT row_id, is_from_me, msg_type, msg_body, msg_datetime
			FROM messages
			WHERE belongs_to_username = ?
			AND contact_id = ?
			ORDER BY row_id ASC
			LIMIT $off, $lim
		";


		$results = mysql_fetch_array($query, [$username, $contact_id]);
		echo json_encode($results);
		die();


		#endregion get_msgs
		break;

	case "get_new_msgs":
    $username   = api_current_user();
    $contact_id = $_POST["contact_id"] ?? null;
    $last_id    = isset($_POST["last_id"]) ? (int)$_POST["last_id"] : 0;

    if (!$username || !$contact_id || $last_id <= 0) {
        echo json_encode(false);
        die();
    }

    // Use row_id instead of id
    $query = "
        SELECT row_id, is_from_me, msg_type, msg_body, msg_datetime
		FROM messages
		WHERE belongs_to_username = ?
		AND contact_id = ?
		AND row_id > ?
		ORDER BY row_id ASC
    ";

    $results = mysql_fetch_array($query, [$username, $contact_id, $last_id]);
    echo json_encode($results);
    die();


    #endregion get_new_msgs
    break;


	case "get_contact_name_by_contact_id":
		$username   = api_current_user();
		$contact_id = $_POST["contact_id"] ?? null;
		if (!$username || !$contact_id) {
			echo json_encode(false);
			die();
		}

		// Keep shape [[value]] because your JS reads data?.[0]?.[0]
		$query = "SELECT COALESCE(contact_name, ?) AS contact_name
              FROM contacts
              WHERE belongs_to_username = ? AND contact_id = ?
              LIMIT 1";

		$results = mysql_fetch_array($query, [$contact_id, $username, $contact_id]);
		echo json_encode($results);
		die();

		#endregion get_contact_name_by_contact_id
		break;

	case "get_profile_pic_by_contact_id":
		$username   = api_current_user();
		$contact_id = $_POST["contact_id"] ?? null;
		if (!$username || !$contact_id) {
			echo json_encode(false);
			die();
		}

		$query = "SELECT COALESCE(profile_picture_url, './profile_pics/unknown.jpg') AS profile_picture_url
              FROM contacts
              WHERE belongs_to_username = ? AND contact_id = ?
              LIMIT 1";

		$results = mysql_fetch_array($query, [$username, $contact_id]);
		echo json_encode($results);
		die();


		#endregion get_profile_pic_by_contact_id
		break;
	case "send_wa_txt_msg":
		$msg        = $_POST["msg"] ?? null;
		$contact_id = $_POST["contact_id"] ?? null;
		$username   = api_current_user();

		if (!$msg || !$username || !$contact_id) {
			echo json_encode(false);
			die();
		}

		// Insert message for the current user's thread
		$r1 = mysql_insert("messages", [
			"belongs_to_username" => $username,
			"contact_id"          => $contact_id,
			"is_from_me"          => 1,
			"msg_type"            => "text",
			"msg_body"            => $msg,
			// msg_datetime defaults to NOW() in table or let DB handle it
		]);

		// Optional mirror (if you're simulating a two-sided chat inside the same DB)
		// If your contact_id actually stores another user's numeric id, resolve it:
		$peer = null;
		if (ctype_digit((string)$contact_id)) {
			$row = mysql_fetch_array("SELECT username FROM users WHERE id=? LIMIT 1", [(int)$contact_id]);
			$peer = $row[0]['username'] ?? $row[0][0] ?? null;
		}

		$ok = $r1["success"] ?? false;

		if ($peer) {
			$my_id_row = mysql_fetch_array("SELECT id FROM users WHERE username=? LIMIT 1", [$username]);
			$my_contact_id_for_peer = $my_id_row[0]['id'] ?? $my_id_row[0][0] ?? null;

			if ($my_contact_id_for_peer) {
				$r2 = mysql_insert("messages", [
					"belongs_to_username" => $peer,
					"contact_id"          => $my_contact_id_for_peer,
					"is_from_me"          => 0,
					"msg_type"            => "text",
					"msg_body"            => $msg,
				]);
				$ok = $ok && ($r2["success"] ?? false);
			}
		}

		echo json_encode($ok ? true : false);
		die();


		#endregion send_wa_txt_msg
		break;
		
		
	case "send_wa_img_msg":
    $username   = api_current_user();
    $contact_id = $_POST["contact_id"] ?? null;
    $file       = $_FILES["file"]["tmp_name"] ?? null;

    if (!$username || !$contact_id || !$file) {
        echo json_encode(["error" => "Missing data"]);
        die();
    }

    try {
        $cloudinary = $GLOBALS['cloudinary'];
        $uploadResult = $cloudinary->uploadApi()->upload($file, [
            "folder" => "chat_uploads"
        ]);
        $imageUrl = $uploadResult["secure_url"];

        // Insert into messages
        $r1 = mysql_insert("messages", [
            "belongs_to_username" => $username,
            "contact_id"          => $contact_id,
            "is_from_me"          => 1,
            "msg_type"            => "image",
            "msg_body"            => $imageUrl,
        ]);

        echo json_encode(["success" => true, "url" => $imageUrl]);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
    die();
    break;

}

include_all_plugins("api.php");
die();