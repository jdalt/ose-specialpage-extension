<?php 
/**
 * class.TrueFansDb.php -- a wrapper for True Fans database
 * Copyright 2012 by Jacob Dalton	
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @file class.TrueFansDb.php
 * @author Jacob Dalton <jacobrdalton@gmail.com>
 * @ingroup Extensions
*/

// Constants used for field and tables names, defined here so that changes to schema
// need only be made once. Note that this must be kept in sync with changes to
// sql files in patches directory to reflect current schema.
global $wgDBprefix;
define('TF_TABLE', $wgDBprefix.'true_fans');
define('TF_ID', 'tf_id');
define('TF_FOREIGN_ID', 'tf_foreign_id');
define('TF_NAME', 'tf_name');
define('TF_EMAIL', 'tf_email');
define('TF_VIDEO_ID', 'tf_video_id');
define('TF_VIDEO_MESSAGE', 'tf_video_message');
define('TF_EMAIL_INVITE_LIST', 'tf_email_invite_list'); 

class TrueFansDb
{
	// TODO: Is bconnected useful? 
	protected $bconnected; // Currently used to control error output of destructor which can't close a connection for a null object
	private $pdo;
	
	/**
	* Creates PDO object according to functions in connect script
	*/
	function __construct()
	{
		global $wgDBtype, $wgDBname, $wgDBserver, $wgDBuser, $wgDBpassword;

		$dsn = $wgDBtype . ':dbname='.$wgDBname.';host='.$wgDBserver;
		try {
			$this->pdo = new PDO($dsn, $wgDBuser, $wgDBpassword);
			$this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->bconnected = true;
		} catch (PDOException $e) {
			$this->log('Connection failed: ' . $e->getMessage());
			$this->bconnected = false;
		}
	}

	/**
	* Destroys PDO object and sets bconnect to false
	*/
	function __destruct()
	{
		if($this->bconnected) {
			// clean up
			$this->pdo = null; // close the connection
			
		} else {
			$this->log('Abnormal Termination. No DB to close.');
		}
	}

	// *** Modification Functions ***
	
	/**
	* Adds a new user and fills TF_NAME, TF_EMAIL, and TF_VIDEO_ID fields
	* @param String $name
	* @param String $email 
	* @param String $video_id
	* @param Boolean $extractUrl Optional parameter to extract url. 
	* Defaults to true. When set to false, TF_VIDEO_ID inserted w/o checks.
	* @return True|NULL On success returns true. On failure returns NULL.
	*/
	public function addUser($foreign_id, $name, $email, $video_id, $extractUrl=true)
	{
		$name = htmlspecialchars($name, ENT_QUOTES);

		$insert_id = $video_id;
		if($extractUrl) {
			$insert_id = $this->extractVideoId($video_id);
			if(!$insert_id) {
				return NULL;
			}
		}

		$stmt = $this->pdo->prepare('INSERT INTO '.TF_TABLE.'('.TF_FOREIGN_ID.','.TF_NAME.','.TF_EMAIL.','.TF_VIDEO_ID.') VALUES(:foreign_id, :name, :email, :video_id)');
		try {
			if($stmt->execute(array(':foreign_id' => $foreign_id, ':name' => $name, ':email' => $email, ':video_id' => $insert_id))) {
				// success
				return true;
			}
		} catch (PDOException $e) {
			$this->log('Unable to insert user: ' .$e->getMessage());
		}
		return NULL;
	}
	
	/**
	* Updates a row of true_ran table with TF_NAME
	* @param Integer $id The row to update.
	* @param String $name The message to add.
	* @return Boolean Returns true on success and false on failure.
	* No rows being updated counts as true TODO: think about this...in the sense of updating you should be able to update to same value...check on your own if it's the same why not...however updating a non profile...check on your own if it exists...
	*/
	public function updateName($id, $name)
	{
		$name = htmlspecialchars($name, ENT_QUOTES);

		$stmt = $this->pdo->prepare('UPDATE '.TF_TABLE.' SET '.TF_NAME.'=:name WHERE '.TF_ID.'=:id');
		try {
			if($stmt->execute(array(':name' => $name, ':id' => $id))) {
				// success
				if($stmt->rowCount() == 0) {
					$this->log('No rows were updated via change to message or email_invite_list.');
					// considered making this return false; but I think it would be better to check on your own if updating same data or updating a non-existant profile
				}
				return true;
			}
		} catch (PDOException $e) {
			$this->log('Unable to add message and '.TF_EMAIL.' list invitation for '.$id.': ' .$e->getMessage());
		}
		return false; 
	}

	/**
	* Updates a TF_VIDEO_ID
	* @param String $id 
	* @param String $video_id
	* @param Boolean $extractUrl Optional parameter to extract url. 
	* Defaults to true. When set to false, TF_VIDEO_ID inserted w/o checks.
	* @return Boolean Returns true on success and false on failure.
	* No rows being updated counts as a failure. This requires a client
	* of this library to test that the new data will different than existing
	* data if they want to understand a 0 row return.
	*/
	public function updateVideoId($id, $video_id, $extractUrl=true)
	{
		$update_id = $video_id;
		if($extractUrl) {
			$update_id = $this->extractVideoId($video_id);
			if(!$update_id) {
				$this->log('Unable to update extract video '.TF_ID.'.'); 
				return false;
			}
		} 
	
		$stmt = $this->pdo->prepare('UPDATE '.TF_TABLE.' SET '.TF_VIDEO_ID.'=:update_id WHERE '.TF_ID.'=:id');
		try {
			if($stmt->execute(array(':id' => $id, ':update_id' => $update_id))) {
				if($stmt->rowCount() == 0) {
					$this->log('No rows were updated on '.TF_VIDEO_ID.'');
					return false;
				}
				return true;
			}
		} catch (PDOException $e) {
			$this->log('Unable to update video '.TF_ID.' exception thrown: ' .$e->getMessage());
		}
		return false; 
	}

	/**
	* Updates a row of true_ran table with TF_VIDEO_MESSAGE 
	* @param Integer $id The row to update.
	* @param String $message The message to add.
	* @return Boolean Returns true on success and false on failure.
	* No rows being updated counts as true 
	*/
	public function updateVideoMessage($id, $video_message)
	{
		$video_message = htmlspecialchars($video_message, ENT_QUOTES);

		$stmt = $this->pdo->prepare('UPDATE '.TF_TABLE.' SET '.TF_VIDEO_MESSAGE.'=:message WHERE '.TF_ID.'=:id');
		try {
			if($stmt->execute(array(':message' => $video_message, ':id' => $id))) {
				// success
				if($stmt->rowCount() == 0) {
					$this->log('No rows were updated via change to message or email_invite_list.');
					// considered making this return false; but I think it would be better to check on your own if updating same data or updating a non-existant profile
				}
				return true;
			}
		} catch (PDOException $e) {
			$this->log('Unable to add video message for '.$id.': ' .$e->getMessage());
		}
		return false; 
	}
	
	/**
	* Deletes the profile of the truefan referred to by TF_ID.
	* @param Integer $id 
	* @return Boolean Returns true on success and false on failure.
	* No rows being updated counts as a failure.
	*/
	public function deleteUser($id)
	{
		$stmt = $this->pdo->prepare('DELETE FROM '.TF_TABLE.' WHERE '.TF_ID.'=:id');
		try {
			if($stmt->execute(array(':id' => $id))) {
				// success
				return true;
			}
		} catch (PDOException $e) {
			$this->log('Unable to delete user: ' .$e->getMessage());
		}
		return false;
	}

	// *** Get Functions *** \\
	
	/**
	* Retrieves a true fan via TF_ID key
	* @param Integer $id Id key in db
	* @return Array|NULL Returns entire row of true fans table as assoc array or NULL on miss
	*/
	public function getUser($id)
	{
		$stmt = $this->pdo->prepare('SELECT * FROM '.TF_TABLE.' WHERE '.TF_ID.'=:id');
		if($stmt->execute(array(':id' => $id))) {
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			return $result;
		} 
		return NULL;
	}

	/**
	* Retrieves a true fan via TF_FOREIGN_ID key
	* @param String $key
	* @return Array|NULL Returns entire row of true fans table as assoc array or NULL on miss
	*/
	public function getUserByForeignId($key)
	{
		$stmt = $this->pdo->prepare('SELECT * FROM '.TF_TABLE.' WHERE '.TF_FOREIGN_ID.'=:key');
		if($stmt->execute(array(':key' => $key))) {
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			return $result;
		}
		return NULL;
	}
	
	/**
	* Retrieves all true fan entries in database
	* @return Array An associate array of all entries
	* TODO: Create a pagination strategy so that a large db doesn't DOS db server
	*/
	public function getAllEntries()
	{
		$stmt = $this->pdo->prepare('SELECT * FROM '.TF_TABLE.'');
		if($stmt->execute()) {
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $result;
		} 
		return NULL;
	}
	
	/**
	* Retrieves a true fan TF_ID from a TF_FOREIGN_ID 
	* @param String $foreign_id 
	* @return Array|NULL Returns entire row of true fans table as assoc array or NULL on miss
	*/
	public function getUserId($foreign_id)
	{
		$stmt = $this->pdo->prepare('SELECT '.TF_ID.' FROM '.TF_TABLE.' WHERE '.TF_EMAIL.'=:email');
		if($stmt->execute(array(':email' => $email))) {
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			return $result['id'];
		} else {
			return NULL;
		}
	}

	// *** Utility functions *** \\
	
	/**
	* Checks for a duplicate TF_EMAIL in database
	* @param String $email The key to match in the database
	* @return Boolean True on match otherwise false
	*/
	public function isDuplicateEmail($email)
	{
		$stmt = $this->pdo->prepare('SELECT '.TF_ID.' FROM '.TF_TABLE.' WHERE '.TF_EMAIL.'=:email');
		if($stmt->execute(array(':email' => $email))) {
			$result = $stmt->fetch(PDO::FETCH_NUM);
			if($result){
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	* Performs a series of regular expressions to find TF_VIDEO_ID
	* Accepts youtube embed iframes, shortened youtu.be urls, youtube viewing urls
	* and straight 11 character TF_ID's.
	* @param String $url_mess The incoming iframe, url, or TF_ID to search inside
	* @return String|NULL Returns 11 character TF_VIDEO_ID String on a match or null on a miss
	*/
	public function extractVideoId($url_mess)
	{
		// TODO: These regexes could use some unit tests to prove their correctness and ferret out any edge cases
		// FIXME: adding characters to an '.TF_ID.' within a url will cause regex to snip added characters and return an invalid '.TF_ID.'
		if(preg_match('/src=\"http[s]??:\/\/www.youtube.com\/embed\/([A-Za-z0-9_-]{11})[\?&]?[\S]*\"/', $url_mess, $matches) == 1) {  // carve out 11 digit '.TF_ID.' from iframe embed
			$this->log('Extracted '.TF_ID.' from src attribute of an iframe.');
			return $matches[1];
		} else if(preg_match('/http[s]??:\/\/youtu.be\/([A-Za-z0-9_-]{11})[\?&]?[\S]*/', $url_mess, $matches) == 1) { //carve out '.TF_ID.' from shortened youtube url
			$this->log('Extracted '.TF_ID.' from shortened url text.');
			return $matches[1];
		} else if(preg_match('/[\?&]v=([A-Za-z0-9_-]{11})[\?&]?[\S]*/', $url_mess, $matches) == 1) { //carve out '.TF_ID.' from youtube viewing url
			$this->log('Extracted '.TF_ID.' from viewing url text.');
			return $matches[1];
		} else if(preg_match('/^[A-Za-z0-9_-]{11}$/', $url_mess, $matches) == 1) { // take an 11 character string of valid characters as an '.TF_ID.'
			$this->log('Extracted plain '.TF_ID.'.');
			return $matches[0];
		} else {
			return NULL;
		}
	}

	/**
	* A function to log debug ouput. Currently must be hot-wired SpecialPage hosting database.
	* @param String $str string to add to log file
	*/
	protected function log($str)
	{
		tfDebug($str); // !! only works with OSE Special Page; !! TODO: indep log file editing
	}
}
