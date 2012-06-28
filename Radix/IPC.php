<?php
/**
    Radix IPC Tools

    @package Radix
    @version $Id$

*/

class Radix_IPC
{
    /**
        Peek at a Message Queue Return the Number of Messages
    */
    static function mqPeek($name)
    {
        $mq = msg_get_queue(self::_ftok($name));
        $stat = msg_stat_queue($mq);
        return $stat['msg_qnum']; // Items in the Queue
    }
    static function mqRead($name,$want=0,$wait=true)
    {
        $mq = msg_get_queue(self::_ftok($name));
        
        $type = null; // Recieved Message Type
        $size = 8192; // Max Message Size 
        $mess = null; // Recieved Message Data
        $unser = true;
        $flags = 0;
        $error = null;
        
        if ($wait==false) {
            $flags |= MSG_IPC_NOWAIT;
        }

        if (msg_receive($mq,$want,$type,$size,$mess,$unser,$flags,$error)) {
            return $mess;
        }
        
        Radix::dump($mq);
        Radix::dump($want);
        Radix::dump($type);
        Radix::dump($size);
        Radix::dump($mess);
        Radix::dump($unser);
        Radix::dump($flags);
        Radix::dump($error);
        exit;
        
    }
    /**
        Put a Message Into the Queue
    */
    static function mqSend($name,$type,$mess)
    {
        $mq = msg_get_queue(self::_ftok($name));
        
        $block = true;
        $error = null;
        
        if (msg_send($mq,$type,$mess,true,$block,$error)) {
            return true;
        }
        
    }
    // Shared Memory Functions
    /**
        smInit
        @param $name = the name of the shared memory segment
        @param $size = how large in bytes, default 1MiB
        @param $perm = permissions, 0660
    */
    static function smInit($name,$size=1048576,$perm=0660)
    {
        $mq = shm_attach(self::_ftok($name),$size,$perm);
    }
    /**
        Gets something from Shared Memory
    */
    static function smGet($name,$key)
    {
        $shm = shm_attach(self::ftok($name));
        return shm_get_var($shm,$key);
    }
    /**
        Puts something into Shared Memory
    */
    static function smSet($name,$key,$val)
    {
        $shm = shm_attach(self::ftok($name));
        return shm_put_var($shm,$key,$val);
    }
    /**
        Our Own FTOK, not compatible with outside!!!
        
    */
    private static function _ftok($name)
    {
        if (is_numeric($name)) {
            if (is_int($name)) {
                return $name;
            }
        }
        return crc32(serialize($name));
        /*
        // Here is the translated glibc 2.3.2 version
        function ftok($pathname, $proj_id) {
           $st = @stat($pathname);
           if (!$st) {
               return -1;
           }
           $key = sprintf("%u", (($st['ino'] & 0xffff) | (($st['dev'] & 0xff) << 16) | (($proj_id & 0xff) << 24)));
           return $key;
        }
        */
    }
}
