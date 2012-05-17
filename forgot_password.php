<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/applicationdialog.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<!-- InstanceBeginEditable name="doctitle" -->
<title>Note It!</title>
<!-- InstanceEndEditable -->
<!-- InstanceBeginEditable name="head" -->
<!-- InstanceEndEditable -->
<link href="css/noteit.css" rel="stylesheet" type="text/css" />
<style type="text/css">
	
	a:link {
		color: 				#E7C681;
		text-decoration: 	underline; 
		font-size: 			0.8em;
	}
	
	a:visited {
		color:	 			#E7C681;
		text-decoration: 	underline;
	}
	
	a:hover, a:active, a:focus {
		text-decoration: 	none;
	}
	
	.dialog_box {
		width: 				400px;
		min-width: 			400px;
		border:				thin;
		border-radius:		20px;
		padding:			20px;
		filter: DropShadow(Color=CCCCCC, OffX=5px, OffY=5px, Positive=1);
		-moz-box-shadow: 10px 5px 10px #CCC;
		-webkit-box-shadow: #CCCCCC 10px 5px 10px;
	}
	
	.error_box {
		font-size: 10px;
		background: red;
		color: white;
		border: thick;
		border-color: #000;
		padding: 5px;
		text-align: center;
		visibility: collapse;
	}
	
</style>

<script type="text/javascript">

	function hideError(id) {
		$(id).css("visibility", "collapse");
		$(id).text("");
		$(id).hide("slow");
	}
	
	function displayError(id, message) {
		$(id).css("visibility", "visible");
		$(id).text(message);
		$(id).show("fast");
	}

</script>
</head>

<body>
	<table align="center" cellpadding="0">
		<tr height="100px;">
			<td>
			</td>
		<tr>
			<td valign="middle">
				<div id="container" class="container dialog_box">
					<div id="logo" align="center">
						<img align="middle" src="img/logo.png" width="96" height="96" alt="Note It!" />
					</div>
					<div id="dialogbox" align="left">
					<!-- InstanceBeginEditable name="dialogcontent" -->
<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . ("controller/controllerdefines.php");
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . ("model/noteitdb.php");

$db_con = NULL;

try {

	global $config;

	$userID 			= isset($_REQUEST[Command::$arg1]) 		? intval($_REQUEST[Command::$arg1]) : 0;
	$token 				= isset($_REQUEST[Command::$arg2]) 		? $_REQUEST[Command::$arg2] 		: "";
	$set_password		= isset($_REQUEST['set']) 				? intval($_REQUEST['set']) 			: 0;
	$new_password 		= isset($_REQUEST['new_password']) 		? $_REQUEST['new_password'] 		: "";
	$confirm_password 	= isset($_REQUEST['confirm_password']) 	? $_REQUEST['confirm_password'] 	: "";
	
	if ($userID <= 0 || $token == '') {
		throw new Exception("Error Processing Request.");
	}
	
	$db_con = new MySQLi(
			$config['MYSQL_SERVER'],
			$config['MYSQL_USER'],
			$config['MYSQL_PASSWD'],
			$config['MYSQL_DB']);
		
	//
	// Use this instead of db_con::connect_error if you need to ensure
	// compatibility with PHP versions prior to 5.2.9 and 5.3.0.
	//
	if (mysqli_connect_error()) {
		throw new Exception(
				'Could not connect to Server: ' .
				"(" . mysqli_connect_errno() . ")");
	}
		
	if (!$db_con->set_charset("utf8")) {
		throw new Exception('Could not set charset to utf8. PHP version 5.2.3 or greater required');
	}
	
	if ($set_password) {
		
		if ($new_password == '' || $confirm_password == '') 
			throw new Exception("Password cannot be blank.");
		else if ($new_password != $confirm_password)
			throw new Exception("Passwords do not match.");
		
		NoteItDB::is_valid_password($new_password);
		NoteItDB::do_reset_password($userID, $token, $new_password);
		
		echo "Password has been updated";
				
	} else {
	
		$salt = $config['SALT'];
		$salted_hash = sha1($salt . $new_password);
	
		$sql = sprintf("SELECT 
							`emailID` 
						FROM 
							`users` AS `usrs`
						INNER JOIN 
							`password_recovery` AS `pr`
						ON 
							`usrs`.`userID`=`pr`.`user_id_FK`
						WHERE 
							`usrs`.`userID`=%d AND 
							`pr`.`recovery_passwd`=UNHEX('%s') AND 
							TIMESTAMPDIFF(HOUR, `pr`.`created_datetime`, NOW()) < 24",
						$userID,
						$token);
		
		$result = $db_con->query($sql);
		if (!$result || mysqli_num_rows($result) <= 0) {
			throw new Exception("
				Either the reset password link you used is invalid or the link has expired. 
				Please resubmit your request.");
		}
		
		if ($row = mysqli_fetch_assoc($result)) {
?>
            <form 
                action="forgot_password.php" 
                method="post" 
                enctype="application/x-www-form-urlencoded" 
                target="_self">
            
            <table cellpadding="5px">
                <tr>
                    <td>Email</td>
                    <td>
                        <input name="set" type="hidden" value="1" />
                        <input name="arg1" type="hidden" value="<?php echo $userID?>" />
                        <input name="arg2" type="hidden" value="<?php echo $token?>" />
                        <input id="email_ID" name="email_ID" class="textButtonStyle" readonly="readonly" value="<?php echo $row['emailID']?>" />
                    </td>
                </tr>
                <tr>
                    <td>New Password</td>
                    <td>
                        <input id="new_password" name="new_password" type="password" class="textButtonStyle" value=""/>
                    </td>
                </tr>
                <tr>
                    <td>Confirm Password</td>
                    <td>
                        <input id="confirm_password" name="confirm_password" type="password" class="textButtonStyle" value=""/>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" align="right">
                        <input name="cmdOK" style="float:right" type="submit" value="OK" class="appButton"/>
                    </td>
                </tr>						
            </table>
            
        </form>
<?php 
		}
	}
	
	$db_con->close();
	$db_con = NULL;

} catch (Exception $e) {
		
	if ($db_con != NULL) {
		$db_con->close();
		$db_con = NULL;
	}
	
	echo "Error Occurred: " . $e->getMessage();
}

?>
					<!-- InstanceEndEditable -->
					</div>
				</div>	
				<div align="center" id="footer" style="padding:20px;">
				<!-- InstanceBeginEditable name="DialogFooterLinks" -->
				
				<!-- InstanceEndEditable -->
				</div>
			</td>
		</tr>
	</table>
</body>
<!-- InstanceEnd --></html>
