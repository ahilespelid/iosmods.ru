<?php namespace SiteIosmods; session_start(['cookie_lifetime' => 3660,]);
if(!function_exists('pa')){function pa($a=[],$br=0,$tag='pre'):bool{$tr = debug_backtrace(); $fi = ''; $sbr='';
    while($br){$sbr.='<br>';$br--;} 
    echo $sbr.(!empty($tr[0]) && is_array($tr[0]) ? $fi = $tr[0]['file'].':'.$tr[0]['line'] : '').'<br><'.$tag.'>'; print_r($a); echo'</'.$tag.'>';return true;
}}
if(!function_exists('str_starts_with')){function str_starts_with($str, $start){return (@substr_compare($str, $start, 0, strlen($start))==0);}}
if(!function_exists('str_ends_with')){function str_ends_with($str, $end){return (@substr_compare($str, $end, -strlen($end))==0);}}
///*/ if(extension_loaded('pdo_mysql')){} ///*/

if (!function_exists('handleException')){
function handleException($e){///*/  Запись в лог исключений $class->writeLog($e); ///* /  
    pa($e); ///* / Показ вида при выявленных исключениях $class->render('exception.php'); ///* /
}}
set_exception_handler('\SiteIosmods\handleException');

$class = new \SiteIosmods\iosMods;
$class->index();

class iosMods{
    protected object $user, $bd;
    public    string $token, $email;
    private   string $pass, $host, $bdName, $bdUser, $bdPass;
    
    public function __construct(){
        $this->host                                 = 'localhost'; 
        $this->bdUser                               = 'root'; 
        $this->bdPass                               = ''; 
        $this->bdName                               = 'iosmods'; 
              
        try{
            $this->bd = $this->getModel($this->host, $this->bdUser, $this->bdPass, $this->bdName);            
        }catch(\Exception $e){exit('No coonection bd!');}
        
        if(empty($_SESSION['user'])){
            $this->email                            = ''; 
            $this->pass                             = ''; 
            $this->token                            = '';
        }else{$this->setUser($_SESSION['user']);}

    }
    public function index($data = []){         
        $stSubNum = (str_starts_with($_SERVER['REQUEST_URI'], '/')) ? 1 : 0;
        $subCount = (str_ends_with($_SERVER['REQUEST_URI'], '/')) ? strlen($_SERVER['REQUEST_URI']) - 2 : strlen($_SERVER['REQUEST_URI']);       
        $route = (0 < $subCount) ? substr($_SERVER['REQUEST_URI'], $stSubNum, $subCount) : '';
        if('logout' == $route){$this->Out();}
        $email = (!empty($_POST['email'])) ? ($_POST['email']) : ((!empty($_SESSION['user']['email'])) ? $_SESSION['user']['email'] : ''); 
        $pass = (!empty($_POST['password'])) ? ($_POST['password']) : ((!empty($_SESSION['user']['password'])) ? $_SESSION['user']['password'] : ''); 
        $act = (!empty($_REQUEST['act'])) ? ($_REQUEST['act']) : '';        
        if(!empty($email) && !empty($pass) && !empty($act)){
            if('login' == $act){
                if($user = $this->bd->query($sql = "SELECT * FROM `users` WHERE `email`='$email' AND `pass`='$pass';")->fetch_assoc()){
                    $this->setUser($user); echo json_encode(['redirect'=>'/site']); exit;
            }else{echo json_encode(['error' => 'Такой логин|пароль - '.$email.'|'.$pass.' не найден.']); exit;}}

            if('registr' == $act){
                if($user = $this->bd->query($sql = "SELECT * FROM `users` WHERE `email`='$email';")->fetch_assoc()){echo json_encode(['error' => 'Этот логин - '.$user['email'].' уже зарегистрирован.']); exit;}
                $this->bd->query($sql = "INSERT INTO users (ip,email,pass,agent,token,ctime) VALUES ('$_SERVER[REMOTE_ADDR]','$email','$pass','$_SERVER[HTTP_USER_AGENT]',NULL,NOW()) ON DUPLICATE KEY UPDATE `dip` = '$_SERVER[REMOTE_ADDR]', `dagent`='$_SERVER[HTTP_USER_AGENT]',`dtime`= NOW();");
                $this->setUser(['id'=>$this->bd->insert_id, 'email'=>$email, 'pass'=>$pass]); echo json_encode(['redirect'=>'/site']); exit;
            }exit;
        }
        ///*/ pa($route); ///*/
        $page = ((is_string($route) && !empty($route)) ? $route : 'home').'.php';      
        $this->render($page,['email' => $this->email]);
    }
    public function newToken($user = []):?string{
    if(is_array($user) && !empty($user['id']) && !empty($user['email']) && !empty($user['pass'])){
        $token = hash('sha3-512', $user['id'].$user['email'].$user['pass']);
        return (!empty($token)) ? $token : null;
    }return null;}

    public function Auth($user = []):bool{
    if(is_array($user) && !empty($user['id']) && !empty($user['email']) && !empty($user['pass']) && !empty($user['token'])){
        $_SESSION['user'] = $user; return ($this->isAuth()) ? true : false;
    }else{$_SESSION['user'] = false; return false;}}

    public function isAuth():bool{return (!empty($_SESSION['user']) && is_array($_SESSION['user'])) ? true : false;}
    public function getToken():?string{return (($this->isAuth() && !empty($_SESSION['user']['token'])) ?  $_SESSION['user']['token'] : null);}
    public function getUser():?array{return (($this->isAuth()) ?  $_SESSION['user'] : null);}
    public function setUser(array $user = []):?array{
    if($user['token'] = $this->newToken($user)){
        if($this->bd->query($sql = "UPDATE users SET `token`='$user[token]' WHERE `id`='$user[id]';")){
        $this->email = $user['email']; $this->pass = $user['pass']; $this->token = $user['token']; 
        $_SESSION['user'] = $user;       
        return $user;               
    }}return null;}
    public function Out():bool{$_SESSION = []; session_destroy();header('Location: /'); return true;}
    
    public function getModel(string $host, string $user, string $pass, string $name):?object{
        try{if($link = new \MySQLi($host, $user, $pass, $name)){$link->query("SET NAMES utf8");}}catch(\Exception $e){}
    return (0 != $link->connect_errno) ? null : $link;}
    
    public function render(string $path, array $parameters = []):?array{
        if(empty($path)){return null;}
        if(is_readable($view = realpath($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR.(str_replace(['\\','/'], DIRECTORY_SEPARATOR, $path))))){
            extract($parameters, EXTR_SKIP); require $view;
        }else{throw new \Exception("Файл на вид по пути '$view' не найден.");}
    return $parameters;}
    public function __destruct(){$this->bd->close(); unset($this->bd);}
}?>