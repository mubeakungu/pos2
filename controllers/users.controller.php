<?php

class ControllerUsers{

	/*=============================================
	CHECK A PASSWORD
	Accepts modern hashes and the old template hashes.
	Old hashes are upgraded the first time the user logs in.
	=============================================*/

	static private function checkPassword($password, $user){

		$stored = $user["password"];

		if(password_verify($password, $stored)){

			if(password_needs_rehash($stored, PASSWORD_DEFAULT)){

				UsersModel::mdlUpdateUser("users", "password", password_hash($password, PASSWORD_DEFAULT), "id", $user["id"]);

			}

			return true;

		}

		$legacy = crypt($password, '$2a$07$asxx54ahjppf45sd87a5a4dDDGsystemdev$');

		if(hash_equals($stored, $legacy)){

			UsersModel::mdlUpdateUser("users", "password", password_hash($password, PASSWORD_DEFAULT), "id", $user["id"]);

			return true;

		}

		return false;

	}

	/*=============================================
	USER LOGIN
	=============================================*/
	
	static public function ctrUserLogin(){

		if (isset($_POST["loginUser"])) {
			
			if (preg_match('/^[a-zA-Z0-9]+$/', $_POST["loginUser"]) && $_POST["loginPass"] !== "") {
				
				$table = 'users';

				$item = 'user';
				$value = $_POST["loginUser"];

				$answer = UsersModel::MdlShowUsers($table, $item, $value);

				// var_dump($answer);

				if($answer && $answer["user"] == $_POST["loginUser"] && self::checkPassword($_POST["loginPass"], $answer)){

					if($answer["status"] == 1){


						$_SESSION["loggedIn"] = "ok";
						$_SESSION["id"] = $answer["id"];
						$_SESSION["name"] = $answer["name"];
						$_SESSION["user"] = $answer["user"];
						$_SESSION["photo"] = $answer["photo"];
						$_SESSION["profile"] = $answer["profile"];

						/*=============================================
						Register date to know last_login
						=============================================*/


						$date = date('Y-m-d');
						$hour = date('H:i:s');

						$actualDate = $date.' '.$hour;

						$item1 = "lastLogin";
						$value1 = $actualDate;

						$item2 = "id";
						$value2 = $answer["id"];

						$lastLogin = UsersModel::mdlUpdateUser($table, $item1, $value1, $item2, $value2);

						if($lastLogin == "ok"){

							echo '<script>

								window.location = "home";

							</script>';

						}

					}else{
						
						echo '<br><div class="alert alert-danger">User is deactivated</div>';
					
					}

				}else{

					echo '<br><div class="alert alert-danger">User or password incorrect</div>';
				
				}
			
			}
		
		}
	
	}


	/*=============================================
	CREATE USER
	=============================================*/
	
	static public function ctrCreateUser(){

		if (isset($_POST["newUser"])) {
			
			if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["newName"]) &&
				preg_match('/^[a-zA-Z0-9]+$/', $_POST["newUser"]) &&
				strlen($_POST["newPasswd"]) >= 4){

				/*=============================================
				VALIDATE IMAGE
				=============================================*/

				$photo = "";
			
				if (isset($_FILES["newPhoto"]) && $_FILES["newPhoto"]["error"] == UPLOAD_ERR_OK){

					list($width, $height) = getimagesize($_FILES["newPhoto"]["tmp_name"]);
					
					$newWidth = 500;
					$newHeight = 500;

					/*=============================================
					Let's create the folder for each user
					=============================================*/

					$folder = "views/img/users/".$_POST["newUser"];

					if(!is_dir($folder)){ mkdir($folder, 0755, true); }

					/*=============================================
					PHP functions depending on the image
					=============================================*/

					if($_FILES["newPhoto"]["type"] == "image/jpeg"){

						$randomNumber = mt_rand(100,999);
						
						$photo = "views/img/users/".$_POST["newUser"]."/".$randomNumber.".jpg";
						
						$srcImage = imagecreatefromjpeg($_FILES["newPhoto"]["tmp_name"]);
						
						$destination = imagecreatetruecolor($newWidth, $newHeight);

						imagecopyresized($destination, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

						imagejpeg($destination, $photo);

					}

					if ($_FILES["newPhoto"]["type"] == "image/png") {

						$randomNumber = mt_rand(100,999);
						
						$photo = "views/img/users/".$_POST["newUser"]."/".$randomNumber.".png";
						
						$srcImage = imagecreatefrompng($_FILES["newPhoto"]["tmp_name"]);
						
						$destination = imagecreatetruecolor($newWidth, $newHeight);

						imagecopyresized($destination, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

						imagepng($destination, $photo);
					}

				}

				$table = 'users';

				$encryptpass = password_hash($_POST["newPasswd"], PASSWORD_DEFAULT);

				$data = array('name' => $_POST["newName"],
							  'user' => $_POST["newUser"],
								'password' => $encryptpass,
								'profile' => $_POST["newProfile"],
								'photo' => $photo);

				$answer = UsersModel::mdlAddUser($table, $data);

				if ($answer == 'ok') {

						echo '<script>
						
						swal({
							type: "success",
							title: "User added succesfully!",
							showConfirmButton: true,
							confirmButtonText: "Close"

						}).then(function(result){

							if(result.value){

								window.location = "users";
							}

						});
						
						</script>';

				}
			
			}else{

				echo '<script>
					
					swal({
						type: "error",
						title: "No special characters or blank fields",
						showConfirmButton: true,
						confirmButtonText: "Close"
			
						}).then(function(result){

							if(result.value){

								window.location = "users";
							}

						});
					
				</script>';
			}
			
		}
	}

	/*=============================================
	SHOW USER
	=============================================*/

	static public function ctrShowUsers($item, $value){

		$table = "users";

		$answer = UsersModel::MdlShowUsers($table, $item, $value);

		return $answer;
	}

	/*=============================================
	EDIT USER
	=============================================*/

	static public function ctrEditUser(){

		if (isset($_POST["EditUser"])) {
			
			if (preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["EditName"])){

				/*=============================================
				VALIDATE IMAGE
				=============================================*/

				$photo = $_POST["currentPicture"];

				if(isset($_FILES["editPhoto"]["tmp_name"]) && !empty($_FILES["editPhoto"]["tmp_name"])){

					list($width, $height) = getimagesize($_FILES["editPhoto"]["tmp_name"]);
					
					$newWidth = 500;
					$newHeight = 500;

					/*=============================================
					Let's create the folder for each user
					=============================================*/

					$folder = "views/img/users/".$_POST["EditUser"];

					/*=============================================
					we ask first if there's an existing image in the database
					=============================================*/

					if (!empty($_POST["currentPicture"])){
						
						if(is_file($_POST["currentPicture"]) && strpos(realpath($_POST["currentPicture"]), realpath("views/img")) === 0){ unlink($_POST["currentPicture"]); }

					}else{

						if(!is_dir($folder)){ mkdir($folder, 0755, true); }

					}

					/*=============================================
					PHP functions depending on the image
					=============================================*/

					if($_FILES["editPhoto"]["type"] == "image/jpeg"){

						/*We save the image in the folder*/

						$randomNumber = mt_rand(100,999);
						
						$photo = "views/img/users/".$_POST["EditUser"]."/".$randomNumber.".jpg";
						
						$srcImage = imagecreatefromjpeg($_FILES["editPhoto"]["tmp_name"]);
						
						$destination = imagecreatetruecolor($newWidth, $newHeight);

						imagecopyresized($destination, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

						imagejpeg($destination, $photo);

					}
					
					if ($_FILES["editPhoto"]["type"] == "image/png") {

						/*We save the image in the folder*/

						$randomNumber = mt_rand(100,999);
						
						$photo = "views/img/users/".$_POST["EditUser"]."/".$randomNumber.".png";
						
						$srcImage = imagecreatefrompng($_FILES["editPhoto"]["tmp_name"]);
						
						$destination = imagecreatetruecolor($newWidth, $newHeight);

						imagecopyresized($destination, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

						imagepng($destination, $photo);
					}

				}

				
				$table = 'users';

				if($_POST["EditPasswd"] != ""){

					if(strlen($_POST["EditPasswd"]) >= 4){

						$encryptpass = password_hash($_POST["EditPasswd"], PASSWORD_DEFAULT);

					}

					else{

						echo '<script>
					
							swal({
								type: "error",
								title: "The password must have at least 4 characters",
								showConfirmButton: true,
								confirmButtonText: "Close"

								}).then(function(result){
										
									if (result.value) {
						
										window.location = "users";

									}
								});
							
						</script>';

						return;
					}
				
				}else{

					/* Keep the password already stored (never trust the form for this) */
					$existingUser = UsersModel::MdlShowUsers($table, "user", $_POST["EditUser"]);

					$encryptpass = $existingUser["password"];
					
				}

				$data = array('name' => $_POST["EditName"],
								'user' => $_POST["EditUser"],
								'password' => $encryptpass,
								'profile' => $_POST["EditProfile"],
								'photo' => $photo);

				$answer = UsersModel::mdlEditUser($table, $data);

				if ($answer == 'ok') {
					
					echo '<script>
					
						swal({
							type: "success",
							title: "User edited succesfully!",
							showConfirmButton: true,
							confirmButtonText: "Close"

						 }).then(function(result){
							
							if (result.value) {

								window.location = "users";
							}

						});
					
					</script>';
				}
				else{
					echo '<script>
						
						swal({
							type: "error",
							title: "No special characters in the name or blank field",
							showConfirmButton: true,
							confirmButtonText: "Close"
							 }).then(function(result){
									
								if (result.value) {

									window.location = "users";
								
								}

							});
						
					</script>';
				}
			
			}	
		
		}
	
	}

	/*=============================================
	DELETE USER
	=============================================*/

	static public function ctrDeleteUser(){

		if(isset($_GET["userId"])){

			$table ="users";
			$data = $_GET["userId"];

			if($_GET["userPhoto"] != ""){

				if(is_file($_GET["userPhoto"]) && strpos(realpath($_GET["userPhoto"]), realpath("views/img")) === 0){ unlink($_GET["userPhoto"]); }				
				@rmdir('views/img/users/'.$_GET["username"]);

			}

			$answer = UsersModel::mdlDeleteUser($table, $data);

			if($answer == "ok"){

				echo'<script>

				swal({
					  type: "success",
					  title: "The user has been succesfully deleted",
					  showConfirmButton: true,
					  confirmButtonText: "Close"

					  }).then(function(result){
					  	
						if (result.value) {

						window.location = "users";

						}
					})

				</script>';

			}		

		}

	}
	
}

