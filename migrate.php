<?php

    
    
    include 'header.php';

    $icon = "remove";

    $username = "";
    $password = "";

    $message1 = "Ocorreu um erro!";
    $message2 = "Tenta novamente.";

    $url = "/";
    $action = "Voltar";

    function process_request(&$username, &$password, &$message1, &$message2, &$icon, &$url, &$action) {

        if(!empty($username) && !empty($password)) {

            //if(true) {
            if(isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])){
                
                //secret key
                $secret = 'GOOGLE_RECAPTCHA_SECRET';
                //get verify response data
                
                // Google API
                $api = 'https://www.google.com/recaptcha/api/siteverify?secret='.$secret.'&response='.$_POST['g-recaptcha-response'];
                $verifyResponse = file_get_contents($api);
                $responseData = json_decode($verifyResponse);
                
                //if(true) {
                if($responseData->success){
                    
                    // allows direct call to phpBB functions
                    //define('IN_PHPBB', true);

                    include './forum/config.php';
                    

                    $conn_string = "host=" . $dbhost . " port=5432" . " dbname=myneect" . 
                        " user=" . $dbuser . " password=" .$dbpasswd;

                    $conn = pg_connect($conn_string);

                    if (!$conn) {
                        $message2 = "Não foi possível ligar à base de dados.";
                        error_log("MIGRATE: Could not connect to database.");
                        return;
                    }

                    unset($dbpasswd);

                    $username = pg_escape_string(utf8_encode($username)); 

                    $query = "SELECT id, salt, password, migrated 
                        FROM myneect.old_users 
                        WHERE (username='{$username}' OR email_address='{$username}');";
                                            
                    $result = pg_query($conn, $query);
                    
                    

                    if (!$result) {
                        pg_close($conn);

                        error_log("MIGRATE: Could not retrieve user data.");
                        $message2 = "Ocorreu um erro no acesso à base de dados.";
                        return;
                    }

                    $row = pg_fetch_row($result);


                    if(!empty($row)) {
                        // get userid, salt, and hashed password
                        $userid = $row[0];                        
                        $salt = $row[1];
                        $hashedpw = $row[2];
                        $migrated = $row[3];

                        if($migrated != 0) {
                            pg_close($conn);

                            $message2 = "A tua conta já foi migrada anteriormente.";
                            return;
                        }

                        // recreate password
                        
                        $password2 = $salt . $password;
                        $password2 = sha1($password2);
                        
                        if( strcmp ($password2 , $hashedpw) == 0 ) {
                            $query = "UPDATE myneect.forum_users
                                SET user_password = MD5('{$password}')
                                WHERE (username = '{$username}' 
                                OR user_email = '{$username}');";
                                            
                            $result = pg_query($conn, $query);

                            $query = "UPDATE myneect.old_users
                                SET migrated = 1
                                WHERE id={$userid};";
                                
                                            
                            pg_query($conn, $query);

                            $message1 = "Sucesso!";
                            $message2 = 'A tua conta foi migrada. Podes iniciar sessão!<br />
                                <strong>Recomendamos que alteres a tua palavra-passe no <a href="/forum/ucp.php?i=ucp_profile&mode=reg_details">painel de controlo de utilizador</a>.</strong>';
                            $url = "/forum";
                            $action = "Ir para o fórum";
                            $icon = "ok";

                            pg_close($conn);
                        } else {
                            pg_close($conn);

                            error_log("MIGRATE: Bad password.");
                            $message2 = "A palavra-passe está incorreta<br />Se te esqueceste da palavra-passe, entra em contacto connosco.";
                            return;
                        }





                    } else {
                        pg_close($conn);
                        error_log("MIGRATE: User not found.");
                        $message2 = "Utilizador não encontrado. <br /><strong>Nota:</strong> Este campo é case-sensitive.";
                        return;
                    }
                    
                }
                else {
                    //$errMsg = 'Robot verification failed, please try again.';
                    $message2 = "Falhou a verificação anti-spam";
                }
            } 
            

        }

    }
        
        
    if(isset($_POST['username']) && isset($_POST['password'])) {
        
        //echo '<meta http-equiv="refresh" content="0; url=/" />';

        $username = $_POST['username'];
        $password = $_POST['password'];
        process_request($username, $password, $message1, $message2, $icon, $url, $action);

        //echo $message1;
        //echo $message2;


    }
?>
    
    


<div class="well">
  <p class="h1" align="center" style="color: #2f84b8;"><span class="glyphicon glyphicon-<?php echo $icon; ?>" aria-hidden="true"></span></p>
  <h1 class="h2" align="center"><?php echo $message1; ?></h1>
  <p align="center"><?php echo $message2; ?></p>
  <br />

  <p><a href="<?php echo $url; ?>" class="btn btn-lg btn-primary btn-block"><?php echo $action; ?></a></p>
</div>

<?php include 'footer.php'; ?>
