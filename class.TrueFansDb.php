<?php 
/* 
	microfundDb.php - This is a wrapper to microfund mySQL database. This class uses a PDO
			  database conenction and prepared statements to prevent SQL injection.
*/

echo 'truefans opened';

if(!@include_once("/home2/wordpage/.connect_microfund.php"))  
{
    @include_once("/home/jacob/.connect_truefan.php"); // local file
}
   
class TrueFansDb
{
	protected $bconnected; // Currently used to control error output of destructor which can't close a connection for a null object
	private $pdo;
	
	function __construct()
	{
		echo 'constructing';
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
	
	// addUser() - fills entire row of true_fans table
	public function addUser($name, $email, $video_id)
	{
		$stmt = $this->pdo->prepare('INSERT INTO true_fans(name,email,video_id) VALUES(:name, :email, :video_id)');
		try {
			if($stmt->execute(array(':name' => $name, ':email' => $email, ':video_id' => $video_id))) {
				// success
				$stmt2 = $this->pdo->prepare('SELECT id FROM true_fans WHERE name=:name AND email=:email');
				if($stmt2->execute(array(':name' => $name, ':email' => $email))) {
					$result = $stmt2->fetch(PDO::FETCH_ASSOC);
					return $result['id']; 
				}
			} 
		} catch (PDOException $e) {
			$this->log('Unable to insert user' .$e->getMessage()); 
		}
		return NULL;
	}
	
	// *** Get Functions ***
	
	// getUser() - gets entire row of true_fans table and returns an associative array
	public function getUser($id)
	{
		$stmt = $this->pdo->prepare('SELECT name, email, video_id FROM true_fans WHERE id=:id');
		if($stmt->execute(array(':id' => $id))) {
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			return $result;
		} else {
			return NULL;
		}
	}
	
	// getAllEntries() - returns a big fat associative array of all the users in the entire databse, currently not even paginated
	public function getAllEntries()
	{
		$stmt = $this->pdo->prepare('SELECT id, name, email, video_id FROM true_fans');
		if($stmt->execute()) {
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $result;
		} else {
			return NULL;
		}
	}
	
	// *** Utility functions ***
	
	// isDuplicateEmail - checks an email string to see if it's already in the database. Emails are UNIQUE in the database, inserting duplicates will fail, so this allows us to test in advance if an insert will fail due to a duplicate key.
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

	// mixed extractVideoId($url_mess) -  performs a regex on $url_mess and returns the 11 character youtube id on a match and NULL if the id cannot be extracted
	public function extractVideoId($url_mess)
	{
		// !!** Delete echo before going live **!!
		if(preg_match('/src=\"http[s]??:\/\/www.youtube.com\/embed\/([A-Za-z0-9_-]{11})[\?&]?[\S]*\"/', $url_mess, $matches) == 1) {  // carve out 11 digit id from iframe embed
			echo 'Extracted id from src attribute of an iframe. <br />';
			return $matches[1];
		} else if(preg_match('/http[s]??:\/\/youtu.be\/([A-Za-z0-9_-]{11})[\?&]?[\S]*/', $url_mess, $matches) == 1) { //carve out id from shortened youtube url
			echo 'Extracted id from shortened url text.<br />';
			return $matches[1];
		} else if(preg_match('/[\?&]v=([A-Za-z0-9_-]{11})[\?&]?[\S]*/', $url_mess, $matches) == 1) { //carve out id from youtube viewing url
			echo 'Extracted id from viewing url text.<br />';
			return $matches[1];
		} else {
			return NULL;
		}
	}

	protected function log($str)
	{
		tfDebug($str); // !! only works with OSE Special Page; !! TODO: indep log file editing
	}
}

?>