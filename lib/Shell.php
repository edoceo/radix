<?php
/**
    Radix Shell Interaction
    
    Provides methods to interact with the system shell

    @package Radix
*/

namespace Radix;

class Shell
{
    /**
        Execute a System Command
        @param $cmd is the command to execute, escape your own arguments!
        @param $stdin is anything to write to stdin
        @return hash-array with return (exit code), stdout and stderr buffers
    */
    static function execute($cmd,$stdin=null)
    {
        $err=$out=$ret = null;

        $iod = array( // IO Definition
          0 => array('pipe', 'r'), // stdin
          1 => array('pipe', 'w'), // stdout
          2 => array('pipe', 'w')  // stderr
        );
        $ioh = null; // IO Handles
        $p = proc_open($cmd, $iod, $ioh);
        if (!is_resource($p)) {
          return false;
        } // trigger_error("Could not open $man_cmd",E_USER_ERROR);

        // Write stdin
        if (!empty($stdin)) {
            fwrite($ioh[0],$stdin);
        }
        fclose($ioh[0]);
        // Read stdout
        while (!feof($ioh[1])) {
            $out.= fgets($ioh[1], 1024);
        }
        fclose($ioh[1]);
        // Read stderr
        while (!feof($ioh[2])) {
            $err.= fgets($ioh[2], 1024);
        }
        fclose($ioh[2]);
        // Important!
        $ret = proc_close($p);
        //syslog(LOG_DEBUG,"ret: $ret");
        //if ($ret!=0) {
        //    syslog(LOG_DEBUG,"err: $err");
        //}
        return array(
            'return'=>$ret,
            'stdout'=>$out,
            'stderr'=>$err
        );
    }
    /**
        Runs a command in the background, output to /tmp/$pid.{out,err} or specified files
        @return PID of task
    */
    static function fork($cmd,$dir='/tmp')
    {
        /*
        $io_spec = array(
          0 => array('file', '/dev/null', 'r'), // stdin
          1 => array('file', $out, 'a'), // stdout
          2 => array('file', $err, 'a')  // stderr
        );
        syslog(LOG_DEBUG,"cmd(fork): $cmd");
                    $cwd = dirname(dirname(dirname(__FILE__)));
        $p = proc_open($cmd, $io_spec, $io_pipe,$cwd);
        if (!is_resource($p)) {
          return false;
        }
        // Close Pipes?
        fclose($io_pipe[0]);
        fclose($io_pipe[1]);
        fclose($io_pipe[2]);
        // Important!
        $ret = proc_close($p);
        syslog(LOG_DEBUG,"ret: $ret");
        if ($ret!=0) {
          syslog(LOG_DEBUG,"err: $err");
        }
        return array($ret,$out,$err,'return'=>$ret,'stdout'=>$out,'stderr'=>$err);
        */
        $stdout = tempnam(sys_get_temp_dir(),'seo');
        $stderr = tempnam(sys_get_temp_dir(),'seo');

        $cmd = sprintf('%s >%s 2>%s & echo $!',$cmd,$stdout,$stderr);
        //Radix::dump($cmd);
        // Using exec
        $pid = exec($cmd,$out,$ret);
        if ($ret == 0) {
            $ret = array(
                'pid' => $pid,
                'out' => $stdout,
                'err' => $stderr,
            );
            return $ret;
        }
        //Radix::dump($out);
        //Radix::dump($ret);
        //Radix::dump($pid);
        // Using shell_exec (same as backtick) - will not work in safe mode
        //$pid = shell_exec($cmd,null,$ret);
        // Using system - discouraged as it flushes output buffers
        //$pid = system($cmd,$ret);
        return false;
    }
    /**
        This routine is intended to fork out an application
    */
    static function fork_wait($pid,$wait=2500)
    {
        // @todo check if PID exists and if it does, wait and try again.
        // if pid don't exist then load the data from the /out and /err file
        // which will be the PID - our application forces that pattern
    }
}