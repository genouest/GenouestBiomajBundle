<?php

namespace Genouest\Bundle\BiomajBundle\Biomaj;

/*
 * This file is part of sfGenouestCommonsPlugin.
 * (c) 2009 GenOuest Platform <support@genouest.org>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
/**
 * BankManagerAccessor class.
 *
 * @package    sfGenouestCommonsPlugin
 * @author     GenOuest Platform <support@genouest.org>
 */
 
/**
 * Bank Manager accessor. Uses Bank Manager lib and provide an easy access for symfony applications.
 *
 */

class BankManagerAccessor
{
  public static function prepareBankListAjax($dbtypes, $dbformat, $filterall, $cleanUp) {
    return '{"tree" : ['.BankManagerAccessor::formatDbListToJSON(BankManagerAccessor::prepareBankList($dbtypes, $dbformat, $filterall, $cleanUp)).']}';
  }
  
  public static function prepareBankList($dbtypes, $format, $filterall, $cleanUp) {
    // Use the bank manager to retrieve the list of available banks
    $bankManager = new \BankManager();
    $banks = $bankManager->getBanks(array("all"), array($format), $dbtypes);
    
    // Prepare an array of available banks
    $bankList = array();
		foreach($banks as $myBank)
		{
			$lastUpdate = $myBank->getLastUpdate();
			$nameDb = $myBank->getName();
			if ($cleanUp)
  			$nameDb = BankManagerAccessor::cleanUpBankName($nameDb);
			
			$fileMap = $myBank->getFormatSections(true);
			
      $previousSection = array();
			foreach($fileMap[$format] as $dbsection => $path)
			{
        $currentSection = array_values(array_filter(explode("/", $dbsection)));
        $maxDepthSections = count($currentSection); // max depth from 1 to n
        $foundNewSection = false;
      
        // Add each found db
        foreach ($currentSection as $posSection => $newSection) {
          if ($foundNewSection || !isset($previousSection[$posSection]) || ($previousSection[$posSection] != $newSection)) { // We are entering a not yet known section
            $foundNewSection = true;
            if ($maxDepthSections == $posSection+1) { // A leaf of our tree => generate the whole branch (from the last already existing node (or root if none) to the leaf)
              $leafTitle = $newSection;
			        if ($cleanUp)
			          $leafTitle = BankManagerAccessor::cleanUpBankName($leafTitle);
              $newarray = array();
              $newarray[$path] = str_repeat("&nbsp;&nbsp;", ($posSection < 3 ? 0 : $posSection-2)).$leafTitle;
              $currentindice = $posSection;
              while ($currentindice > 0) {
                $currentindice--;
                $oldarray = $newarray;
                $newarray = array();
                $branchTitle = $currentSection[$currentindice];
			          if ($cleanUp)
			            $branchTitle = BankManagerAccessor::cleanUpBankName($branchTitle);
			          $branchTitle = str_repeat("&nbsp;&nbsp;", $currentindice+1).$branchTitle;
                $newarray[$branchTitle] = $oldarray;
              }
              if (array_key_exists($nameDb, $bankList))
                $bankList[$nameDb] = array_merge_recursive($bankList[$nameDb], $newarray);
              else
                $bankList[$nameDb] = $newarray;
            }
          }
        }
        $previousSection = $currentSection;
			}
	  }

    return $bankList;
  }
  
  public static function cleanUpBankName($dbName) {
    $res = str_replace('_', ' ', $dbName);
    return $res;
  }
  
  public static function formatDbListToJSON($choices) {
    $output = '';
    $isFirst = true;
    foreach ($choices as $path => $displayName) {
      if (!$isFirst)
        $output .= ',';
      
      $output .= '{';
      $output .= '"path" : "'.$path.'",';
      if (is_array($displayName)) {
        $output .= '"displayName" : "null",';
        $output .= '"type" : "group",';
        $output .= '"dbChildren" : ['.BankManagerAccessor::formatDbListToJSON($displayName).']';
      }
      else {
        $output .= '"displayName" : "'.$displayName.'",';
        $output .= '"type" : "item"';
      }
      $output .= '}';
      $isFirst = false;
    }
    return $output;
  }
}
