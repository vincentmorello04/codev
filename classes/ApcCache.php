<?php

/*
  This file is part of CoDev-Timetracking.

  CoDev-Timetracking is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  CoDev-Timetracking is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with CoDev-Timetracking.  If not, see <http://www.gnu.org/licenses/>.
 */


/**
 * Description of ApcCache
 *
 */
class ApcCache {

   private static $logger;
   private static $instance;

   private $isEnabled;
   private $keyBase;

   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   /**
    * The singleton pattern
    * @static
    * @return PagApplication
    */
   public static function getInstance() {
      if (!self::$instance) {
         $c = __CLASS__;
         self::$instance = new $c();
      }
      return self::$instance;
   }

   protected function __construct() {

      // check if APC cache is enabled.
      $this->isEnabled = extension_loaded('apc') && ini_get('apc.enabled') && Constants::$apc_cache_enabled;

      if ($this->isEnabled) {
         $this->keyBase = 'CodevTT_'.$_SESSION['userid'].'_'.session_id().'_';
         $this->keyBaseGlobal = 'CodevTT_'.str_replace(array(' ', '\\'), '-', dirname(__DIR__)).'_'; # codevtt root dir

         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("ApcCache is enabled. keyBase = '$this->keyBase'");
         }
      }

   }

   public function isEnabled() {
      return $this->isEnabled;
   }

   public function setEnabled($enabled) {

      if(self::$logger->isDebugEnabled()) {
         $tmp = ($enabled) ? 'true' : 'false';
         self::$logger->debug("setEnabled($tmp)");
      }
      $this->isEnabled = $enabled;
   }

   /**
    * Save data to the cache
    */
   public function set($key, $data, $ttl = 0, $isGlobal = false) {

      if ($this->isEnabled) {
         $realKey = $isGlobal ? $this->keyBaseGlobal.$key : $this->keyBase.$key;

         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("set key = '$realKey' ttl = $ttl sec.");
         }

         if (!apc_store($realKey, $data, $ttl)) {
            $self::$logger->error('Error saving key ' . $realKey);
            return false;
         }
      }
      return true;
   }

   /**
    * Get the specified data from the cache
    *
    * @param type $key
    * @return $value or null if not found (or error)
    */
   public function get($key, $isGlobal = false) {

      if ($this->isEnabled) {
         $realKey = $isGlobal ? $this->keyBaseGlobal.$key : $this->keyBase.$key;

         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("get key = '$realKey'");
         }

         if (apc_exists($realKey)) {
            if (!$data = apc_fetch($realKey)) {
               $self::$logger->error('Error fetching key ' . $realKey);
               return null;
            }
            return $data;
         } else {
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("get key = '$key' : Not found in cache");
            }
         }
      } else {
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("get key = '$key' : APC Cache NOT enabled !");
         }
      }
      return null;
   }

   /**
    * Delete the specified data from the cache
    */
   public function delete($key, $isGlobal = false) {

      if ($this->isEnabled) {
         $realKey = $isGlobal ? $this->keyBaseGlobal.$key : $this->keyBase.$key;

         if ($this->exists($realKey)) {
            if (!apc_delete($realKey)) {
               $self::$logger->error('Error deleting key ' . $realKey);
               return false;
            }
         }
      }
      return true;
   }

   /**
    * Check if the specified cache key exists
    */
   public function exists($key, $isGlobal = false) {
      if (!$this->isEnabled) { return false; }

      $realKey = $isGlobal ? $this->keyBaseGlobal.$key : $this->keyBase.$key;
      return apc_exists($realKey);
   }

   /**
    * Clear the cache
    */
   public function clear($cacheType = 'user') {
      if (!$this->isEnabled) { return true; }

      return apc_clear_cache($cacheType);
   }

}

ApcCache::staticInit();
?>
