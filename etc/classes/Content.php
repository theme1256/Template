<?php
	/**
	*
	* Beskrivelse af hvad classen skal kunne
	*
	*/
	class Content{
		private $pdo;
		private $lang;

		function __construct($pdo, $lang){
			$this->pdo = $pdo;
			$this->lang = strtolower($lang);
		}

		/**
		*
		* Beskrivelse af hvad functionen gør
		*
		* @param   array  beskrivelse     #
		* 
		* @return  true / false
		*
		*/
		public function out($id){
			try{
				$q = $this->pdo->prepare("SELECT {$this->lang} FROM content WHERE contentID LIKE (:id)");
				$q->bindParam(":id", $id);
				$q->execute();
				$r = $q->fetch(PDO::FETCH_ASSOC);
				return $r[$this->lang];
			} catch(PDOException $e){
				return $e->getMessage();
			}
		}

		/**
		*
		* Beskrivelse af hvad functionen gør
		*
		* @param   array  beskrivelse     #
		* 
		* @return  true / false
		*
		*/
		public function clean($felt){
			$felt = stripslashes($felt);
			$felt = strip_tags($felt);
			$felt = addslashes($felt);
			return $felt;
		}

		public function hash($p){
			$cost = 10;
			$salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
			$salt = sprintf("$2a$%02d$", $cost) . $salt;
			return password_hash($p, PASSWORD_BCRYPT, ['cost' => $cost, 'salt' => $salt]);
		}

		public function curPageName(){
			$url = $_SERVER["SCRIPT_NAME"];
			if(DEBUG)
				error_log("url: ".$url);
			if(strpos($url, "scripts") == false){
				$var = substr($url, strrpos($url,"/")+1);
				$var = explode(".", $var);
				if(DEBUG)
					error_log("chopped url: ".$var[0]);
				return $var[0];
			}
			return $url;
		}

		public function activePage($page){
			if(is_array($page)){
				if(in_array($this->curPageName(), $page))
					return " class=\"active\"";
			} else{
				if($page == $this->curPageName())
					return " class=\"active\"";
			}
		}
		public function activeMenu($page){
			if(is_array($page)){
				if(in_array($this->curPageName(), $page))
					return " active";
			} else{
				if($page == $this->curPageName())
					return " active";
			}
		}

		public function login(){
			return ($_SESSION['login'] && isset($_SESSION['tID']));
		}
	}
?>