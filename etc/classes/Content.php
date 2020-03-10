<?php
	require __DIR__ . "/traits/parse.php";
	/**
	*
	* Beskrivelse af hvad classen skal kunne
	*
	*/
	class Content {
		use \Traits\Parse;
		private $pdo;
		private $lang;
		private $css_rule;
		private $debug = false;

		function __construct(\PDO $pdo, String $lang) {
			$this->pdo = $pdo;
			$this->lang = \strtolower($lang);
			if(CHROME)
				$this->css_rule = 'rel="preload" as="style" onload="this.rel=\'stylesheet\'"';
			else
				$this->css_rule = 'rel="stylesheet"';
			if(DEBUG == true)
				$this->debug = true;
		}
		public function isItThisPage($check) {
			if (\is_array($check)) {
				foreach ($check as $site) {
					if (\strpos($site, "!") !== false){
						$site = \str_replace("!", "", $site);
						if ($site == $this->pageName() || \strpos($this->pageName(), $site) !== false)
							return false;
					} else {
						if ($site == $this->pageName() || \strpos($this->pageName(), $site) !== false)
							return true;
					}
				}
				return false;
			} else {
				if ($check == $this->pageName() || \strpos($this->pageName(), $check) !== false)
					return true;
				else
					return false;
			}
		}
		public function pageName() {
			// Finder URL og erstatter / med /dashboard og fjerner .php
			if ($_SERVER['REQUEST_URI'] == "/")
				$u = "/dashboard";
			else
				$u = \str_replace(".php", "", $_SERVER['REQUEST_URI']);
			// Fjerner det der står efter ?
			if (\strpos($u, "?")){
				$U = \explode("?", $u);
				$u = $U[0];
			}
			// Fjerner det afsluttende /
			if ($u[\strlen($u)-1] == "/")
				$u = \substr($u, 0, \strlen($u)-1);
			return $u;
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
		public function out($id) {
			try {
				$q = $this->pdo->prepare("SELECT {$this->lang} FROM content WHERE contentID LIKE (:id)");
				$q->bindParam(":id", $id);
				$q->execute();
				$r = $q->fetch(\PDO::FETCH_ASSOC);
				return $r[$this->lang];
			} catch (\PDOException $e) {
				return $e->getMessage();
			}
		}

		public function nice_number($in, $pre = "+45"){
			$in = \str_replace(" ", "", $in);
			$in = \preg_replace("[^0-9+]", "", $in);
			$in = \substr($in, -8);
			$in = $pre . $in;
			return $in;
		}

		public function nice_number_view($in, $pre = "+45"){
			$in = $this->nice_number($in, "");
			$in = $pre . " ".\substr($in, 0, 2)." ".\substr($in, 2, 2)." ".\substr($in, 4, 2)." ".\substr($in, 6, 2);
			return $in;
		}

		public function nice_email($in){
			return strtolower($in);
		}

		private function link($link, $text){
			$text = "<a href='{$link}'>{$text}</a>";
			return $text;
		}

		public function phone_link($nr) {
			return $this->link("tel:{$this->nice_number($nr)}", $this->nice_number_view($nr));
		}

		public function gravatar($email, $size = 50, $default = null){
			if(is_null($default))
				$default = $_SERVER["SERVER_NAME"] . "/assets/img/user.jpg";
			return "https://www.gravatar.com/avatar/" . \md5(\strtolower(\trim($email))) . "?d=" . \urlencode($default) . "&s=" . $size;
		}

		public function hash($p) {
			$cost = 10;
			$salt = \strtr(\base64_encode(\mcrypt_create_iv(16, \MCRYPT_DEV_URANDOM)), '+', '.');
			$salt = \sprintf("$2a$%02d$", $cost) . $salt;
			return \password_hash($p, PASSWORD_BCRYPT, ['cost' => $cost, 'salt' => $salt]);
		}

		public function js(String $src, $opts = []) {
			$opts = $this->parse($opts, ["async" => true, "inline" => false]);
			if ($opts["inline"])
				return "<script type=\"text/javascript\">".\file_get_contents($_SERVER["DOCUMENT_ROOT"] . \explode("?", $src)[0]) . "</script>";
			else
				return "<script ".($opts["async"] == true ? "async": "")." type=\"text/javascript\" src=\"" . $src . ($this->debug ? "?debug=".\time() : "") . "\"></script>\n";
		}

		public function css(String $src, $opts = []) {
			$opts = $this->parse($opts, ["inline" => false]);
			if ($opts["inline"])
				return "<style type='text/css'>".\file_get_contents($_SERVER["DOCUMENT_ROOT"] . \explode("?", $src)[0]) . "</style>";
			else
				return "<link ".$this->css_rule." type=\"text/css\" href=\"" . $src . ($this->debug ? "debug=".\time() : "") . "\">\n";
		}
	}
?>