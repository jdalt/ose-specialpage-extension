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


if(!@include_once("/home2/wordpage/.connect_truefans.php"))
{
    @include_once("/home/jacob/.connect_truefans.php"); // local file
}
   
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
		$dsn = 'mysql:dbname='.getDatabase().';host='.getHost();
		try {
			$this->pdo = new PDO($dsn, getUser(),getPass());
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
	* Adds a new user and fills name, email, and video_id fields
	* @param String $name
	* @param String $email 
	* @param String $video_id
	* @param Boolean $extractUrl Optional parameter to extract url. 
	* Defaults to true. When set to false, video_id inserted w/o checks.
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

		$stmt = $this->pdo->prepare('INSERT INTO true_fans(foreign_id, name,email,video_id) VALUES(:foreign_id, :name, :email, :video_id)');
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
	* Updates a video_id
	* @param String $id 
	* @param String $video_id
	* @param Boolean $extractUrl Optional parameter to extract url. 
	* Defaults to true. When set to false, video_id inserted w/o checks.
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
				$this->log('Unable to update extract video id.'); 
				return false;
			}
		} 
	
		$stmt = $this->pdo->prepare('UPDATE true_fans SET video_id=:update_id WHERE id=:id');
		try {
			if($stmt->execute(array(':id' => $id, ':update_id' => $update_id))) {
				if($stmt->rowCount() == 0) {
					$this->log('No rows were updated on video_id');
					return false;
				}
				return true;
			}
		} catch (PDOException $e) {
			$this->log('Unable to update video id exception thrown: ' .$e->getMessage());
		}
		return false; 
	}

	/**
	* Updates a row of true_ran table with video_message and email_invite_list
	* @param Integer $id The row to update
	* @param String $message The message to add
	* @return Boolean Returns true on success and false on failure.
	* No rows being updated counts as a failure.
	*/
	public function updateInvitation($id, $message, $emailStr)
	{
		$message = htmlspecialchars($message, ENT_QUOTES);
		$emailStr = htmlspecialchars($emailStr, ENT_QUOTES);

		$stmt = $this->pdo->prepare('UPDATE true_fans SET video_message=:message, email_invite_list=:emailStr WHERE id=:id');
		try {
			if($stmt->execute(array(':message' => $message, ':emailStr' => $emailStr, ':id' => $id))) {
				// success
				if($stmt->rowCount() == 0) {
					$this->log('No rows were updated via change to message or email_invite_list.');
					return false;
				}
				return true;
			}
		} catch (PDOException $e) {
			$this->log('Unable to add message and email list invitation for '.$id.': ' .$e->getMessage());
		}
		return false; 
	}

	
	/**
	* Deletes the profile of the truefan referred to by id.
	* @param Integer $id 
	* @return Boolean Returns true on success and false on failure.
	* No rows being updated counts as a failure.
	*/
	public function deleteUser($id)
	{
		$stmt = $this->pdo->prepare('DELETE FROM true_fans WHERE id=:id');
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
	* Retrieves a true fan via id key
	* @param Integer $id Id key in db
	* @return Array|NULL Returns entire row of true fans table as assoc array or NULL on miss
	*/
	public function getUser($id)
	{
		$stmt = $this->pdo->prepare('SELECT * FROM true_fans WHERE id=:id');
		if($stmt->execute(array(':id' => $id))) {
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			return $result;
		} 
		return NULL;
	}

	/**
	* Retrieves a true fan via foreign_id key
	* @param String $key
	* @return Array|NULL Returns entire row of true fans table as assoc array or NULL on miss
	*/
	public function getUserByForeignId($key)
	{
		$stmt = $this->pdo->prepare('SELECT * FROM true_fans WHERE foreign_id=:key');
		if($stmt->execute(array(':key' => $key))) {
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			return $result;
		}
		return NULL;
	}
	
	/**
	* Retrieves all true fan entries in database
	* @return Array An associate array of all entries
	* TODO: Create a pagination strategy so that a large db doesn't DDOS db server
	*/
	public function getAllEntries()
	{
		$stmt = $this->pdo->prepare('SELECT * FROM true_fans');
		if($stmt->execute()) {
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $result;
		} 
		return NULL;
	}
	
	/**
	* Retrieves a true fan id from a foreign_id 
	* @param String $foreign_id 
	* @return Array|NULL Returns entire row of true fans table as assoc array or NULL on miss
	*/
	public function getUserId($foreign_id)
	{
		$stmt = $this->pdo->prepare('SELECT id FROM true_fans WHERE email=:email');
		if($stmt->execute(array(':email' => $email))) {
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			return $result['id'];
		} else {
			return NULL;
		}
	}

	// *** Utility functions *** \\
	
	/**
	* Checks for a duplicate email in database
	* @param String $email The key to match in the database
	* @return Boolean True on match otherwise false
	*/
	public function isDuplicateEmail($email)
	{
		$stmt = $this->pdo->prepare('SELECT id FROM true_fans WHERE email=:email');
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
	* Performs a series of regular expressions to find video_id
	* Accepts youtube embed iframes, shortened youtu.be urls, youtube viewing urls
	* and straight 11 character ids.
	* @param String $url_mess The incoming iframe, url, or id to search inside
	* @return String|NULL Returns 11 character video_id String on a match or null on a miss
	*/
	public function extractVideoId($url_mess)
	{
		// TODO: These regexes could use some unit tests to prove their correctness and ferret out any edge cases
		// FIXME: adding characters to an id within a url will cause regex to snip added characters and return an invalid id
		if(preg_match('/src=\"http[s]??:\/\/www.youtube.com\/embed\/([A-Za-z0-9_-]{11})[\?&]?[\S]*\"/', $url_mess, $matches) == 1) {  // carve out 11 digit id from iframe embed
			$this->log('Extracted id from src attribute of an iframe.');
			return $matches[1];
		} else if(preg_match('/http[s]??:\/\/youtu.be\/([A-Za-z0-9_-]{11})[\?&]?[\S]*/', $url_mess, $matches) == 1) { //carve out id from shortened youtube url
			$this->log('Extracted id from shortened url text.');
			return $matches[1];
		} else if(preg_match('/[\?&]v=([A-Za-z0-9_-]{11})[\?&]?[\S]*/', $url_mess, $matches) == 1) { //carve out id from youtube viewing url
			$this->log('Extracted id from viewing url text.');
			return $matches[1];
		} else if(preg_match('/^[A-Za-z0-9_-]{11}$/', $url_mess, $matches) == 1) { // take an 11 character string of valid characters as an id
			$this->log('Extracted plain id.');
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
