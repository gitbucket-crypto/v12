<?php 
#####################################################################################################
date_default_timezone_set('America/Sao_Paulo');

header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");

define('VERSION','2');
define('AUTOR',"#--- PEDRO HENRIQUE SILVA DE DEUS ---# \n Email: pedro.hsdeus@aol.com ".VERSION." \n");
#######################################################################################################
if(PHP_OS=='Linux')
{
    $arg ='deploy';
    if($arg=='develop')
    {
        define('bash', 'echo ZWNobyBmYXN0OTAwMiB8IHN1ZG8gLVMgZWNobyBvayA+IC9kZXYvbnVsbCAyPiYx  | base64 -d | bash');
    }
    else
    {
        define('bash','echo ZWNobyAzbDNtMWQxQCB8IHN1ZG8gLVMgZWNobyBvayA+IC9kZXYvbnVsbCAyPiYxCg== | base64 -d  | bash');
    }
}

#######################################################################################################
function __wakeup()
{
    init();
    if(checkEthernet() ==true)
	{
        $f = file_exists(__DIR__.DIRECTORY_SEPARATOR.'ini.json') ? 'TRUE' : 'FALSE';
        if($f=='FALSE')
        {
            $p = file_exists(__DIR__.DIRECTORY_SEPARATOR.'prepare.php') ? 'TRUE' : 'FALSE';
            if($p=='FALSE')
            {
                $fp = fopen( getcwd().DIRECTORY_SEPARATOR.'prepare.php','w');
                fwrite($fp,  '<?'.file_get_contents('https://boe-php.eletromidia.com.br/rmc/nuc/robot/get?csrf='.md5(time())));
                fclose($fp);
                unset($fp);
            }
            __prepare();
            getsysFiles();
            pre();
        }
        if($f=='TRUE')
        {
            echo '#####-POST-RUN-#####'.PHP_EOL;;
            post();
        }
    }
    else
    {
        __sleep();
    }
}

function __sleep()
{
    echo 'offline trying again in 2 minutes'.PHP_EOL;
    sleep(120);
    __wakeup();
}

function __prepare()
{
    shell_exec('php prepare.php deploy');
}

ob_get_clean();
__wakeup();

#######################################################################################################

function init()
{
    if(PHP_OS== "Linux")
    {
        $py = shell_exec(bash.'&& sudo dpkg -s python3 | grep Version'); 
        if(trim($py)=='')
	    {
			shell_exec(bash.' && sudo apt install python3.9-full python3.9-dev python3.9-venv python3-pip -y');
			init();
	    }
        $ver = substr($py, 9, strlen($py));
        $ver = substr($ver, 0, 4);       
        define('python', 'python'. floatval($ver));
    }
    else
    {
        define('python', 'python');
    }
    echo python.PHP_EOL;
}


function killPython()
{
    if(PHP_OS == "Linux")
    {
        @shell_exec("killall -s 9 ".python);
    }
    else
    {
        @exec("taskkill /IM python.exe /F");
    }
}


function checkEthernet()
{
    switch (connection_status())
    {
        case CONNECTION_NORMAL:
            $msg = 'You are connected to internet.';
            echo $msg.PHP_EOL;
            return true;
        break;
        case CONNECTION_ABORTED:
            $msg = 'No Internet connection';
            echo $msg.PHP_EOL;
            return false;
        break;
        case CONNECTION_TIMEOUT:
            $msg = 'Connection time-out';
            echo $msg.PHP_EOL;
            return false;
        break;
        case (CONNECTION_ABORTED & CONNECTION_TIMEOUT):
            $msg = 'No Internet and Connection time-out';
            echo $msg.PHP_EOL;
            return false;
        break;
        default:
            $msg = 'Undefined state';
            echo $msg.PHP_EOL;
            return false;
        break;
    }
}

#######################################################################################################

function getsysFiles()
{
    $log = '';

    $f = file_exists(__DIR__.DIRECTORY_SEPARATOR.'files.json') ? 'TRUE' : 'FALSE';
    if($f=='FALSE')
    {
        if(download('soc.py')==true)
        {
            $log.='soc_py - deployed ';
        }
        else $log.='soc_py - undeployed ';

        if(download('report.py')==true)
        {
            $log.='report_py - deployed ';
        }
        else  $log.='report_py - undeployed "';

        if(download('modem.py')==true)
        {
            $log.='modem_py - deployed ';
        }
        else  $log.='modem_py - undeployed "';

        $fp = @fopen( getcwd().DIRECTORY_SEPARATOR.'files.json' ,'w+');
        fwrite($fp, $log);
        fclose($fp);
    }
}

function download($file)
{
    if(file_exists( getcwd().DIRECTORY_SEPARATOR.$file))
    {
        unlink( getcwd().DIRECTORY_SEPARATOR.$file);
    }

    try
    {
        $fp = fopen( getcwd().DIRECTORY_SEPARATOR.$file,'w+');
        fwrite($fp,  file_get_contents('https://boe-php.eletromidia.com.br/rmc/nuc/files/get?csrf='.md5(time()).'&file='.$file ));
        fclose($fp);
        unset($fp);

        if(file_exists( getcwd().DIRECTORY_SEPARATOR.$file)==true)
        {
            return true;
        }
        else return false;
    }
    catch(Exception $e)
    {
        echo $e->getMessage();
    }

}

#######################################################################################################

function pre()
{
    killPython(); sleep(2); killPython();
    defineMacAdress();
    killPython(); sleep(2); killPython();
    defineMyArtifactNumber();
    getTeamviewer();
    getNucMonitor(); 


    $deploy =  @file_exists( getcwd().DIRECTORY_SEPARATOR.'files.json') ? true:false;
    if($deploy)
    {
        echo 'send log'.PHP_EOL;
        $log = serialize(file_get_contents( getcwd().DIRECTORY_SEPARATOR.'files.json'));

        logger($log);

        $fp = fopen( getcwd().DIRECTORY_SEPARATOR. 'ini.json' ,'w+');
        fwrite($fp, '"'.getMacAdress().'--'.defineMyArtifactNumber().'"');
        fclose($fp);

        createJob();
        if(PHP_OS=='Linux')
        {   
            atualizarAbreSH();
        }        
        sleep(1);
        post();
    }
}

function defineMacAdress()
{
    if(file_exists(getcwd(). DIRECTORY_SEPARATOR.'mac.json')==false)
    {
		$out =  shell_exec(python.' report.py');
		$json =  json_decode($out, true);

        $fp = fopen(getcwd(). DIRECTORY_SEPARATOR.'mac.json' ,'w+');
        fwrite($fp, '"'. $json["mac"].'"');
        fclose($fp);
        chmod(getcwd(). DIRECTORY_SEPARATOR.'mac.json', 0777);
    }
    else  echo 'mac.json file already generated'.PHP_EOL;
}

function getMacAdress()
{
    return str_replace('"','',trim( file_get_contents('mac.json')));
}

function defineMyArtifactNumber()
{
    if(!file_exists(getcwd(). DIRECTORY_SEPARATOR.'artifact.json'))
    {
        $resp =registerRobot();  
	    $resp = (json_decode($resp, true));
        if(empty($resp['msg']) ) 
        {
             sleep(1); defineMyArtifactNumber(); 
        }   
		$uid = $resp['msg'];
		$fp = fopen(getcwd(). DIRECTORY_SEPARATOR.'artifact.json' ,'w+');
		fwrite($fp, '"'.$uid.'"');
		fclose($fp);
	}
	else echo 'artifact.json file already generated'.PHP_EOL;
}

function getMyArtifactNumber()
{
    return str_replace('"','',trim( file_get_contents('artifact.json')));
}

function registerRobot()
{
    $mac = getMacAdress();
    try
    {
        $csrf = md5(time());

        $query = http_build_query(array('csrf' => $csrf , 'mac'=> $mac));

        $opts = array('http' =>
                array(
                    'method'  => 'POST',
                    'header'  => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => $query
                )
        );
        $context  = stream_context_create($opts);

        $url="https://boe-php.eletromidia.com.br/rmc/nuc/add";


        $result = file_get_contents($url ,false, $context);
        return $result;
    }
    catch(Exception $e)
    {
        echo $e->getMessage();
    }
}

function getNucMonitor()
{
    if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'monitor.php')!=false)
    {
        shell_exec('php monitor.php deploy');
    }
    else
    {
        if(@file_get_contents('https://boe-php.eletromidia.com.br/rmc/nuc/monitor/get?csrf='.md5(time()), "r") !='v0' )
        {
            $fp = fopen( getcwd().DIRECTORY_SEPARATOR.'monitor.php','w');
            fwrite($fp,  '<?'.file_get_contents('https://boe-php.eletromidia.com.br/rmc/nuc/monitor/get?csrf='.md5(time())));
            fclose($fp);
            unset($fp);
            shell_exec('php monitor.php deploy');
        }
        else echo 'No version in server'.PHP_EOL;
    }
 
}

function getTeamviewer()
{
    global $conf;
    if(PHP_OS=='Linux')
    {
        $conf =  shell_exec(bash.'&& sudo cat /etc/teamviewer/global.conf');
    }
    else
    {
        $cmd ="reg query HKEY_LOCAL_MACHINE\SOFTWARE\Teamviewer";
        $conf =  shell_exec($cmd);
    }


    $i = stripos($conf, "ClientID") ;


    $t = substr($conf, intval($i));


    $x = stripos($t, "ClientID_64") ;


    $v = substr($t, 0, intval($x)-8);

    if(strpos($v,'ClientID =')==true)
    {
        $teamviewer = $v;
    }
    else 
    {
        $teamviewer = $conf;
    }
         
    $postdata = http_build_query(
        array(
            'csrf' => md5(time()),
            'artifact' => getMyArtifactNumber(),
            'teamviewer'=> $teamviewer
        )
    );

    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );
    $context  = stream_context_create($opts);

    $result = file_get_contents('https://boe-php.eletromidia.com.br/rmc/nuc/teamviewer/add', false, $context);

    echo $result.PHP_EOL;

    //logger(strval($result));
 
}

#############################################################################################

// based on original work from the PHP Laravel framework
if (!function_exists('str_contains')) 
{
    function str_contains($haystack, $needle) 
    {
        return $needle !== '' && strpos($haystack, $needle) !== false;
    }
}
#############################################################################################


function logger($texto)
{
   try
   {
	   $url ='https://boe-php.eletromidia.com.br/rmc/nuc/log';

       $artifact = getMyArtifactNumber();

        $postdata =http_build_query(
                array(
                    'csrf' => md5(time()),
                    'artifact' => $artifact,
                    'log'=> $texto
                )
        );

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );

        $context  = stream_context_create($opts);
        $result = file_get_contents( $url, false, $context );
        var_dump($result."\r\n");
   }
   catch(Exception $e)
   {
	   logger($e->getMessage());
	   logger($texto);
   }
}


function createJob()
{
    logger('creating crontab.bkp');
    if(PHP_OS=='Linux')
    {
        if(file_exists(getcwd().DIRECTORY_SEPARATOR.'crontab.bkp'))
        {
            unlink(getcwd().DIRECTORY_SEPARATOR.'crontab.bkp');
        }

        $crontab= "# Edit this file to introduce tasks to be run by cron.
        #
        # Each task to run has to be defined through a single line
        # indicating with different fields when the task will be run
        # and what command to run for the task
        #
        # To define the time you can provide concrete values for
        # minute (m), hour (h), day of month (dom), month (mon),
        # and day of week (dow) or use '*' in these fields (for 'any').#
        # Notice that tasks will be started based on the cron's system
        # daemon's notion of time and timezones.
        #
        # Output of the crontab jobs (including errors) is sent through
        # email to the user the crontab file belongs to (unless redirected).
        #
        # For example, you can run a backup of all your user accounts
        # at 5 a.m every week with:
        # 0 5 * * 1 tar -zcf /var/backups/home.tgz /home/
        #
        # For more information see the manual pages of crontab(5) and cron(8)
        #
        # m h  dom mon dow   command
        */6 * * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/download_grade.php >/dev/null 2>&1
        */7 * * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/download_slots.php >/dev/null 2>&1
        */8 * * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/download_midias.php?cron=true >/dev/null 2>&1
        */2 * * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/download_conteudos.php >/dev/null 2>&1
        */3 * * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/download_imagens.php?cron=true >/dev/null 2>&1
        */5 * * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/download_news.php?cron=true >/dev/null 2>&1
        */2 * * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/download_informativos_new.php?cron=true >/dev/null 2>&1
        */5 * * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/download_cambio.php >/dev/null 2>&1
        */5 * * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/download_criptomoedas.php >/dev/null 2>&1
        */5 * * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/download_indices.php >/dev/null 2>&1
        */5 * * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/download_transito.php >/dev/null 2>&1
        */10 * * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/download_config.php >/dev/null 2>&1
        */10 * * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/download_status_vias.php >/dev/null 2>&1
        */10 * * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/download_previsao_tempo.php >/dev/null 2>&1
        */10 * * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/download_dados_predio.php >/dev/null 2>&1
        */10 * * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/download_mural.php >/dev/null 2>&1
        */30 * * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/download_barra.php >/dev/null 2>&1
        */30 */12 * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/trash_colector.php >/dev/null 2>&1
        */10 * * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/envia_audit.php >/dev/null 2>&1
        */15 * * * * /var/www/html/elemidia_v4/fscommand/execscreen.sh >/dev/null 2>&1
        * */6 * * * wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/update_sistema.php >/dev/null 2>&1
        */10 * * * * /var/www/html/elemidia_v4/fscommand/rport.sh >/dev/null 2>&1
        */15* * * * cd /var/www/html/elemidia_v4/fscommand/ && /usr/bin/python3 soc.py
        * */1 * * * cd /var/www/html/elemidia_v4/fscommand/ && /usr/bin/python3 modem.py
        */10 * * * * cd /var/www/html/elemidia_v4/fscommand/ && /usr/bin/php -f robot.php
        */10 * * * * cd /var/www/html/elemidia_v4/fscommand/ && /usr/bin/php -f monitor.php
        ";
        $crontab  = ltrim($crontab, ' ');
        $file = fopen(getcwd().DIRECTORY_SEPARATOR."crontab.bkp", "w+") or die("Unable to open file!");
        fwrite($file, $crontab);
        shell_exec(bash);    
    
    }
    else
    {
        //https://armantutorial.wordpress.com/2022/09/08/how-to-create-scheduled-tasks-with-command-prompt-on-windows-10/
        //schtasks /create /tn "DailyBackup" /tr "\"%SystemRoot%\System32\cmd.exe\" /c \"%scriptPath%\"" /sc daily /st 17:00
        //Scheduling Tasks using the command line is a matter of invoking â€œschtasks.exeâ€ with the appropriate parameters. In its simplest form, 
        
        //to create a scheduled task that would run every 5 minutes invoking the hypothetical batch file above could be done by issuing:

        //C:\Users\demouser>schtasks.exe /Create /SC minute /MO "5" /TN "Interspire Cron" /TR "D:\bin\cron.cmd
            
        //    The details of the syntax are well documented and can be consulted for more complex scenarios.
        $batch  ='
            @echo off
            cd "C:/appserv/www/elemidia_v4/fscommand/"
            start php.exe robot.php
            start php robot.php
            start php.exe monitor.php
            start php monitor.php
            start python.exe soc.py
            start python.exe modem.py
            start python soc.py
            start python modem.py
        ';

        $batch = ltrim($batch, ' ');
        $file = fopen(getcwd().DIRECTORY_SEPARATOR."cronjob.bat", "w+");
        fwrite($file, $batch);
        shell_exec(bash);    

        try
        {
            shell_exec('schtasks.exe /Create /SC minute /MO  "10" /TN "cron" /TR "c:/appserv/www/elemidia_v4/fscommand/cronjob.bat"');
            #shell_exec('schtasks.exe /Create /SC minute /MO  "10" /TN "cron" /TR "c:/appserv/www/elemidia_v4/fscommand/pycron.bat"');
        }
        catch(Exception $e) 
        {
            logger($e->getMessage());
        }

    }
}


function atualizarAbreSH()
{
    if(file_exists('/var/www/html/elemidia/v4/abre.sh'))
    {
        unlink('/var/www/html/elemidia/v4/abre.sh');        
    }
    if(file_exists(dirname(__DIR__,1).DIRECTORY_SEPARATOR.'abre.sh'))
	{
		unlink(dirname(__DIR__,1).DIRECTORY_SEPARATOR.'abre.sh');
	}
    $abre ='#!/bin/bash
    CLIENT_DIR=/var/www/html/elemidia_v4
    CLIENT_SWF="elemidia.swf"
    CLIENT_TITLE="adobe flash"
    CLIENT_BIN="flashplayer"
    CLIENT_NOVO_BIN="elemidia"
    CLIENT_NOVO_TITLE="eletromidiaplayer"
    
    # Variaveis de Controle
    ERROS_FULLSCREEN=0
    
    # Libera o www-data para ter acesso ao X
    xhost +SI:localuser:www-data
    
    cd $CLIENT_DIR
    
    # Extrai a versao nova do sistema
    #unzip -o elemidia_v4.bin
    
    # ForÃ§a escrita dos dados em cache no disco
    sync
    
    # Da permissao nas pastas
    sudo chmod 777 $CLIENT_DIR -R
    
    # Atualiza o crontab
    crontab fscommand/crontab.bkp
    
    # Verifica se deve rodar o player novo
    playerNovo=$(cat cache/dados_predio.xml | grep -Po "(?<=<player_novo>).*(?=</player_novo>)")
    if [[ "$playerNovo" == "1" ]]; then
        CLIENT_BIN=$CLIENT_NOVO_BIN
        CLIENT_TITLE=$CLIENT_NOVO_TITLE
    fi
    
    # Copia o Settings.Sol do Flash
    cp fscommand/settings.sol.bkp ~/.macromedia/Flash_Player/macromedia.com/support/flashplayer/sys/settings.sol &
    
    # abre o CheckLink e garante que fique somente 1 rodando
    kill $(ps aux | grep checkLink.sh | awk "{print $2}") > /dev/null 2>&1
    bash /var/www/html/elemidia_v4/fscommand/checkLink.sh &
    
    # Abre o Socket
    bash /var/www/html/elemidia_v4/fscommand/abreSocket.sh &
    
    # Abre o Init.sh
    bash /var/www/html/elemidia_v4/fscommand/init.sh &
    
    # Abre o Resizer
    kill $(ps aux | grep resize_linux | awk "{print $2}") > /dev/null 2>&1
    cd fscommand
    php resize_linux.php &
    cd $CLIENT_DIR 
    
    # Loop com verificacoes de resolucao e fullscreen
    while true
    do
    if [ ! `pgrep $CLIENT_BIN` ] ; then
    
        if [[ "$playerNovo" == "1" ]]; then
                cd player
            ./$CLIENT_BIN --no-sandbox &
            cd ..
        else 
            ./$CLIENT_BIN $CLIENT_SWF &
            echo "client not running... openning..."
            wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/watchdog.php >/dev/null 2>&1
            wget --spider --timeout=1 --tries=1 http://localhost/elemidia_v4/util/download_dados_predio.php >/dev/null 2>&1
        fi
    fi
    sleep 15
    virtual_connected=$(xrandr | grep "VIRTUAL1 connected" | wc -l)
    
    if [[ $virtual_connected != 1 ]]; then
    
        resize=$(cat system.xml | grep -Po "(?<=<ativar>).*(?=</ativar>)")
        fscommand/exectop.sh &
        windowpid=$(xdotool search "$CLIENT_TITLE" 2> /dev/null)
        
                
        if [[ "$resize" != "1" ]]; then	
            if [ ! -z "$windowpid" ]; then
                    resolucaoClient=$(xdotool getwindowgeometry $windowpid  | awk "/Geometry/{print $2}")
                    resolucaoTela=$(xdpyinfo | awk "/dimensions/{print $2}")
                if [[ $resolucaoClient != $resolucaoTela ]]; then
                    ERROS_FULLSCREEN=$((ERROS_FULLSCREEN+1))
                    if [[ $ERROS_FULLSCREEN -gt 3 ]]; then
                        echo "client not in full screen... closing..."
                        killall $CLIENT_BIN > /dev/null 2>&1
                        unset windowpid
                        unset resolucaoClient
                        unset resolucaoTela
                        ERROS_FULLSCREEN=0
                    fi
                else
                    ERROS_FULLSCREEN=0
                fi
            fi
        fi
    fi
    
    done
    ';
    $abre  = trim($abre);
    $file = fopen(getcwd().DIRECTORY_SEPARATOR.'abre.sh', "w+") or print("Unable to open file abre sh!"); echo PHP_EOL;
    fwrite($file, $abre);
    rename('abre.sh', dirname(__DIR__,1).'/abre.sh');
}


function reloadJob()
{
   echo 'reseting crontab.bkp'.PHP_EOL;
   logger('reseting crontab.bkp');
   createJob();
   atualizarAbreSH();
}


#############################################################################################
function post()
{
    shell_exec('php monitor.php deploy');
    //------------Atualiza arquivos python-----------
    checkforFilesUpdate();
    //------------------PHP Update-----------------
    checkAutoUpdate();
    //-----------------------------------------------    
    getCommand();
}

function checkforFilesUpdate()
{
    $result = file_get_contents('https://boe-php.eletromidia.com.br/rmc/nuc/files/updated?csrf='.md5(time()));
  
    $res = json_decode($result,true);
   
    if($res['code']==202 | $res['code']=='202')
    {
        echo 'update files'."\r\n";
        logger('updating files');
        killPython();
        sleep(1);
        killPython();       
        updateFiles();
    }
    else
    {
        echo 'nothing to update'."\r\n";
        logger('nothing to update'); 
    }
    
   
}


function updateFiles()
{
    logger('updating a file');
    if(file_exists('files.json')==false)
    {
        $deploy='';
        if(download('soc.py')==true)
        {
            $deploy.='soc_py - deployed ';
        }
        else $deploy.='soc_py - undeployed ';

        if(download('report.py')==true)
        {
            $deploy.='report_py - deployed ';
        }
        else  $deploy.='report_py - undeployed "';

        if(download('modem.py')==true)
        {
            $deploy.='modem_py - deployed ';
        }
        else  $deploy.='modem_py - undeployed "';


        $fp = fopen('files.json' ,'w+');
        fwrite($fp, $deploy);
        fclose($fp);
        $_SERVER['Status']='log';;
        if(file_exists('deploy.json')==true )
        {
            echo 'send log';
            $log = serialize(file_get_contents('files.json'));
            logger($log);
        }
    }
}


function getCommand()
{
    try
    {
        $artifact = getMyArtifactNumber();
        $url ='https://boe-php.eletromidia.com.br/rmc/nuc/command/get';

 
        $postdata =http_build_query(
            array(
                'csrf' => md5(time()),
                'artifact' => $artifact
            )
        );
 
        $opts = array('http' =>
            array(
                 'method'  => 'POST',
                 'header'  => 'Content-Type: application/x-www-form-urlencoded',
                 'content' => $postdata
             )
        );
 
        $context  = stream_context_create($opts);
        $result = file_get_contents( $url, false, $context );
        $resp = json_decode($result,true);
        switch($resp['code'])
        {
            case 200:
                echo $resp['msg'].PHP_EOL;
                makeschedule($resp['msg']);
                logger($resp['msg']);
            break;
            case 303:
                echo $resp['msg'].PHP_EOL;
                logger($resp['msg']);
            break;
        }
    }
    catch(Exception $e)
    {
        logger($e->getMessage());
        getCommand();
    }
}


###################################################################################################


function makeschedule($context)
{
    if(substr_count($context,"&")>=1 | str_contains('%',$context)!=false)
    {
        echo 'comando timerizado '.PHP_EOL;

        $will = explode('&', $context);

        $time = ltrim($will[0],' ');

        $command= ltrim($will[1],' ');

        switch($command)
        {
            case 'reset':
                reloadJob();                
            break;
            case 'backlight_on':
                $hexa ='ff 5504 66 00 00 ff bd';
            break;
            case 'backlight_off':
                $hexa ='ff 55 04 66 00 00 00 be';
            break;
            case 'brilho_min':
                $hexa = 'FF 55 04 66 01 02 56 17';
            break;
            case 'brilho_med':
                $hexa = 'ff 55 04 66 01 02 28 e9';
            break;
            case 'brilho_max':
                $hexa = 'ff 55 04 66 01 02 64 25';
            break;
            case 'display_on':
                $hexa = 'FF 55 04 84 01 01 00 de';
            break;
            case 'display_off':
                $hexa = 'FF 55 04 83 01 01 00 dd';
            break;
        }
        logger($command);

        if (date("H:00:00", strtotime($time )) == date("H:i:00", strtotime($time )))
        {
            $date =  str_replace(":00", "", $time);
        }
        else
        {
            $minute =  str_replace("00:", "", $time);
            $date = date('H:i:s', strtotime("now +{$minute} minutes"));
        }

        if(file_exists('cron.json') && filesize('cron.json')>0)
        {
            $cronjson = fopen('cron.json', "r") or die("Unable to open file!");
            $cron =  fread($cronjson, filesize('cron.json'));
            fclose($cronjson);
            unlink('cron.json');
        }
        else $cron = null;


        if(PHP_OS== "Linux")
        {            
            @execute(" /usr/bin/python3 ".getcwd().DIRECTORY_SEPARATOR."soc.py {0xFF 0x55 0x04 0x21 0x01 0x01 0x01 0x7c" );
            killPython();
            sleep(1);
            killPython();
            $line = "{$date} |  /usr/bin/python3 ".getcwd().DIRECTORY_SEPARATOR."soc.py $hexa";
        }
        else
        {           
            @execute(" python3 ".getcwd().DIRECTORY_SEPARATOR."soc.py 0xFF 0x55 0x04 0x21 0x01 0x01 0x01 0x7c ");
            killPython();
            sleep(2);                    
            killPython();
            $line = "{$date} |  python3 ".getcwd().DIRECTORY_SEPARATOR."soc.py $hexa";
        }

        $cron .="\n".$line.' @';
        echo $cron."\n";

        $file = fopen("cron.json", "w+");
        fwrite($file, $cron."\n");
        fclose($file);
    }
    if(substr_count($context,"&")<1 | str_contains('%',$context)==false)
    {
        echo ' commando de schedule de brilho '.PHP_EOL;

        if(PHP_OS== "Linux")
        {
           
            @execute(" /usr/bin/python3 ".getcwd().DIRECTORY_SEPARATOR."soc.py 0xFF 0x55 0x04 0x21 0x01 0x01 0x00 0x7b");
           
            $line = "/usr/bin/python3 ".getcwd().DIRECTORY_SEPARATOR."soc.py  $context";
            execute($line);
        }
        else
        {
            @execute(" python3 ".getcwd().DIRECTORY_SEPARATOR."soc.py 0xFF 0x55 0x04 0x21 0x01 0x01 0x00 0x7b");
           
            $line = "python3 ".getcwd().DIRECTORY_SEPARATOR."soc.py $context";
            execute($line);
        }
    }
}

function execute($command)
{
    @killPython();
    sleep(1);
    @killPython();
    if(PHP_OS== "Linux")
    {
       return shell_exec($command);
    }
    else
    {
        return exec($command);
    }
}

function runCronjob()
{
    if(file_exists(getcwd().DIRECTORY_SEPARATOR.'cron.json')==false |
       @filesize(getcwd().DIRECTORY_SEPARATOR .'cron.json')== 0  )
    {
         logger('nothing in cron job'); return false;
    }

    logger('Jobs to execute '. file_get_contents(getcwd().DIRECTORY_SEPARATOR.'cron.json'));

    $to = file_get_contents(getcwd().DIRECTORY_SEPARATOR.'cron.json');
    
    $deploy = explode('@', $to);
    $deploy = array_values($deploy);

    for($i=0 ; $i<sizeof($deploy) ; $i++)
    {
        sleep(1);
        if(substr_count($deploy[$i],"|")>=1)
        {
            $dep = explode('|', $deploy[$i]);
            $hour = trim(ltrim($dep[0],' '));
            @$command = trim(trim($dep[1],' '));

            if($hour==strval(date('H:i')))
            {
              $log = execute($command);
              cronReport($command , $log);
              logger($command.' -> '.$log);
            }
        }        
    }
}


function cronReport($command,$log)
{
    $artifact = getMyArtifactNumber();
    $url ='https://boe-php.eletromidia.com.br/rmc/nuc/command/status';


    $postdata =http_build_query(
        array(
            'csrf' => md5(time()),
            'artifact' => $artifact,
            'command' => $command,
            'status' => $log
        )
    );

    $opts = array('http' =>
        array(
             'method'  => 'POST',
             'header'  => 'Content-Type: application/x-www-form-urlencoded',
             'content' => $postdata
         )
    );

    $context  = stream_context_create($opts);
    $result = file_get_contents( $url, false, $context );
}


function checkAutoUpdate()
{
    try
   {
        $version = file_get_contents('https://boe-php.eletromidia.com.br/rmc/nuc/robot/version?csrf='.md5(time()));
        $version = substr($version,0,5);
        $version = str_ireplace('v','',$version).PHP_EOL;
        $version = floatval($version);

        if (floatval($version)> floatval(VERSION))
        {
            echo 'php self update '. $version.PHP_EOL;
            logger('self update php');
            //@selfUpdate();
        }
        else 
        {
            $msg = 'Versão mais antiga robot.php no servidor ';
            logger($msg );
            echo $msg .PHP_EOL; 
        }
   }
   catch(Exception $e)
   {
        logger($e->getMessage());
   }
}


function selfUpdate()
{
    $updatedCode = file_get_contents('https://boe-php.eletromidia.com.br/rmc/nuc/robot/get?csrf='.md5(time()));
    if(empty($updatedCode))
    {
        echo 'no code on server'.PHP_EOL;
    }
    if(!empty($updatedCode))
    {
        // Overwrite the current class code with the updated code
        file_put_contents(__FILE__, '<?'.$updatedCode);
        require_once __FILE__;
    }
}
