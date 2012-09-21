<?php
/**
 * class.TrueFanDb.php
 *
 */


class TrueFanDb
{
	private $db;

	function __construct()
	{
		$this->db = wfGetDB( DB_MASTER ); // !! need a way to differentiate between slave and master; construct a db for writing or not
		tfDebug('Constructing TrueFanDb');
	}

	function isTableCreated()
	{
		$sql = 'SHOW TABLES LIKE "true_fans"';
		try{
			$result = $this->db->query($sql);
			if($result->numRows() == 1) {
				tfDebug("Exactly one true_fans table.");
				return true;
			} else {
				tfDebug("No true_fans table.");
			}
		} catch (DBQueryError $e) {
			tfDebug($e->getMessage());
		}
		return false;
	}

	function createTrueFanTable()
	{
		wfDebugLog( 'ShareOSE', 'Creating some tables!!');
		$sql = ' CREATE TABLE /*_*/true_fans (
					tf_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
					tf_name VARCHAR(256) NOT NULL,
					tf_email VARCHAR(256) NOT NULL,
					tf_video_id VARCHAR(25)
					);';
	
		try{
			$result = $this->db->query($sql);
			if($result) {
				tfDebug("Created table true_fans.");
			} else {
				tfDebug("!!Could not create table true_fans!!");
			}
		} catch (DBQueryError $e) {
			tfDebug($e->getMessage());
		}
		
		$sql2 = 'CREATE INDEX /*i*/ tf_email ON true_fans (tf_email);';
		try{
			$result = $this->db->query($sql2);
			if($result) {
				tfDebug("Created index on email for true_fans.");
				return true;
			} else {
				tfDebug("!!Could not create index on email for true_fans!!");
			}
		} catch (DBQueryError $e) {
			tfDebug($e->getMessage());
		}
		
		return false;
	}

	function createEntry($name, $email, $url)
	{
		$result = $this->db->insert( 'true_fans', array('tf_name' => $name, 'tf_email' => $email, 'tf_video_id' => $video_id) );
		if($result) {
			return true;
		} else {
			return false;
		}
	}

}