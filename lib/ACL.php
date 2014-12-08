<?php
/**
    @file
    @brief Implements a Simple Actor->Asset Based Access Control List

    @copyright 2009 edoceo, inc.
    @package radix

    This controls Actor access to Asset

*/

namespace Radix;

class ACL
{
    private static $_access = 'reject,permit';
    private static $_actors; // List of Actors
    private static $_assets; // List of Assets
    private static $_family; // Actor Family Tree
    private static $_default_actor = null;

    /**
        Initialize the Array - could use a serialized ACL here
        @param mixed $args
            true initialize from session
    */
    static function init($args=null)
    {
        self::$_actors = array();
        self::$_assets = array();
        if ($args === true) {
            self::$_access = $_SESSION['_radix']['_acl']['access'];
            self::$_actors = $_SESSION['_radix']['_acl']['actors'];
            self::$_assets = $_SESSION['_radix']['_acl']['assets'];
            self::$_family = $_SESSION['_radix']['_acl']['family'];
        }
    }

    /**
        Return Array of Settings
    */
    static function info()
    {
        $ret = array();
        $ret['Access'] = self::$_access;
        $ret['Actors'] = self::$_actors;
        $ret['Assets'] = self::$_assets;
        $ret['Family'] = self::$_family;
        return $ret;
    }

    /**
        Add and Actor (by name) to the List, with optional Parent
        @param $a = the Name of the Actor
        @param $p = null|the Name of the Parent Actor
    */
    static function addActor($a,$p=true)
    {
        // self is parent if no parent
        if (empty($p)) {
            $p = $a;
        }
        self::$_family[$a] = $p;
    }

    /**
        Determine if Actor may Asset
        @param $actor
        @param $asset
        @return true|false
    */
    static function may($actor,$asset=null)
    {
        // One Parameter Means Just Checking Asset against Actor
        if ( empty($actor) && ($asset===null) ) {
            $asset = $actor;
            $actor = self::$_default_actor;
        }

        $actor = self::_normalize($actor);
        $asset = self::_normalize($asset);

        $list = array();
        $p = $actor;
        while (!empty($p)) { // self::$_parent[ $p ]) {
            $list[] = $p;
            $p = self::$_family[ $p ];
        }
        //Radix::dump($list); // Check this Actor List
        // if (count($list)) {
        //     foreach ($list as $a) {
        //         // echo "<p>Checking: $a</p>";
        //     }
        // }
        
        // Can this Role access this Resource
        // $p = null;
        // while ($p != $actor) {
        //     $p = self::$_actors[ $actor ]['parent_id'];
        // }

        // Actor Global Access
        if (self::$_actors[ $actor ]['*'] == 'permit') {
            // echo "Actor Open";
            return true;
        }

        // Asset Global Access
        if (self::$_assets[ $asset ]['*'] == 'permit') {
            // echo "Asset Open";
            return true;
        }

        // Actor Specific Asset Rule
        if (self::$_actors[ $actor ][ $asset ] == 'permit') {
            // Actor Given Specific Asset Access
            // echo "Actor => Asset Permit";
            return true;
        }

        //Radix::dump(self::$_actors[ $actor ]);
        //Radix::dump(self::$_assets[ $asset ]);

        return false;
    }

    /**
        Open a Path for an Actor to an Asset
        @param $actor
        @param $asset
    */
    static function permit($actor,$asset)
    {
        $actor = self::_normalize($actor);
        $asset = self::_normalize($asset);
        if (empty(self::$_assets[ $asset ])) {
            self::$_assets[ $asset ] = array();
            // Default Rule
            self::$_assets[ $asset ]['*'] = self::$_access;
        }
        self::$_assets[ $asset ][ $actor ] = 'permit';
        self::$_actors[ $actor ][ $asset ] = 'permit';
    }

    /**
        Shut a Path for an Actor to an Asset
        @param $actor
        @param $asset
    */
    static function reject($actor,$asset)
    {
        $actor = self::_normalize($actor);
        $asset = self::_normalize($asset);
        if (empty(self::$_assets[ $asset ])) {
            self::$_assets[ $asset ] = array();
            // Default Rule
            self::$_assets[ $asset ]['*'] = self::$_access;
        }
        self::$_assets[ $asset ][ $actor ] = 'reject';
        self::$_actors[ $actor ][ $asset ] = 'reject';
    }

    /**
        Save to the Session
    */
    public static function save()
    {
        $_SESSION['_radix']['_acl'] = array(
            'access' => self::$_access,
            'actors' => self::$_actors,
            'assets' => self::$_assets,
            'family' => self::$_family,
        );
    }

    /**
        Set the Default Actor
        @param $a Actor
    */
    public static function setActor($a)
    {
        self::$_default_actor = self::_normalize($a);
    }

    /**
        Normalizes the Object Passed
        @param mixed $o
        @return string normalized value
    */
    private static function _normalize($o)
    {
        if ( (is_array($o)) || (is_object($o)) ) {
            $o = sprintf('%08x',crc32(serialize($o)));
        }
        return strtolower($o);
    }
}
