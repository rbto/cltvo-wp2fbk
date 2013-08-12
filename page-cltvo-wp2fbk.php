<?php 

//Mover esta template al dir del theme
//para correr la autenticación de FBK

$cltvo_fbk_options = array(
	'appId' => '190429214467897',
	'secret'=> '8fa21ba73855a1d56d2b3c7cbbbc9e9c'
);

if( isset($_POST['pagina_por_administrar']) ){
	//Si ya le dió permisos a la App
	//y escogió la página que va a admin

	//$pagina_arr = explode(', ', $_POST['pagina_por_administrar']);
	$cltvo_fbk_options['pagId'] = $pagina_arr[0];
	//$cltvo_fbk_options['pagToken'] = $pagina_arr[1];

	// echo "<pre>";
	// print_r($cltvo_fbk_options);
	// echo "</pre>";
	if( update_option( 'cltvo_fbk_options', $cltvo_fbk_options ) ){
		$redirect_admin_url = admin_url();
		header("Location: {$redirect_admin_url}");
	}


}else{
	//Si no ha escogido la página que quiere administrar

	//include the Facebook PHP SDK
	include_once 'fbk-api/facebook.php';
	 
	//instantiate the Facebook library with the APP ID and APP SECRET
	$facebook = new Facebook(array(
	   'appId' => $cltvo_fbk_options['appId'],
	   'secret' => $cltvo_fbk_options['secret'],
	   'cookie' => true
	));

	//Get the FB UID of the currently logged in user
	$user = $facebook->getUser();
	 
	//if the user has already allowed the application, you'll be able to get his/her FB UID
	if($user) {
	   //do stuff when already logged in

		//start the session if needed
		if( session_id() ) {

		} else {
			session_start();
		}

		//get the user's access token
		$facebook->setExtendedAccessToken();
		$access_token = $facebook->getAccessToken();

		//check permissions list
		$permissions_list = $facebook->api(
			'/me/permissions',
			'GET',
			array(
				'access_token' => $access_token
			)
		);

		//check if the permissions we need have been allowed by the user
		//if not then redirect them again to facebook's permissions page
		$permissions_needed = array('publish_stream', 'read_stream', 'manage_pages');
		foreach($permissions_needed as $perm) {
			if( !isset($permissions_list['data'][0][$perm]) || $permissions_list['data'][0][$perm] != 1 ) {
				$login_url_params = array(
					'scope' => $perm,
					'fbconnect' =>  1,
					'display'   =>  "page",
					'next' => 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']
				);
				$login_url = $facebook->getLoginUrl($login_url_params);
				header("Location: {$login_url}");
				exit();
			}
		}

		//if the user has allowed all the permissions we need,
		//get the information about the pages that he or she managers
		$accounts = $facebook->api(
		   '/me/accounts',
		   'GET',
		   array(
		      'access_token' => $access_token
		   )
		);

		// echo "<pre>";
		// print_r($accounts);
		// echo "</pre>";

		//la información realmente está en $accounts['data']
		$accounts = $accounts['data'];

		echo '<h1>Cuál página quieres administrar:</h1>';
		echo '<form method="post" >';
		echo '<select name="pagina_por_administrar">';
		foreach($accounts as $account){
			echo '<option value="';
			echo $account['id'];
			// echo ', ';
			// echo $account['access_token'];
			echo '">';
			echo $account['name'];
			echo '</option>';
		}
		echo '</select>';
		echo '<input type="submit" value="submit">';
		echo '</form>';

	} else {
	   //if not, let's redirect to the ALLOW page so we can get access
	   //Create a login URL using the Facebook library's getLoginUrl() method
	   $login_url_params = array(
	      'scope' => 'publish_stream,read_stream,manage_pages',
	      'fbconnect' =>  1,
	      'redirect_uri' => 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']
	   );
	   $login_url = $facebook->getLoginUrl($login_url_params);
	    
	   //redirect to the login URL on facebook
	   header("Location: {$login_url}");
	   exit();
	}
}
?>