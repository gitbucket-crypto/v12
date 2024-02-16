<?php
header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");

define('VERSION','1.00');
define('AUTOR',"#--- PEDRO HENRIQUE SILVA DE DEUS ---# \n Email: pedro.hsdeus@aol.com ".VERSION." \n");
#################################################################################################

if(PHP_OS=='Linux')
{
    if(@$argv[1]=='develop')
    {
        define('bash', 'echo ZWNobyBmYXN0OTAwMiB8IHN1ZG8gLVMgZWNobyBvayA+IC9kZXYvbnVsbCAyPiYx  | base64 -d | bash');
    }
    else
    {
        define('bash','echo ZWNobyAzbDNtMWQxQCB8IHN1ZG8gLVMgZWNobyBvayA+IC9kZXYvbnVsbCAyPiYxCg== | base64 -d  | bash');
    }
}


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


function downloadFile($url, $path)
{
    $newfname = $path;
    if(file_exists($path))
    {
        unlink($path);
    }
    $file = fopen ($url, 'rb');
    if ($file) {
        $newf = fopen ($newfname, 'wb');
        if ($newf) {
            while(!feof($file)) {
                fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
            }
        }
    }
    if ($file) {
        fclose($file);
    }
    if ($newf) {
        fclose($newf);
    }
}

function installENV()
{   
    if(PHP_OS == "Linux")
    {
        $uname = shell_exec(' uname -v');
        if(strpos($uname, '6.1.69')==false)
        {
            echo 'kernel antigo '.PHP_EOL;

            $sys =   shell_exec(bash.'&& sudo dpkg -l sysstat');
            if(strpos($sys, 'dpkg-query: não foram encontrados pacotes coincidindo com')!=false)
            {
                echo 'dpkg nãoo achou sysstat 12.5 '.PHP_EOL;
                downloadFile('http://ftp.de.debian.org/debian/pool/main/s/sysstat/sysstat_12.5.2-2_amd64.deb',getcwd().DIRECTORY_SEPARATOR.'sysstat_12.5.2-2_amd64.deb');
                shell_exec(bash.'&& sudo apt install ./sysstat_12.5.2-2_amd64.deb  -y');
            }            
        }
        else
        {
            $sys =   shell_exec(bash.'&& sudo dpkg -l sysstat');
            if(strpos($sys, 'dpkg-query: não foram encontrados pacotes coincidindo com')!=false)
            {
                shell_exec(bash.' && sudo apt-get install sysstat -y');
            }      
        }

        $lsw = shell_exec(bash.'&& sudo dpkg -l lshw');
        if(strpos($lsw, 'dpkg-query: não foram encontrados pacotes coincidindo com')!=false)
        {
            shell_exec(bash.' && sudo apt-get install lshw -y');
        }

        $lms = shell_exec(bash.'&& sudo dpkg -l lm-sensors');
        if(strpos($lms, 'dpkg-query: não foram encontrados pacotes coincidindo com')!=false)
        {
            shell_exec(bash.' && sudo apt-get install lm-sensors -y');
        }

        $pip= shell_exec(bash.'&& sudo dpkg -l python3-pip');
        if(strpos($pip, 'dpkg-query: não foram encontrados pacotes coincidindo com')!=false)
        {
            shell_exec(bash.' && sudo apt-get install python3-pip -y');
        }
    
       shell_exec(bash.'&& sudo rm -rf /usr/lib/'.python.'/EXTERNALLY-MANAGED');
       shell_exec(bash.'&& sudo touch EXTERNALLY-MANAGED');

       $pip = shell_exec(python.' -m pip freeze');
       if(find('requests', $pip)==false)
       {
            shell_exec(bash.'&& '.python.' -m pip install requests');
            shell_exec(bash.'&& '.python.' -m pip install requests --user');

       }
       if(find('selenium', $pip)==false)
       {
            shell_exec(bash.'&& '.python.' -m pip install selenium');
            shell_exec(bash.'&& '.python.' -m pip install selenium --user');

       }
       if(find('speedtest-cli', $pip)==false)
       {
            shell_exec(bash.'&& '.python.' -m pip speedtest-cli ');
            shell_exec(bash.'&& '.python.' -m pip speedtest-cli --user');
       }  
    }
    else
    {
        exec(python.' -m pip install --upgrade pip');
        exec(python.' -m pip install --upgrade virtualenv');
        exec(python.' -m pip install requests selenium ');
        exec(python.' -m pip install requests selenium --user');
    }
}

function find($needle, $haystack)
{
    if ($needle !== '' && str_contains($haystack, $needle)) {
        echo "This returned true!".PHP_EOL;
        return true;
    }
    else 
    {
        echo "This returned false!".PHP_EOL;
        return false;
    }
}

if (!function_exists('str_contains')) 
{
    function str_contains($haystack, $needle) {
        return $needle !== '' && strpos($haystack, $needle) !== false;
    }
}

#################################################################################################
init();
installENV();
exit;
?>
