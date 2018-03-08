<?php

include 'header.php';

define('FORUM_ADD', true);
define('IN_PHPBB', true);
define('IN_PORTAL', true);
define('PHPBB_ROOT_PATH', '../forum/');
define('USERS_TABLE', "forum_users");
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . '/includes/functions_user.' . $phpEx);

#$request->enable_super_globals();

$username = pg_escape_string(utf8_encode(request_var('username','')));
$password = pg_escape_string(utf8_encode(request_var('password','')));
$token = pg_escape_string(utf8_encode(request_var('token','')));
$ano_matricula = date("Y");

$recaptcha = request_var('g-recaptcha-response','');

if($recaptcha == '') {
    echo '<meta http-equiv="refresh" content="0; url=/register/" />';
    return;
}
//secret key
$secret = 'GOOGLE_RECAPTCHA_SECRET';
//get verify response data

// Google API
$api = 'https://www.google.com/recaptcha/api/siteverify?secret='.$secret.'&response='.$recaptcha;
$verifyResponse = file_get_contents($api);
$responseData = json_decode($verifyResponse);

if($responseData->success){
    continue_registration($username, $password, $token, $ano_matricula);
} else {
    echo '<meta http-equiv="refresh" content="0; url=/register/" />';
}


function continue_registration($username, $password, $token, $ano_matricula) {
    include '../forum/config.php';

    if($username == "" || $password == "" || $token == "") {
        echo '<meta http-equiv="refresh" content="0; url=/register/" />';
        return;
    }

    
    # get user data from token
    $conn_string = "host=" . $dbhost . " port=5432" . " dbname=myneect" . 
    " user=" . $dbuser . " password=" .$dbpasswd;
    
    $conn = pg_connect($conn_string);
    
    if (!$conn) {
      echo '<p class="lead">Não foi possível ligar à base de dados.</p>';
      error_log("CREATE: Could not connect to database.");
      unset($dbpasswd);
      return;
    }
    
    unset($dbpasswd);
    
    $query = "SELECT id, full_name, mail, token_used 
    FROM myneect.new_users 
    WHERE token='{$token}';";
                        
    $result = pg_query($conn, $query);
    
    if (!$result) {
      pg_close($conn);
    
      error_log("CREATE: Could not retrieve user token.");
      echo '<p class="lead">Ocorreu um erro no acesso à base de dados.</p>';
      return;
    }
    
    $row = pg_fetch_row($result);
    
    if(!empty($row)) {
      // get userid, salt, and hashed password
      $userid = $row[0];                        
      $full_name = ucwords(strtolower($row[1]));
      $mail = $row[2];
      $migrated = $row[3];
    
      if($migrated != 0) {
          pg_close($conn);
    
          echo '<p class="lead">Esta conta já foi ativada. Podes iniciar sessão.</p>';
          return;
      } else {
        $can_create_account = true;
        echo '<p class="lead">Insere o teu nome de utilizador e palavra-passe para criar a tua conta.</p>';
      }
    
    }

    # register user in phpBB
    $group_id = 2;
    $timezone = '0';
    $language = 'pt';
    $user_type = USER_NORMAL;
    $registration_time = time();


    $error = validate_username($username);
    if ($error)
    {
        echo '<h2>Ocorreu um erro.</h2>';
       echo '<p class="lead">Nome de utilizador em uso ou inválido.</p>';
       echo '<a href="/register/?token=' . $token . '" class="btn btn-lg btn-default">Tentar novamente</a>';
       pg_close($conn);
       return;
    }

    $user_row = array(
        'username'              => $username,
        'user_password'         => phpbb_hash($password),
        'user_email'            => $mail,
        'group_id'              => (int) $group_id,
        'user_timezone'         => (float) $timezone,
        'user_lang'             => $language,
        'user_type'             => $user_type,
        'user_regdate'          => $registration_time
    );

    $user_id = user_add($user_row);

    #if($user_id) {

    #}

    # add user custom profile fields
    $query = "INSERT INTO forum_profile_fields_data(user_id, pf_nome, pf_ano_matricula, pf_full_name) VALUES "
        . "({$user_id}, '{$full_name}', '{$ano_matricula}', '{$full_name}');";
    
    
    $result = pg_query($conn, $query);
    
    if (!$result) {
        pg_close($conn);
    
        error_log("CREATE: Could not retrieve user token.");
        echo '<p class="lead">Ocorreu um erro no acesso à base de dados.</p>';
        return;
    }


    # add user to group
    $query = "INSERT INTO forum_user_group(group_id, user_id, group_leader, user_pending) VALUES "
    . "(2, {$user_id}, 0, 0);";


    $result = pg_query($conn, $query);

    if (!$result) {
        pg_close($conn);

        error_log("CREATE: Could not retrieve user token.");
        echo '<p class="lead">Ocorreu um erro no acesso à base de dados.</p>';
        return;
    }

    $query = "UPDATE myneect.new_users
    SET token_used = true
    WHERE id={$userid};";
    
                
    pg_query($conn, $query);

    pg_close($conn);

    echo '<h2>Registo concluído com sucesso!</h2>';
    echo '<p class="lead">Bem-vindo ao myNEECT, ' . $username . '!</p>';
    echo '<p>Estamos a redirecionar-te para a página inicial do fórum.</p>';
    echo '<meta http-equiv="refresh" content="5; url=/forum/" />';

        /*$query = "SELECT id, full_name, mail, token_used 
    FROM myneect.new_users 
    WHERE token='{$token}';";
                        
    $result = pg_query($conn, $query);
    
    if (!$result) {
      pg_close($conn);
    
      error_log("CREATE: Could not retrieve user token.");
      $message = "Ocorreu um erro no acesso à base de dados.";
      return;
    }*/

    #echo $user_id;

} 