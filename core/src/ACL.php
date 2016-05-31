<?php
	/**
	* @uses DALi Class
	*
	*/

	require_once 'DALi.php';
	class ACL extends DALi {

		public function __construct() {
			DALi::init();
		}
		/**
		 * @var
		 */
		private static $error_message;
		/**
		 * @return Boolean True if $username and $password are correct
		 */
		public static function login() {
			$username = trim($_POST['username']);
    	    $password = trim($_POST['password']);
    	    if (!isset($_SESSION))
				session_start();
			if (!ACL::checkLoginInDB($username,$password))
				return false;
			$_SESSION[ACL::getLoginSessionVar()] = $username;
			if (isset($_POST['remember_me']))
				setcookie('remember_me', $username, $year);
			else {
				if (isset($_COOKIE['remember_me'])) {
					$past = time() - 100;
					setcookie('remember_me', gone, $past);
				}
			}
			$_COOKIE['session'] = $_SESSION;
			return true;
		}
		/**
		 * @param
		 * @return
		 */
		private static function checkLoginInDB($username, $password) {
	        $nresult = parent::query("SELECT * FROM users WHERE username = '$username' LIMIT 1");
	        $salt = $nresult[0]['salt'];
	        $encrypted_password = $nresult[0]['password'];
	        $hash = ACL::checkhashSSHA($salt, $password);
	        $query = "SELECT * FROM users WHERE username='$username' AND password='$hash'";
	        $result = parent::query($query);
	        if (!$result) {
	            ACL::handleError("Error logging in. The username and/or password is incorrect");
	            return false;
	        }
	        $_SESSION['UserID']         = $result[0]['id'];
	        $_SESSION['first_name']     = $result[0]['fname'];
	        $_SESSION['last_name']      = $result[0]['lname'];
	        $_SESSION['name_of_user']   = $result[0]['fname'] ." ". $result[0]['lname'];
	        $_SESSION['username']       = $result[0]['username'];
	        $_SESSION['email_of_user']  = $result[0]['email'];
	        return true;
		}
		/**
		 * @param
		 */
		private static function handleError($statement) {
			ACL::$error_message .= $statement."\r\n";
		}
		/**
		 * @param
		 */
		private static function checkhashSSHA($salt, $pass) {
			$hash = base64_encode(sha1($pass . $salt, true) . $salt);
    	return $hash;
		}
		/**
		 * @return
		 */
		private static function getLoginSessionVar() {
			$retvar = md5(parent::getRandomKey());
            $retvar = 'usr_'.substr($retvar,0,10);
            return $retvar;
		}
		/**
		 * @param
		 */
		public static function getErrorMessage() {
			if (empty(ACL::$error_message)) 
				return '';
			$errormsg = nl2br(htmlentities(ACL::$error_message));
			return $errormsg;
		}
		/**
		 * @return
		 */
		public static function checkLogin() {
           $sessionvar = ACL::getLoginSessionVar();
           if(!isset($_SESSION['UserID']))
              return false;
           return true;
        }
        /**
         * @return
         */
        public static function userFullName() {
            return isset($_SESSION['name_of_user'])?$_SESSION['name_of_user']:'';
        }
        /**
         * @return
         */
        public static function userFirstName() {

            return isset($_SESSION['first_name'])?$_SESSION['first_name']:'';
        }
        /**
         * @return
         */
        public static function userLastName() {
            return isset($_SESSION['last_name'])?$_SESSION['last_name']:'';
        }
        /**
         * @return
         */
        public static function userEmail() {
            return isset($_SESSION['email_of_user'])?$_SESSION['email_of_user']:'';
        }
        /**
         * @method
         */
        public static function logOut() {
            session_start();
			session_destroy();
			unlink($_SESSION);
            $sessionvar = ACL::GetLoginSessionVar();
            $_SESSION[$sessionvar]=NULL;
            unset($_SESSION[$sessionvar]);
    				ACL::RedirectTo('/Login/');
        }
    		/**
    		 * @method
    		 */
		private static function redirectTo($url) {
			header("Location: $url");
			exit;
		}
        /**
         * @return
         */
        public static function resetPassword() {
            if(empty($_GET['email'])) {
                ACL::HandleError("Email is empty!");
                return false;
            }
            if(empty($_GET['code'])) {
                ACL::HandleError("reset code is empty!");
                return false;
            }
            $email = trim($_GET['email']);
            $code = trim($_GET['code']);
            if(ACL::GetResetPasswordCode($email) != $code) {
                ACL::HandleError("Bad reset code!");
                return false;
            }
            $user_rec = array();
            if(!ACL::GetUserFromEmail($email,$user_rec)) {
                return false;
            }
            $new_password = ACL::ResetUserPasswordInDB($user_rec);
            if(false === $new_password || empty($new_password)) {
                ACL::HandleError("Error updating new password");
                return false;
            }
           /* if(false == ACL::SendNewPassword($user_rec,$new_password)) {
                ACL::HandleError("Error sending new password");
                return false;
            }*/
            return true;
        }
        /**
         * @return
         */
        public function changePassword() {
            if(!ACL::checkLogin()) {
                ACL::handleError("Not logged in!");
                return false;
            }
            if(empty($_POST['oldpwd'])) {
                ACL::handleError("Old password is empty!");
                return false;
            }
            if(empty($_POST['newpwd'])) {
                ACL::handleError("New password is empty!");
                return false;
            }
            $user_rec = array();
            if(!ACL::getUserFromEmail($this->userEmail(),$user_rec)) {
                return false;
            }
            $pwd = trim($_POST['oldpwd']);
            $salt = $user_rec['salt'];
            $hash = ACL::checkhashSSHA($salt, $pwd);
            if($user_rec['password'] != $hash) {
                ACL::handleError("The old password does not match!");
                return false;
            }
            $newpwd = trim($_POST['newpwd']);
            if(!ACL::changePasswordInDB($user_rec, $newpwd)) {
                return false;
            }
            return true;
        }
}
