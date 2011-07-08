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

class BankManager {
    private $biomajUrl; // Base URL of the BioMaj server
    // FIXME check statistic code

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
     * @returns a Bank object or NULL if the Bank was not found.
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
     * @returns An array: Bank name (string) => version (string)
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
            throw new Exception('Failed to download bank list ('.$reqUrl.').');

        $jsonData = json_decode($jsonData, true); // Get associative array
        
        if (empty($jsonData))
            throw new Exception('Received wrong response from BioMAJ server ('.$reqUrl.').');
        
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
     * Collect statistics for the given bank path (added 13/03/09)
     *
     * @param app The name of the app using this db
     * @param path The path of the bank asked
     */
    static public function sendStats($app, $path) {
        BankManager::sendGAStats($app, $path); // use google analytics stats
        //BankManager::sendSQLStats($app, $path); // SQL stats. Not used yet but it works.
    }
    
    /**
     * Collect statistics for the given bank path using Google Analytics (added 13/03/09)
     *
     * @param app The name of the app using this db
     * @param path The path of the bank asked
     */
    static private function sendGAStats($app, $path) {
        // For more info, see:
        // http://code.google.com/intl/fr/apis/analytics/docs/gaTrackingTroubleshooting.html
        // http://www.morevisibility.com/analyticsblog/from-__utma-to-__utmz-google-analytics-cookies.html
        // http://www.wagablog.com/2007/11/les-cookies-geres-par-google-analytics/27
        $utmac = 'UA-7389032-5'; // The GA profile ID
        $utmwv = '4.3'; // GA version
        $utmn=rand(1000000000,9999999999); // Random request number
        $utmhid=rand(1000000000,9999999999); // HID from AdSense (random as we don't use AdSense)
        $utmcs="UTF-8"; // Encoding
        $utmhn="genodata.irisa.fr"; // Hostname
        $utmr="-"; // Referer URL
        if (!empty($path))
            $utmp=$path; // Document URL
        else
            $utmp="/personal/db";
        $utmdt=urlencode($app); // Document title: set to the app name
        
        // 1 db request => 1 unique visit
        $currentTimestamp = time(); //today
        $domainHash = rand(10000000,99999999);// random cookie number = domain hash
        $randomHash = rand(1000000000000000000,2147483647000000000); // number under 2147483647000000000
        $currentVisit = $currentTimestamp;
        $firstVisit = $currentVisit;
        $previousVisit = $firstVisit;
        $visitNumber = 1;
        
        // Populate the link parameters
        $utmcc = '__utma%3D';
        $utmcc .= $domainHash; // domain hash
        $utmcc .= '.'.$randomHash;// unknown, constant (another hash?)
        $utmcc .= '.'.$firstVisit; // Timestamp of our first visit
        $utmcc .= '.'.$previousVisit; // Timestamp of our previous visit
        $utmcc .= '.'.$currentVisit; // Timestamp of current visit
        $utmcc .= '.'.$visitNumber; // Number of visits since the first visit (including the first one)
        $utmcc .= '%3B%2B__utmz%3D'; // Track the access sources (here, we consider always direct, so the data is quite constant)
        $utmcc .= $domainHash; // domain hash
        $utmcc .= '.'.$firstVisit; // Timestamp when the cookie was set
        $utmcc .= '.1'; // How many visits they had made when the cookie was set
        $utmcc .= '.1'; // How many different sources this visitor has come from 
        $utmcc .= '.utmcsr%3D(direct)%7Cutmccn%3D(direct)%7Cutmcmd%3D(none)'; // How we accessed to the page the first time
        $utmcc .= '%3B%2B__utmv%3D'; // A user-defined variable (here, the app name)
        $utmcc .= $domainHash; // domain hash
        $utmcc .= '.'.urlencode($app);
        $utmcc .= '%3B';
        
        // Construct the request URL
        $gaUrl = 'http://www.google-analytics.com/__utm.gif?';
        $gaUrl .= 'utmwv='.$utmwv; // GA version
        $gaUrl .= '&utmn='.$utmn; // Random request number
        $gaUrl .= '&utmhn='.$utmhn;
        $gaUrl .= '&utmcs='.$utmcs;
        $gaUrl .= '&utmsr=1600x1200';
        $gaUrl .= '&utmsc=24-bit';
        $gaUrl .= '&utmul=fr';
        $gaUrl .= '&utmje=1';
        $gaUrl .= '&utmfl=10.0%20r12';
        $gaUrl .= '&utmdt='.$utmdt;
        $gaUrl .= '&utmhid='.$utmhid;
        $gaUrl .= '&utmr='.$utmr;
        $gaUrl .= '&utmp='.$utmp;
        $gaUrl .= '&utmac='.$utmac; // The GA profile ID
        $gaUrl .= '&utmcc='.$utmcc;
        
        exec("curl '$gaUrl' 1>/dev/null 2>&1 &");
    }
    
    /**
     * Collect statistics for the given bank path in a MySQL database (added 23/03/09)
     *
     * @param app The name of the app using this db
     * @param path The path of the bank asked
     */
    static private function sendSQLStats($app, $path) {
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=test', 'biomaj', 'BioMaj!');
            
            $req = $pdo->prepare('SELECT id FROM db_stats WHERE db_path = ? AND program = ? AND MONTH(month) = MONTH(CURRENT_DATE) AND YEAR(month) = YEAR(CURRENT_DATE)');
            $req->bindValue(1, $path);
            $req->bindValue(2, $app);
            
            $updateId = 0;
            
            if ($req->execute()) {
                if ($row = $req->fetch())
                    $updateId = $row['id'];
            }

            $req->closeCursor();
            
            if ($updateId > 0) {
                $upReq = $pdo->query('UPDATE db_stats SET nb_consult = nb_consult+1 WHERE id = '.$updateId);
                $upReq->closeCursor();
            }
            else {
                $insertReq = $pdo->prepare("INSERT INTO db_stats VALUES ('', DATE(CONCAT(YEAR(CURRENT_DATE),'-',MONTH(CURRENT_DATE),'-01')), ?, ?, 1)");
                $insertReq->bindValue(1, $app);
                $insertReq->bindValue(2, $path);
                $insertReq->execute();
                $insertReq->closeCursor();
            }
            
            $pdo = null;
        }
        catch (PDOException $e) {
            //print "<p>Database error: " . $e->getMessage() . "</p>";
            return; // Exit silently as this is not a critical feature.
        }
    }
}
?>
