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

class Bank {

    protected $name; // Name of the bank
    protected $lastUpdate; // Date and time of the last update of this bank
    protected $type; // Bank's type
    protected $currentRelease; // Version of the current release
    protected $releases = array(); // The list of all available releases (array of BankRelease objects).

    /**
     * Constructs a Bank object.
     * 
     * @param name Name of the bank
     * @param lastupdate Date and time of the last update of this bank
     * @param type Bank's type
     * @param currentRelease Name of the current release
     * @param releases The list of all available releases (array of BankRelease objects).
     */
    public function __construct($name, $lastUpdate, $type, $currentRelease, $releases) {
        $this->name = $name;
        $this->lastUpdate = $lastUpdate;
        $this->type = $type;
        $this->currentRelease = $currentRelease;
        $this->releases = $releases;
    }

    /**
     * Returns the bank name
     *
     * @returns the bank name
     */
    public function getName(){
        return $this->name;
    }

    /**
     * Returns the bank's type
     *
     * @returns the bank's type
     */
    public function getType(){
        return $this->type;
    }

    /**
     * Returns the bank's current release
     *
     * @returns the bank's current release
     */
    public function getCurrentRelease(){
        return $this->currentRelease;
    }

    /**
     * Returns the last update datetime
     *
     * @returns the last update datetime
     */
    public function getLastUpdate(){
        return $this->lastUpdate;
    }

    /**
     * Get the list of available releases including the latest version.
     * 
     * @returns an array: [$version => BankRelease object]
     */
    public function getReleases() {
        return ($this->releases);
    }

    /**
     * Returns the production directory of this bank.
     *
     * @param release Specify a release number. Default is current release.
     * @returns the production directory of the asked release
     */
    public function getDir($release = ""){
        if (($release = "") || (!array_key_exists($release, $this->releases)))
            $release = $this->currentRelease;
            
        if (array_key_exists($release, $this->releases) && !empty($this->releases[$release]))
            return $this->releases[$release]->getRootDir();
        else
            return array();
    }

    /**
     * Returns the directory of a given format of this bank
     *
     * @param format The requested format
     * @param release Specify a release number. Default is current release.
     * @returns the directory of a given format of the asked release
     */
    public function getFormatDir($format, $release = ""){
        if ($format == "")
            $format = "flat";

        return $this->getDir($release)."/".$format;
    }

    /**
     * Returns the flat directory of the this bank
     *
     * @param release Specify a release number. Default is current release.
     * @returns the flat directory of the asked release
     */
    public function getFlatDir($release = ""){
        return $this->getFormatDir("flat", $release);
    }

    /**
     * Returns the list of available formats for this bank
     *
     * @param release Specify a release number. Default is current release.
     * @returns the available formats for the asked release (array of strings)
     */
    public function getFormats($release = ""){
        if (($release = "") || (!array_key_exists($release, $this->releases)))
            $release = $this->currentRelease;
        if (array_key_exists($release, $this->releases) && !empty($this->releases[$release]))
            return $this->releases[$release]->getFormats();
        else
            return array();
    }

    /**
     * Returns the list of sections for each format
     *
     * @param release Specify a release number. Default is current release.
     * @returns the available formats and their sections for the asked release (array('fasta' => array('Section1' => array('/db/..../xx.fasta'))))
     */
    public function getFormatSections($release = ""){
        if (($release = "") || (!array_key_exists($release, $this->releases)))
            $release = $this->currentRelease;
        
        if (array_key_exists($release, $this->releases) && !empty($this->releases[$release]))
            return $this->releases[$release]->getFormatSections();
        else
            return array();
    }

    /**
     * Tells if the bank is of the given type (searches in subtypes)
     *
     * @param type the requested type
     * @returns true if the bank is of the given type, false otherwise.
     */
    public function isOfType($type) {
        return (strpos($this->type, $type) !== FALSE);
    }
    
    /**
     * Tells if the bank is of nucleic type (searches in subtypes)
     *
     * @returns true if the bank is nucleic, false otherwise.
     */
    public function isNucleic() {
        return $this->isOfType('nucleic');
    }

    /**
     * Tells if the bank is of proteic type (searches in subtypes)
     *
     * @returns true if the bank is proteic, false otherwise.
     */
    public function isProteic() {
        return $this->isOfType('proteic');
    }

    /**
     * Tells if the bank is of genomic type (searches in subtypes)
     *
     * @returns true if the bank is genomic, false otherwise.
     */
    public function isGenomic() {
        return $this->isOfType('genome');
    }
}

?>
