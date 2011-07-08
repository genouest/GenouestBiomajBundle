<?php

/*
 * Copyright 2011 Anthony Bretaudeau <abretaud@irisa.fr>
 *
 * Licensed under the CeCILL License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt
 *
 */

namespace Genouest\Bundle\BiomajBundle\Biomaj;

use Symfony\Component\DependencyInjection\ContainerAware;

class BankManagerAccessor extends ContainerAware
{
  
    /**
     * Retrieve the list of banks corresponding to the given parameters from a Biomaj server
     *
     * @param array An array of bank types
     * @param string The format of the bank
     * @param bool 
     * @param bool Set to true if you want the names of banks to be cleaned up (mostly replace '_' by ' ')
     */
    public function prepareBankList($dbtypes, $format, $filterall, $cleanUp) {
        // Use the bank manager to retrieve the list of available banks
        $bankManager = $this->container->get('biomaj.bank.manager');
        $banks = $bankManager->getBanks(array("all"), array($format), $dbtypes);

        // Prepare an array of available banks
        $bankList = array();
        foreach($banks as $myBank) {
            $lastUpdate = $myBank->getLastUpdate();
            $nameDb = $myBank->getName();
            if ($cleanUp)
                $nameDb = BankManagerAccessor::cleanUpBankName($nameDb);

            $fileMap = $myBank->getFormatSections(true);

            $previousSection = array();
            foreach($fileMap[$format] as $dbsection => $path) {
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

    /**
     * 
     */
    public function prepareBankListAjax($dbtypes, $dbformat, $filterall, $cleanUp) {
        return '{"tree" : ['.BankManagerAccessor::formatDbListToJSON(BankManagerAccessor::prepareBankList($dbtypes, $dbformat, $filterall, $cleanUp)).']}';
    }
  
    /**
     * 
     */
    public function cleanUpBankName($dbName) {
        $res = str_replace('_', ' ', $dbName);
        
        return $res;
    }
  
    /**
     * 
     */
    public function formatDbListToJSON($choices) {
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
