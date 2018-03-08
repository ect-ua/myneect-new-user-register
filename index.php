<?php 

include 'header.php';
include 'common.php';
include '../forum/config.php';

$token = $_GET['token'];

$full_name = "desconhecido";
$message = "O registo no myNEECT requer um token de ativação válido.";

$can_create_account = false;


$conn_string = "host=" . $dbhost . " port=DB_PORT" . " dbname=DB_NAME" . 
" user=" . $dbuser . " password=" .$dbpasswd;

$conn = pg_connect($conn_string);

if (!$conn) {
  $message = "Não foi possível ligar à base de dados.";
  error_log("CREATE: Could not connect to database.");
  unset($dbpasswd);
  return;
}

unset($dbpasswd);

$token = pg_escape_string(utf8_encode($token));

$query = "SELECT id, full_name, mail, token_used 
FROM myneect.new_users 
WHERE token='{$token}';";
                    
$result = pg_query($conn, $query);

if (!$result) {
  pg_close($conn);

  error_log("CREATE: Could not retrieve user token.");
  $message = "Ocorreu um erro no acesso à base de dados.";
  return;
}

$row = pg_fetch_row($result);

if(!empty($row)) {
  // get userid, salt, and hashed password
  $userid = $row[0];                        
  $full_name = ucwords(strtolower($row[1]));
  $mail = $row[2];
  $migrated = $row[4];

  if($migrated) {
      pg_close($conn);

      $message = "Esta conta já foi ativada. Podes iniciar sessão.";
  } else {
    $can_create_account = true;
    $message = "Insere um nome de utilizador e palavra-passe para criar a tua conta.";
  }

}

                    

?>

<script>
       function onSubmit(token) {
         document.getElementById("form-update").submit();
       }
</script>

<div class="col-lg-6">
<form id="form-update" method="post" action="register.php">
  <h2>Olá, <?php echo $full_name; ?>!</h2>
  <p class="lead"><?php echo $message; ?></p>

  <input type="hidden" name="token" value="<?php echo $token; ?>">
  <br />

  <?php 
    if($can_create_account) { ?>
  <div class="input-group">
    <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
    <input id="username" type="text" class="form-control" name="username" value="" placeholder="Nome de utilizador" required>                                        
  </div>

  <div class="input-group">
    <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
    <input id="password" type="password" class="form-control" name="password" placeholder="Palavra-passe" required>
  </div>
  
  <br />
  <p align="center"><button class="btn btn-lg btn-primary g-recaptcha" data-sitekey="6Lem2x8UAAAAAO8LtKMNbDDMLXHSWfETHTGN8cKt" data-callback="onSubmit">Criar a minha conta</button></p>

    <?php } ?>
  <p align="center"><a href="/forum" class="btn btn-lg btn-default">Continuar para o fórum</a></p>
</form>
    </div>

    <div class="col-lg-6">
      <h3>O que é o myNEECT?</h3>
      <p>O myNEECT é uma plataforma exclusiva dos alunos 
        de Engenharia de Computadores e Telemática da Universidade de Aveiro.</p>
      <p>No myNEECT tens acesso a material de apoio às disciplinas,
        podes tirar dúvidas com colegas, conversar sobre qualquer assunto e
        experienciar em completo a comunidade ECT.</p>

      <h3>Queres contribuir para o myNEECT?</h3>
      <p>Boa! Não precisas de saber nenhuma linguagem de programação em específico.
         Esta é uma plataforma de CTs para CTs e é um espaço que precisa da colaboração de todos.
         No fórum, encontrarás uma secção dedicada a colaboradores do myNEECT, por isso regista-te já!
      </p>
      
      <h3>Problemas em criar conta?</h3>
      <p>Estamos disponíveis para te auxiliar no processo de criação de conta.
        Entra em contacto connosco através do e-mail neect[@]aauav.pt.
      </p>

      
      

    </div>
<?php include 'footer.php' ?>