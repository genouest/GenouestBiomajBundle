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

use Genouest\Bundle\BiomajBundle\Exception\DeadBiomajServerException;

class BankManager extends ContainerAware
{
    private $biomajUrl; // Base URL of the BioMaj server

    /**
     * Constructs a BankManager object.
     */
    public function __construct($biomajUrl) {
        
        // Add the trailing slash if not present
        if (substr($biomajUrl, -1) !== '/') {
            $biomajUrl .= '/';
        }
        
        $this->biomajUrl = $biomajUrl;
    }

    /**
     * Get the url of the biomaj server
     *
     * @return string The url of the biomaj server
     */
    public function getBiomajUrl() {
        return $this->biomajUrl;
    }

    /**
     * Get a Bank object with the given name and having the given format
     * This is for compatibility with previous BankManager versions. You should use getBanks() instead.
     * 
     * @param name The name of the asked bank
     * @param format The format needed
     * @return a Bank object or NULL if the Bank was not found.
     */
    public function getDB($name, $format = "") {
        $banks = $this->getListDB(array($name), $format);
        
        if (!array_key_exists($name, $banks))
            return null;

        return $banks[$name];
    }

    /**
     * Get a list of Bank objects with a name found in the given list of bank name and the given format
     * This is for compatibility with previous BankManager versions. You should use getBanks() instead.
     * 
     * @param names An array of bank names to find
     * @param format The format needed
     */
    public function getListDB($names, $format = "") {
        return $this->getBanks($names, array($format), array());
    }

    /**
     * Get a list of Bank objects with the given type and the given format
     * The types list can be viewed using biomaj status
     * (genome(dog,bacteria,mouse,human),nucleic,protein,nucleic_protein,other)
     * This is for compatibility with previous BankManager versions. You should use getBanks() instead.
     * 
     * @param type The bank type asked
     * @param format The format needed
     */
    public function getListTypeDB($type, $format = "") {
        return $this->getBanks(array(), array($format), array($type));
    }

    /**
     * Get a list of all Bank objects with the given format
     * This is for compatibility with previous BankManager versions. You should use getBanks() instead.
     * 
     * @param format The format needed
     */
    public function getAllDB($format = "") {
        return $this->getBanks(array("all"), array($format), array());
    }

    /**
     * Get a list of all Bank names and versions of the given type
     * 
     * @param type The type asked
     * @return An array: Bank name (string) => version (string)
     */
    public function getBankNames($type) {
        // Download bank list
        $banks = $this->getBanks(array("all"), array(), array($typeDB), true);
        
        $listeNameDB = array();
        
        foreach ($banks as $bank) {
            $listeNameDB[$bank->getName()] = $bank->getLastUpdate();
        }
        return $listeNameDB;
    }

    /**
     * Get a list of banks from REST API
     * Examples:
     *     getBanks(array(), array(), array()) => get all banks
     *     getBanks(array("Genbank", "Unigene"), array("fasta"), array())
     *     getBanks(array("all"), array("fasta"), array("nucleic", "genome"))
     *
     * @param names Array of bank names to look for
     * @param formats Array of formats to look for
     * @param types Array of types to look for
     * @param light Use the light mode (default is false)
     */
    public function getBanks($names, $formats, $types, $light = false) {
        // Check input data
        if (empty($names))
            $names['all'];
        
        // Construct the URL
        $reqUrl = $this->getBiomajUrl()."GET?";
        if (!empty($names)) {
            $reqUrl .= "banks=";
            foreach ($names as $id => $name) {
                if ($id != 0)
                    $reqUrl .= '|';
                $reqUrl .= $name;
            }
        }
        if (!empty($formats)) {
            $reqUrl .= "&formats=";
            foreach ($formats as $id => $format) {
                if ($id != 0)
                    $reqUrl .= '|';
                $reqUrl .= $format;
            }
        }
        if (!empty($types)) {
            $reqUrl .= "&types=";
            foreach ($types as $id => $type) {
                if ($id != 0)
                    $reqUrl .= '|';
                $reqUrl .= $type;
            }
        }
        
        if ($light)
            $reqUrl .= "&lightmode";
        
        // Download using cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $reqUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return the transfer as a string
        $jsonData = curl_exec($ch);
        $res = curl_errno($ch);
        curl_close($ch);
        
        // Check for errors
        if ($res)
            throw new DeadBiomajServerException('Failed to download bank list ('.$reqUrl.').');

        $jsonData = json_decode($jsonData, true); // Get associative array
        
        if (empty($jsonData))
            throw new DeadBiomajServerException('Received wrong response from BioMAJ server ('.$reqUrl.').');
        
        $bankList = array();
        
        foreach($jsonData['banks'] as $jsonBank) {
            $jsonBank;
            $newBank = $this->createBank($jsonBank);
            if ($newBank)
                $bankList[$newBank->getName()] = $newBank;
        }
        
        uksort($bankList, 'strnatcasecmp');
        
        return $bankList;
    }

    /**
     * Create a new Bank object with the given JSON content.
     *
     * @param json A 'bank' from the JSON content
     */
    protected function createBank($json){
        $dbName = $json['name'];
        $currentRelease = $json['current_release'];
        $lastUpdate = $json['session_date'];
        $bankType = $json['db_type'];

        $releases = array();

        if (empty($dbName) || empty($currentRelease) || empty($lastUpdate) || empty($bankType))
            return null;

        // Get available releases
        foreach ($json['releases'] as $name => $release)
        {
            // Get formats
            $formats = array();
            $formatSections = array();
            foreach ($release['formats'] as $jsonFormat) {
                $formats[] = $jsonFormat['value'];
                $formatSections[$jsonFormat['value']] = $this->getSection($jsonFormat, $jsonFormat['value']);
            }
            $newRelease = new BankRelease($name, $release['path'], $formats, $formatSections, ($name == $currentRelease));
            
            $releases[$name] = $newRelease;
        }

        ksort($releases);

        // Construct and return the bank object
        return new Bank($dbName, $lastUpdate, $bankType, $currentRelease, $releases);
    }

    /**
     * Create a list of sections and corresponding paths for the given bank format.
     * 
     * @param jsonFormat A 'format' JSON data
     * @param format The name of the current bank format
     * @param res_section The current position in section arborescence
     */
    protected function getSection($jsonFormat, $format, $res_section=""){
        $tab_res = array();
        if (isset($jsonFormat['sections'])) {
            $sections = $jsonFormat['sections'];
            foreach ($sections as $section)
            {
                $sectionName = $section['name'];
                $res_tmp = $res_section."/".$sectionName;
                $subSectionRes = $this->getSection($section,$format,$res_tmp);
                $tab_res = array_merge($tab_res, $subSectionRes);
            }
        }
        
        if (isset($jsonFormat['files'])) {
            $files = $jsonFormat['files'];
            foreach ($files as $file)
            {
                if ($format == "blast") {
                    $fp = preg_match("/^(.*)\.(.*)$/", $file, $items);
                    $tab_res[$res_section] = $items[1];
                }
                else {
                    $tab_res[$res_section] = $file;
                }
            }
        }
        
        uksort($tab_res, 'strnatcasecmp');
        return $tab_res;
    }


    /**
     * Retrieve in JSON format the list of banks corresponding to the given parameters from a Biomaj server
     
     * @param array An array of bank types
     * @param string The format of the bank
     * @param bool Set to true if you want the names of banks to be cleaned up (mostly replace '_' by ' ')
     * @return string A JSON representation of the bank list
     */
    public function getJsonBankList($dbtypes, $dbformat, $cleanUp) {
        return '{"tree" : ['.$this->convertBankListToJson($this->getBankList($dbtypes, $dbformat, $cleanUp)).']}';
    }
  
    /**
     * Retrieve the list of banks corresponding to the given parameters from a Biomaj server
     *
     * @param array An array of bank types
     * @param string The format of the bank
     * @param bool Set to true if you want the names of banks to be cleaned up (mostly replace '_' by ' ')
     * @return array The bank list
     */
    public function getBankList($dbtypes, $format, $cleanUp) {
        // Use the bank manager to retrieve the list of available banks
        $banks = $this->getBanks(array("all"), array($format), $dbtypes);

        // Prepare an array of available banks
        $bankList = array();
        foreach($banks as $myBank) {
            $lastUpdate = $myBank->getLastUpdate();
            $nameDb = $myBank->getName();
            if ($cleanUp)
                $nameDb = $this->cleanUpBankName($nameDb);

            $fileMap = $myBank->getFormatSections();

            $previousSection = array();
            foreach($fileMap[$format] as $dbsection => $path) {
                $currentSection = array_values(array_filter(explode("/", $dbsection)));
                $maxDepthSections = count($currentSection); // max depth from 1 to n
                $foundNewSection = false;

                // Add each found db
                foreach ($currentSection as $posSection => $newSection) {
                    if ($foundNewSection || !isset($previousSection[$posSection]) || ($previousSection[$posSection] != $newSection)) {
                        // We are entering a not yet known section
                        $foundNewSection = true;
                        if ($maxDepthSections == $posSection+1) {
                            // A leaf => generate the whole branch (from the last already existing node (or root if none) to the leaf)
                            $leafTitle = $newSection;
                            if ($cleanUp)
                                $leafTitle = $this->cleanUpBankName($leafTitle);
                            $newarray = array();
                            $newarray[$path] = str_repeat("&nbsp;&nbsp;", ($posSection < 3 ? 0 : $posSection-2)).$leafTitle;
                            $currentindice = $posSection;
                            
                            while ($currentindice > 0) {
                                $currentindice--;
                                $oldarray = $newarray;
                                $newarray = array();
                                $branchTitle = $currentSection[$currentindice];
                                if ($cleanUp)
                                    $branchTitle = $this->cleanUpBankName($branchTitle);
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
     * Make a bank name prettier (remove strange chars)
     *
     * @param string A bank name to make prettier
     * @return string The prettier bank name 
     */
    public function cleanUpBankName($dbName) {
        $res = str_replace('_', ' ', $dbName);
        
        return $res;
    }
  
    /**
     * Convert a bank list array to JSON representation
     *
     * @param array A bank list
     * @return string A JSON representation of the bank list
     */
    public function convertBankListToJson($list) {
        $output = '';
        $isFirst = true;
        
        foreach ($list as $path => $displayName) {
        
            if (!$isFirst)
                $output .= ',';

            $output .= '{';
            $output .= '"path" : "'.$path.'",';
            
            if (is_array($displayName)) {
                $output .= '"displayName" : "null",';
                $output .= '"type" : "group",';
                $output .= '"dbChildren" : ['.$this->convertBankListToJson($displayName).']';
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
?>
