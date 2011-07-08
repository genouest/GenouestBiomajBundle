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

class BankRelease
{

    protected $name; // Release name
    protected $rootDir; // The root directory of this version
    protected $isCurrent; // True if this release is the current one, false otherwise.
    protected $formats; // List of available formats (array("blast", "fasta"))
    protected $formatSections; // List of sections for each format (array('fasta' => array('Section1' => array('/db/..../xx.fasta'))))

    /**
     * Constructs a BankRelease object.
     * 
     * @param name Name of the release
     * @param rootDir The root directory of this version
     * @param formats List of available formats (array)
     * @param formatSections List of sections for each format (array('fasta' => array('Section1' => array('/db/..../xx.fasta'))))
     * @param isCurrent Is this release the current one?
     */
    public function __construct($name, $rootDir, $formats, $formatSections, $isCurrent){
        $this->name = $name;
        $this->rootDir = $rootDir;
        $this->formats = $formats;
        $this->formatSections = $formatSections;
        $this->isCurrent = $isCurrent;
    }

    /**
     * Returns the release name
     *
     * @returns the release name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns the root directory of the release
     *
     * @returns the root directory of the release
     */
    public function getRootDir() {
        return $this->rootDir;
    }
    
    /**
     * Returns true if this release is the current one, false otherwise.
     *
     * @returns true if this release is the current one, false otherwise.
     */
    public function isCurrent() {
        return $this->isCurrent;
    }

    /**
     * Returns the flat directory of the release
     *
     * @returns the flat directory of the release
     */
    public function getFlatDir() {
        return $this->getFormatDir("flat");
    }

    /**
     * Returns the directory of a given format of the release
     *
     * @param format The requested format
     * @returns the directory of a given format of the release
     */
    public function getFormatDir($format) {
        if ($format == "")
            $format = "flat";

        return $this->getRootDir()."/".$format;
    }

    /**
     * Returns the list of available formats for this release
     *
     * @returns the available formats (array)
     */
    public function getFormats() {
        return $this->formats;
    }

    /**
     * Returns the list of sections for each format
     *
     * @returns the available formats and their sections (array('fasta' => array('Section1' => array('/db/..../xx.fasta'))))
     */
    public function getFormatSections($filterAll = false) {
        if ($filterAll) {
            $filtered = array();
            foreach($this->formatSections as $format => $sections){
                $filteredFormat = array();
                foreach($sections as $section => $path){
                    if (strpos($section, 'All') === FALSE){
                        $filteredFormat[$section] = $path;
                    }
                }
                $filtered[$formats] = $filteredFormat;
            }
            return $filtered;
        }
        
        return $this->formatSections;
    }
}

?>
