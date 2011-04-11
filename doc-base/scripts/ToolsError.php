<?php
/**
 * Class file for checking errors
 */

class ToolsError
{

    /**
     * EN content of the file.
     *
     * @var string
     */
    private $en_content;

    /**
     * LANG content of the file.
     *
     * @var string
     */
    private $lang_content;

    /**
     * LANG of the checked file .
     *
     * @var string
     */
    private $lang;

    /**
     * Maitainer of the checked file.
     *
     * @var string
     */
    private $maintainer;

    /**
     * The path of the checked file.
     *
     * @var string
     */
    private $filePath;

    /**
     * The name of the checked file.
     *
     * @var string
     */
    private $fileName;

    /**
     * The stack of errors
     *
     * @var string
     */
    private $errorStack;

    /**
     * Initialise the check
     *
     * @param resource $db Database connexion
     */
    function __construct()
    {
        $this->errorStack = array();
    }

    /**
     * Set parameters to perform a check
     *
     * @param string $en_content
     * @param string $lang_content
     * @param string $lang
     */
    function setParams($en_content, $lang_content, $lang)
    {
        $this->lang_content = preg_replace('/<!--(.*?)?-->/s', '', $lang_content);
        $this->en_content   = preg_replace('/<!--(.*?)?-->/s', '', $en_content);
        $this->lang         = $lang;
    }

    /**
     * Clear all error from the stack
     *
     */
    function clearErrors() {
        return $this->errorStack = array();
    }

    /**
     * Add a new error into the stack
     *
     * @param array $error The new error as an array
     *
     */
    function addError($error) {
        if (is_array($error)) { array_push($this->errorStack, $error); }
    }

    function getErrors() {
        return $this->errorStack;
    }

    /**
     * Run all errors checks
     *
     */
    function run()
    {
        // Check Error specific to EN files
        if( $this->lang == 'en' )
        {
            //$this->checkAcronym(); Disable acronym check for EN document
            $this->missingInitializer();
            $this->spaceOrPeriodRefpurposeTag($this->lang);
            $this->tabCharacterInDocument($this->lang);
            $this->documentNotUTF8($this->lang);
        }

        // Check Error specific to LANG files
        else
        {
            $this->attributAppendixTag();
            $this->attributBookTag();
            $this->attributChapterTag();
            $this->attributLinkTag();
            $this->attributXrefTag();
            $this->attributPrefaceTag();
            $this->attributQandaentryTag();
            $this->attributRefsec1Tag();
            $this->attributRefentryTag();
            $this->attributReferenceTag();
            $this->attributSect1Tag();
            $this->attributSectionTag();
            $this->attributVarlistentryTag();
            $this->classsynopsis();
            $this->methodsynopsis();
            $this->nbCdataTag();
            $this->nbElInTable();
            $this->nbMemberInSeeAlso();
            $this->nbTag();
            $this->spaceOrPeriodRefpurposeTag($this->lang);
            $this->tabCharacterInDocument($this->lang);
            $this->documentNotUTF8($this->lang);
        }

    }

    /**
     * Check all acronyms
     * Add an entry into the error's stack if an acronym is found without <acronym> tag
     *
     */
    function checkAcronym()
    {
        // Get acronyms
        $acronyms = EntitiesAcronymsFetcher::getInstance()->getAcronyms();

        for( $i=0; $i < count($acronyms); $i++ ) {

            $match = array();
            $acronym = $acronyms[$i]['items'];
            preg_match_all("/\s($acronym)\s/si", $this->en_content, $match);

            for( $j=0; $j < count($match[1]); $j++ ) {

                $this->addError(array(
                        'value_en'   => $match[1][$j],
                        'value_lang' => '',
                        'type'       => 'acronym'
                ));

            }
        }
    }

    /**
     * Check attributs in chapter tag
     * Add an entry into the error's stack if an error is found
     *
     */
    function attributChapterTag()
    {

        $reg = '/<chapter\s*?xml:id="(.*?)"\s*?(xmlns="(.*?)")?\s*?(xmlns:xlink="(.*?)"\s*?)?(version="(.*?)"\s*?)?>/s';

        $en_chapter = array();
        $match = array();
        if (preg_match_all($reg, $this->en_content, $match)) {
            $en_chapter['xmlid'] = $match[1];
            $en_chapter['xmlns'] = $match[3];
            $en_chapter['xmlnsxlink'] = $match[5];
            $en_chapter['version'] = $match[7];
        }

        $lang_chapter = array();
        $match = array();
        if (preg_match_all($reg, $this->lang_content, $match)) {
            $lang_chapter['xmlid'] = $match[1];
            $lang_chapter['xmlns'] = $match[3];
            $lang_chapter['xmlnsxlink'] = $match[5];
            $lang_chapter['version'] = $match[7];
        }

        $properties = array(
        'xmlid'      => 'XmlId',
        'xmlns'      => 'XmlNs',
        'xmlnsxlink' => 'XmlXlink',
        'version'    => 'Version'
        );

        foreach ($properties as $property => $label) {

            if( !isset($en_chapter[$property]) ) {
                $en_chapter[$property] = '';
            }

            if( !isset($en_chapter[$property]) ) {
                $en_chapter[$property] = '';
            }

            for ($i = 0; $i < count($en_chapter[$property]); $i++) {
                if (!isset($en_chapter[$property][$i])) {
                    $en_chapter[$property][$i] = '';
                }
                if (!isset($lang_chapter[$property][$i])) {
                    $lang_chapter[$property][$i] = '';
                }

                if ($en_chapter[$property][$i] != $lang_chapter[$property][$i] ) {
                    $this->addError(array(
                        'value_en'   => $en_chapter[$property][$i],
                        'value_lang' => $lang_chapter[$property][$i],
                        'type'       => 'attribut' . $label . 'Chapter'
                    ));

                }
            }
        }

    }

    /**
     * Check attributs in appendix tag
     * Add an entry into the error's stack if an error is found
     *
     */
    function attributAppendixTag()
    {

        $reg = '/<appendix\s*?xml:id="(.*?)"\s*?(xmlns="(.*?)")?\s*?(xmlns:xlink="(.*?)"\s*?)?>/s';

        $match = $en_appendix = array();
        preg_match_all($reg, $this->en_content, $match);
        $en_appendix["xmlid"] = $match[1];
        $en_appendix["xmlns"] = $match[3];
        $en_appendix["xmlnsxlink"] = $match[5];

        $match = $lang_appendix = array();
        preg_match_all($reg, $this->lang_content, $match);
        $lang_appendix['xmlid'] = $match[1];
        $lang_appendix['xmlns'] = $match[3];
        $lang_appendix['xmlnsxlink'] = $match[5];


        $properties = array(
        'xmlid' => 'XmlId',
        'xmlns' => 'XmlNs',
        'xmlnsxlink' => 'XmlXlink',
        );

        foreach ($properties as $property => $label) {

            for ($i = 0; $i < count($en_appendix[$property]); $i++) {

                if (!isset($en_appendix[$property][$i])) { $en_appendix[$property][$i] = ''; }
                if (!isset($lang_appendix[$property][$i])) { $lang_appendix[$property][$i] = ''; }

                if ($en_appendix[$property][$i] != $lang_appendix[$property][$i] ) {
                    $this->addError(array(
                        'value_en'   => $en_appendix[$property][$i],
                        'value_lang' => $lang_appendix[$property][$i],
                        'type'       => 'attribut' . $label . 'Appendix',
                    ));

                }
            }
        }
    }

    /**
     * Check attributs in qandaentry tag
     * Add an entry into the error's stack if an error is found
     *
     */
    function attributQandaentryTag()
    {

        $reg = '/<qandaentry\s*?xml:id="(.*?)"\s*?>/s';

        $match = $en_qandaentry = array();
        if (preg_match_all($reg, $this->en_content, $match)) {
            $en_qandaentry = $match[1];
        }

        $match = $lang_qandaentry = array();
        if (preg_match_all($reg, $this->lang_content, $match)) {
            $lang_qandaentry = $match[1];
        }

        for ($i = 0; $i < count($en_qandaentry); $i++) {

            if (!isset($en_qandaentry[$i])) {
                $en_qandaentry[$i] = '';
            }
            if (!isset($lang_qandaentry[$i])) {
                $lang_qandaentry[$i] = '';
            }

            if ($en_qandaentry[$i] != $lang_qandaentry[$i] ) {
                $this->addError(array(
                    'value_en'   => $en_qandaentry[$i],
                    'value_lang' => $lang_qandaentry[$i],
                    'type'       => 'attributXmlIdQandaentry'
                ));

            }
        }
    }

    /**
     * Check attributs in Xref tag
     * Add an entry into the error's stack if an error is found
     *
     */
    function attributXrefTag()
    {

        $reg = '/<xref\s*?linkend=\s*?"(.[^"]*?)"\s*\/>/s';

        $match = $en_xref = array();
        preg_match_all($reg, $this->en_content, $match);
        $en_xref = $match[1];

        $match = $lang_xref = array();
        preg_match_all($reg, $this->lang_content, $match);
        $lang_xref = $match[1];

        for ($i = 0; $i < count($en_xref); $i++) {

            if (!isset($lang_xref[$i])) { $lang_xref[$i] = ''; }

            if( !in_array($en_xref[$i], $lang_xref) || $lang_xref[$i] == '' ) {
                $this->addError(array(
                    "value_en"   => $en_xref[$i],
                    "value_lang" => $lang_xref[$i],
                    "type"       => "attributLinkendXref"
                ));
            }
        }

        if( count($en_xref) < count($lang_xref) ) {
                $this->addError(array(
                    "value_en"   => count($en_xref),
                    "value_lang" => count($lang_xref),
                    "type"       => "NbXref"
                ));
        }
    }

    /**
     * Check attributs in Link tag
     * Add an entry into the error's stack if an error is found
     *
     */
    function attributLinkTag()
    {

        $reg = '/<link\s*?xlink:href=\s*?"(.[^"]*?)"(\s*|\s*?\/)?>/s';

        $match = $en_xlink = array();
        preg_match_all($reg, $this->en_content, $match);
        $en_xlink = $match[1];

        $match = $lang_xlink = array();
        preg_match_all($reg, $this->lang_content, $match);
        $lang_xlink = $match[1];

        for ($i = 0; $i < count($en_xlink); $i++) {

            if( !in_array($en_xlink[$i], $lang_xlink) ) {

                if (!isset($lang_xlink[$i])) { $lang_xlink[$i] = ''; }

                $this->addError(array(
                    "value_en"   => $en_xlink[$i],
                    "value_lang" => $lang_xlink[$i],
                    "type"       => "attributXlinkLink"
                ));

            }

        }

        if( count($en_xlink) < count($lang_xlink) ) {

                $this->addError(array(
                    "value_en"   => count($en_xlink),
                    "value_lang" => count($lang_xlink),
                    "type"       => "NbXlinkLink"
                ));

        }


        $reg = '/<link\s*?linkend=("|\')(.*?)("|\')\s*?>/s';

        $en_linkend = array();
        $match = array();
        if (preg_match_all($reg, $this->en_content, $match)) {
            $en_linkend = $match[2];
        }

        $lang_linkend = array();
        $match = array();
        if (preg_match_all($reg, $this->lang_content, $match)) {
            $lang_linkend = $match[2];
        }

        for ($i = 0; $i <count($en_linkend); $i++) {
            if (!isset($lang_linkend[$i])) {
                $lang_linkend[$i] = '';
            }
            if ($en_linkend[$i] != $lang_linkend[$i]) {
                $this->addError(array(
                    "value_en"   => $en_linkend[$i],
                    "value_lang" => $lang_linkend[$i],
                    "type"       => "attributLinkendLink"
                ));

            }
        }
    }

    /**
     * Check attributs in Sect1 tag
     * Add an entry into the error's stack if an error is found
     *
     */
    function attributSect1Tag()
    {

        $reg = '/<sect1\s*?xml:id="(.*?)"\s*?(xmlns="(.*?)")?\s*?(xmlns:xlink="(.*?)"\s*?)?>/s';

        $en_sect1 = array();
        $match = array();
        preg_match_all($reg, $this->en_content, $match);
        $en_sect1["xmlid"] = $match[1];
        $en_sect1["xmlns"] = $match[3];
        $en_sect1["xmlnsxlink"] = $match[5];

        $lang_sect1 = array();
        $match = array();
        preg_match_all($reg, $this->lang_content, $match);
        $lang_sect1["xmlid"] = $match[1];
        $lang_sect1["xmlns"] = $match[3];
        $lang_sect1["xmlnsxlink"] = $match[5];

        for ($i = 0; $i < count($en_sect1["xmlid"]); $i++) {
            if (isset($lang_sect1["xmlid"][$i]) && $en_sect1["xmlid"][$i] != $lang_sect1["xmlid"][$i] ) {
                $this->addError(array(
                    "value_en"   => $en_sect1["xmlid"][$i],
                    "value_lang" => $lang_sect1["xmlid"][$i],
                    "type"       => "attributXmlIdSect1"
                ));

            }
        }
        for ($i = 0; $i < count($en_sect1["xmlns"]); $i++) {
            if (isset($lang_sect1["xmlns"][$i]) && $en_sect1["xmlns"][$i] != $lang_sect1["xmlns"][$i] ) {
                $this->addError(array(
                    "value_en"   => $en_sect1["xmlns"][$i],
                    "value_lang" => $lang_sect1["xmlns"][$i],
                    "type"       => "attributXmlNsSect1"
                ));

            }
        }
        for ($i = 0; $i < count($en_sect1["xmlnsxlink"]); $i++) {
            if (isset($lang_sect1["xmlnsxlink"][$i]) && $en_sect1["xmlnsxlink"][$i] != $lang_sect1["xmlnsxlink"][$i] ) {
                $this->addError(array(
                    "value_en"   => $en_sect1["xmlnsxlink"][$i],
                    "value_lang" => $lang_sect1["xmlnsxlink"][$i],
                    "type"       => "attributXmlNsXlinkSect1"
                ));

            }
        }
    }

    /**
     * Check attributs in Book tag
     * Add an entry into the error's stack if an error is found
     *
     */
    function attributBookTag()
    {

        $reg = '/<book\s*?xml:id="(.*?)"\s*?(xmlns="(.*?)")?\s*?(xmlns:xlink="(.*?)"\s*?)?>/s';

        $en_book = $match = array();
        preg_match_all($reg, $this->en_content, $match);
        $en_book["xmlid"] = $match[1];
        $en_book["xmlns"] = $match[3];
        $en_book["xmlnsxlink"] = $match[5];

        $lang_book = $match = array();
        preg_match_all($reg, $this->lang_content, $match);
        $lang_book["xmlid"] = $match[1];
        $lang_book["xmlns"] = $match[3];
        $lang_book["xmlnsxlink"] = $match[5];

        for ($i = 0; $i < count($en_book["xmlid"]); $i++) {
            if ($en_book["xmlid"][$i] != $lang_book["xmlid"][$i] ) {
                $this->addError(array(
                    "value_en"   => $en_book["xmlid"][$i],
                    "value_lang" => $lang_book["xmlid"][$i],
                    "type"       => "attributXmlIdBook"
                ));

            }
        }

        for ($i = 0; $i < count($en_book["xmlns"]); $i++) {
            if ($en_book["xmlns"][$i] != $lang_book["xmlns"][$i] ) {
                $this->addError(array(
                    "value_en"   => $en_book["xmlns"][$i],
                    "value_lang" => $lang_book["xmlns"][$i],
                    "type"       => "attributXmlNsBook"
                ));

            }
        }

        for ($i = 0; $i < count($en_book["xmlnsxlink"]); $i++) {
            if ($en_book["xmlnsxlink"][$i] != $lang_book["xmlnsxlink"][$i] ) {
                $this->addError(array(
                    "value_en"   => $en_book["xmlnsxlink"][$i],
                    "value_lang" => $lang_book["xmlnsxlink"][$i],
                    "type"       => "attributXmlXlinkBook"
                ));
            }
        }
    }

    /**
     * Check attributs in Preface tag
     * Add an entry into the error's stack if an error is found
     *
     */
    function attributPrefaceTag()
    {

        $reg = '/<preface\s*?xml:id="(.*?)"\s*?(xmlns="(.*?)")?\s*?(xmlns:xlink="(.*?)"\s*?)?>/s';

        $match = $en_preface = array();
        if (preg_match_all($reg, $this->en_content, $match)) {
            $en_preface['xmlid']      = $match[1];
            $en_preface['xmlns']      = $match[3];
            $en_preface['xmlnsxlink'] = $match[5];
        }

        $match = $lang_preface = array();
        if (preg_match_all($reg, $this->lang_content, $match)) {
            $lang_preface['xmlid']      = $match[1];
            $lang_preface['xmlns']      = $match[3];
            $lang_preface['xmlnsxlink'] = $match[5];
        }

        $properties = array(
            'xmlid'      => 'XmlId',
            'xmlns'      => 'XmlNs',
            'xmlnsxlink' => 'XmlNsXlink'
        );
        foreach ($properties as $property => $label) {

            if( !isset($en_preface[$property]) ) {
                $en_preface[$property] = '';
            }

            if( !isset($lang_preface[$property]) ) {
                $lang_preface[$property] = '';
            }

            for ($i = 0; $i < count($en_preface[$property]); $i++) {

                if( !isset($en_preface[$property][$i]) ) {
                    $en_preface[$property][$i] = '';
                }

                if( !isset($lang_preface[$property][$i]) ) {
                    $lang_preface[$property][$i] = '';
                }

                if ($en_preface[$property][$i] != $lang_preface[$property][$i] ) {
                    $this->addError(array(
                        'value_en' => $en_preface[$property][$i],
                        'value_lang' => $lang_preface[$property][$i],
                        'type' => 'attribut' . $label . 'Preface'
                    ));

                }
            }
        }
    }

    /**
     * Check attributs in Section tag
     * Add an entry into the error's stack if an error is found
     *
     */
    function attributSectionTag()
    {

        $reg = '/<section\s*?xml:id=("|\')(.*?)("|\')\s*?(xmlns=("|\')(.*?)("|\'))?\s*?(xmlns:xlink=("|\')(.*?)("|\')\s*?)?>/s';

        $en_section = array();
        $match = array();
        if (preg_match_all($reg, $this->en_content, $match)) {
            $en_section["xmlid"] = $match[2];
            $en_section["xmlns"] = $match[6];
            $en_section["xmlnsxlink"] = $match[10];
        }

        $lang_section = array();
        $match = array();
        if (preg_match_all($reg, $this->lang_content, $match)) {
            $lang_section["xmlid"] = $match[2];
            $lang_section["xmlns"] = $match[6];
            $lang_section["xmlnsxlink"] = $match[10];
        }

        $properties = array(
            'xmlid'      => 'XmlId',
            'xmlns'      => 'XmlNs',
            'xmlnsxlink' => 'XmlNsXlink'
        );

        foreach ($properties as $property => $label) {

            if( !isset($en_section[$property]) ) {
                $en_section[$property] = '';
            }

            if( !isset($lang_section[$property]) ) {
                $lang_section[$property] = '';
            }

            for ($i = 0; $i < count($en_section[$property]); $i++) {

                if( !isset($en_section[$property][$i]) ) {
                    $en_section[$property][$i] = '';
                }

                if( !isset($lang_section[$property][$i]) ) {
                    $lang_section[$property][$i] = '';
                }

                if ($en_section[$property][$i] != $lang_section[$property][$i] ) {
                    $this->addError(array(
                        'value_en'   => $en_section[$property][$i],
                        'value_lang' => $lang_section[$property][$i],
                        'type'       => 'attribut' . $label . 'Section'
                    ));

                }
            }

        }
    }

    /**
     * Check attributs in Varlistentry tag
     * Add an entry into the error's stack if an error is found
     *
     */
    function attributVarlistentryTag()
    {

        $reg = '/<varlistentry\s*?xml:id=("|\')(.*?)("|\')\s*?>/s';

        $match = $en_varlistentry = array();
        if (preg_match_all($reg, $this->en_content, $match)) {
            $en_varlistentry = $match[2];
        }

        $match = $lang_varlistentry = array();
        if (preg_match_all($reg, $this->lang_content, $match)) {
            $lang_varlistentry = $match[2];
        }

        for ($i = 0; $i < count($en_varlistentry); $i++) {

            if (!isset($en_varlistentry[$i]) )   {
                $en_varlistentry[$i]   = '';
            }
            if (!isset($lang_varlistentry[$i])) {
                $lang_varlistentry[$i] = '';
            }

            if ($en_varlistentry[$i] != $lang_varlistentry[$i] ) {
                $this->addError(array(
                "value_en"   => $en_varlistentry[$i],
                "value_lang" => $lang_varlistentry[$i],
                "type"       => "attributXmlIdVarlistentry"
                ));
            }
        }
    }

    /**
     * Check attributs in Reference tag
     * Add an entry into the error's stack if an error is found
     *
     */
    function attributReferenceTag()
    {

        $reg = '/<reference\s*?xml:id="(.*?)"\s*?xmlns="(.*?)"\s*?(xmlns:xlink="(.*?)"\s*?)?>/s';

        $en_reference = array();
        $match = array();
        preg_match_all($reg, $this->en_content, $match);
        $en_reference["xmlid"] = $match[1];
        $en_reference["xmlns"] = $match[2];
        $en_reference["xmlnsxlink"] = $match[4];

        $lang_reference = array();
        $match = array();
        preg_match_all($reg, $this->lang_content, $match);
        $lang_reference["xmlid"] = $match[1];
        $lang_reference["xmlns"] = $match[2];
        $lang_reference["xmlnsxlink"] = $match[4];

        for ($i = 0; $i < count($en_reference["xmlid"]); $i++) {

            if (!isset($en_reference["xmlid"][$i]) )   { $en_reference["xmlid"][$i]   = ''; }
            if (!isset($lang_reference["xmlid"][$i])) { $lang_reference["xmlid"][$i] = ''; }

            if ($en_reference["xmlid"][$i] != $lang_reference["xmlid"][$i] ) {
                $this->addError(array(
                    "value_en"   => $en_reference["xmlid"][$i],
                    "value_lang" => $lang_reference["xmlid"][$i],
                    "type"       => "attributXmlIDReference"
                ));

            }
        }

        for ($i = 0; $i < count($en_reference["xmlns"]); $i++) {

            if (!isset($en_reference["xmlns"][$i]) )   { $en_reference["xmlns"][$i]   = ''; }
            if (!isset($lang_reference["xmlns"][$i])) { $lang_reference["xmlns"][$i] = ''; }

            if ($en_reference["xmlns"][$i] != $lang_reference["xmlns"][$i] ) {
                $this->addError(array(
                    "value_en"   => $en_reference["xmlns"][$i],
                    "value_lang" => $lang_reference["xmlns"][$i],
                    "type"       => "attributXmlNsReference"
                ));
            }
        }

        for ($i = 0; $i < count($en_reference["xmlnsxlink"]); $i++) {

            if (!isset($en_reference["xmlnsxlink"][$i]) )   { $en_reference["xmlnsxlink"][$i]   = ''; }
            if (!isset($lang_reference["xmlnsxlink"][$i])) { $lang_reference["xmlnsxlink"][$i] = ''; }

            if ($en_reference["xmlnsxlink"][$i] != $lang_reference["xmlnsxlink"][$i] ) {
                $this->addError(array(
                    "value_en"   => $en_reference["xmlnsxlink"][$i],
                    "value_lang" => $lang_reference["xmlnsxlink"][$i],
                    "type"       => "attributXmlNsXlinkReference"
                ));
            }
        }
    }

    /**
     * Check attributs in Refentry tag
     * Add an entry into the error's stack if an error is found
     *
     */
    function attributRefentryTag()
    {

        $reg = '/<refentry\s*?xml:id="(.*?)"\s*?xmlns="(.*?)"\s*?(xmlns:xlink="(.*?)"\s*?)?>/s';

        $en_refentry = array();
        $match = array();
        preg_match_all($reg, $this->en_content, $match);
        $en_refentry["xmlid"]      = $match[1];
        $en_refentry["xmlns"]      = $match[2];
        $en_refentry["xmlnsxlink"] = $match[4];

        $lang_refentry = array();
        $match = array();
        preg_match_all($reg, $this->lang_content, $match);
        $lang_refentry["xmlid"] = $match[1];
        $lang_refentry["xmlns"] = $match[2];
        $lang_refentry["xmlnsxlink"] = $match[4];

        for ($i = 0; $i < count($en_refentry["xmlid"]); $i++) {

            if (!isset($en_refentry["xmlid"][$i])) { $en_refentry["xmlid"][$i] = ''; }
            if (!isset($lang_refentry["xmlid"][$i])) { $lang_refentry["xmlid"][$i] = ''; }

            if ($en_refentry["xmlid"][$i] != $lang_refentry["xmlid"][$i] ) {
                $this->addError(array(
                    "value_en"   => $en_refentry["xmlid"][$i],
                    "value_lang" => $lang_refentry["xmlid"][$i],
                    "type"       => "attributXmlIdRefentry"
                ));
            }
        }

        for ($i = 0; $i < count($en_refentry["xmlns"]); $i++) {

            if (!isset($en_refentry["xmlns"][$i])) { $en_refentry["xmlns"][$i] = ''; }
            if (!isset($lang_refentry["xmlns"][$i])) { $lang_refentry["xmlns"][$i] = ''; }

            if ($en_refentry["xmlns"][$i] != $lang_refentry["xmlns"][$i] ) {
                $this->addError(array(
                    "value_en"   => $en_refentry["xmlns"][$i],
                    "value_lang" => $lang_refentry["xmlns"][$i],
                    "type"       => "attributXmlNsRefentry"
                ));

            }
        }

        for ($i = 0; $i < count($en_refentry["xmlnsxlink"]); $i++) {

            if (!isset($en_refentry["xmlnsxlink"][$i])) { $en_refentry["xmlnsxlink"][$i] = ''; }
            if (!isset($lang_refentry["xmlnsxlink"][$i])) { $lang_refentry["xmlnsxlink"][$i] = ''; }

            if ($en_refentry["xmlnsxlink"][$i] != $lang_refentry["xmlnsxlink"][$i] ) {
                $this->addError(array(
                    "value_en"   => $en_refentry["xmlnsxlink"][$i],
                    "value_lang" => $lang_refentry["xmlnsxlink"][$i],
                    "type"       => "attributXmlNsXlinkRefentry"
                ));

            }
        }
    }

    /**
     * Check attributs in Refsec1 tag
     * Add an entry into the error's stack if an error is found
     *
     */
    function attributRefsec1Tag()
    {

        $reg = '/<refsect1\s*?role="(.*?)"\s*?>/s';

        $en_refsect1 = array();
        $match = array();
        preg_match_all($reg, $this->en_content, $match);
        $en_refsect1 = $match[1];

        $lang_refsect1 = array();
        $match = array();
        preg_match_all($reg, $this->lang_content, $match);
        $lang_refsect1 = $match[1];

        for ($i = 0; $i < count($en_refsect1); $i++) {
            if (isset($lang_refsect1[$i]) && $en_refsect1[$i] != $lang_refsect1[$i] ) {
                $this->addError(array(
                    "value_en"   => $en_refsect1[$i],
                    "value_lang" => $lang_refsect1[$i],
                    "type"       => "attributRefsect1"
                ));
            }
        }
    }
    /**
     * Check Space or period at the end of Refpurpose tag
     * Add an entry into the error's stack if an error is found
     *
     */
    function spaceOrPeriodRefpurposeTag($lang)
    {

        $reg = '/<refpurpose>.*([^A-Za-z1-9 ])<\/refpurpose>/s';

        if( $lang == 'en' ) {
            $content = $this->en_content;
        } else {
            $content = $this->lang_content;
        }

        $match = array();
        preg_match_all($reg, $this->lang_content, $match);

        if (isset($match[1][0])) {

            if ($match[1][0] == '.')  {
                $this->addError(array(
                    'value_en'   => 'N/A',
                    'value_lang' => 'N/A',
                    'type'       => 'spaceOrPeriodRefpurpose'
                ));
            }
        }
    }

    /**
     * Check for missing <initializer> tag
     * Add an entry into the error's stack if a missing <initializer> tag was found
     *
     * This is the convertion of the following script :
     * http://svn.php.net/viewvc/phpdoc/doc-base/trunk/scripts/check-missing-initializers.php?view=markup
     *
     * This method is only available for EN files.
     */
    function missingInitializer()
    {
	$matches = array();
	preg_match_all('@<methodparam choice="opt"><type>(.*)</type><parameter>(.*)</parameter>(.*)</methodparam>@', $this->en_content, $matches);

	if ( !empty($matches))
	{
            $i=0;
	    foreach ($matches[3] as $match) {
		if (empty($match) || (false === strpos($match, '<initializer>'))) {
		    $this->addError(array(
			'value_en'   => $matches[2][$i],
			'value_lang' => 'N/A',
			'type'       => 'missingInitializer'
		    ));
		}
                $i++;
	    }
	}
    }

    /**
     * Check for tabs into the document
     * Add an entry into the error's stack if a tab is found in the document
     *
     */
    function tabCharacterInDocument($lang)
    {

        if( $lang == 'en' ) {
            $content = $this->en_content;
        } else {
            $content = $this->lang_content;
        }

        if ( strstr($content, "\t")) {

            $this->addError(array(
                'value_en'   => 'N/A',
                'value_lang' => 'N/A',
                'type'       => 'tabCharacterInDocument'
            ));
        }
    }

    /**
     * Check the document encoding
     * Add an entry into the error's stack if the document is not UTF-8
     *
     */
    function documentNotUTF8($lang)
    {
        if( $lang == 'en' ) {
            $content = $this->en_content;
        } else {
            $content = $this->lang_content;
        }

        $matches = array();
        preg_match('!<\?xml(.+)\s?encoding=("|\')(.*)("|\')\s?\?>!U', $content, $matches);

        if ( !isset($matches[3]) || strtoupper($matches[3]) != 'UTF-8') {

            $this->addError(array(
                'value_en'   => 'N/A',
                'value_lang' => 'N/A',
                'type'       => 'documentNotUTF8'
            ));
        }
    }

    /**
     * Check Nb <![CDATA tag
     * Add an entry into the error's stack if an error is found
     *
     */
    function nbCdataTag()
    {

        $reg = '/<!\[CDATA\[(.*?)\]\]>/s';

        $en_cdataSection = 0;
        $match = array();
        if (preg_match_all($reg, $this->en_content, $match)) {
            $en_cdataSection = count($match[1]);
        }

        $lang_cdataSection = 0;
        $match = array();
        if (preg_match_all($reg, $this->lang_content, $match)) {
            $lang_cdataSection = count($match[1]);
        }

        if ($en_cdataSection != $lang_cdataSection ) {
            $this->addError(array(
                "value_en"   => $en_cdataSection,
                "value_lang" => $lang_cdataSection,
                "type"       => "nbCdataTag"
            ));
        }
    }

    /**
     * Check error in <classsynopsis> tag
     * Add an entry into the error's stack if an error is found
     *
     */
    function classsynopsis()
    {

        $reg1 = '/<classsynopsis>(\s.*?)<\/classsynopsis>/s';
        $reg2 = '/<ooclass><classname>(.*?)<\/classname><\/ooclass>/s';
        $reg3 = '/<fieldsynopsis>\s*?<modifier>(.*?)<\/modifier>\s*?<type>(.*?)<\/type>\s*?<varname(.*?)>(.*?)<\/varname>\s*?<initializer>(.*?)<\/initializer>\s*?<\/fieldsynopsis>/s';

        $match = $en_classsynopsis = array();
        preg_match_all($reg1, $this->en_content, $match);

        for ($i = 0; $i < count($match[1]); $i++) {
            $match2 = array();
            preg_match_all($reg2, $match[1][$i], $match2);
            if (!isset($match2[1][0])) { $match2[1][0] = ''; }
            $en_classsynopsis[$i]['ooclass']['classname']['libel'] = $match2[1][0];
            $en_classsynopsis[$i]['ooclass']['classname']['nb'] = count($match2[1]);

            $match2 = array();
            preg_match_all($reg3, $match[1][$i], $match2);
            $en_classsynopsis[$i]['fieldsynopsis']['modifier']            = $match2[1];
            $en_classsynopsis[$i]['fieldsynopsis']['type']                = $match2[2];
            $en_classsynopsis[$i]['fieldsynopsis']['varname']['attr']     = $match2[3];
            $en_classsynopsis[$i]['fieldsynopsis']['varname']['value']    = $match2[4];
            $en_classsynopsis[$i]['fieldsynopsis']['initializer']         = $match2[5];

        }

        $lang_classsynopsis = array();
        $match = array();
        preg_match_all($reg1, $this->lang_content, $match);

        for ($i = 0; $i < count($match[1]); $i++) {
            $match2 = array();
            preg_match_all($reg2, $match[1][$i], $match2);
            if (!isset($match2[1][0])) { $match2[1][0] = ''; }
            $lang_classsynopsis[$i]['ooclass']['classname']['libel'] = $match2[1][0];
            $lang_classsynopsis[$i]['ooclass']['classname']['nb'] = count($match2[1]);

            preg_match_all($reg3, $match[1][$i], $match2);

            $lang_classsynopsis[$i]['fieldsynopsis']['modifier']            = $match2[1];
            $lang_classsynopsis[$i]['fieldsynopsis']['type']                = $match2[2];
            $lang_classsynopsis[$i]['fieldsynopsis']['varname']['attr']     = $match2[3];
            $lang_classsynopsis[$i]['fieldsynopsis']['varname']['value']    = $match2[4];
            $lang_classsynopsis[$i]['fieldsynopsis']['initializer']         = $match2[5];

        }

        // Verification
        for ($i = 0; $i < count($en_classsynopsis); $i++) {

            if (!isset($lang_classsynopsis[$i]['ooclass']['classname']['libel'])) {
                $lang_classsynopsis[$i]['ooclass']['classname']['libel'] = '';
            }

            // ooclass, classname
            if ($en_classsynopsis[$i]['ooclass']['classname']['libel'] != $lang_classsynopsis[$i]['ooclass']['classname']['libel'] ) {
                $this->addError(array(
                    "value_en"   => $en_classsynopsis[$i]['ooclass']['classname']['libel'],
                    "value_lang" => $lang_classsynopsis[$i]['ooclass']['classname']['libel'],
                    "type"       => "errorOoclassClassname"
                ));

            }
            if (!isset($lang_classsynopsis[$i]['ooclass']['classname']['nb'])) { $lang_classsynopsis[$i]['ooclass']['classname']['nb'] = 0; }

            // ooclass, classname
            if ($en_classsynopsis[$i]['ooclass']['classname']['nb'] != $lang_classsynopsis[$i]['ooclass']['classname']['nb'] ) {
                $this->addError(array(
                    "value_en"   => $en_classsynopsis[$i]['ooclass']['classname']['nb'],
                    "value_lang" => $lang_classsynopsis[$i]['ooclass']['classname']['nb'],
                    "type"       => "errorNbOoclassClassname"
                ));

            }


            // fieldsynopsis
            for ($j = 0; $j < count($en_classsynopsis[$i]['fieldsynopsis']['varname']['value']); $j++ ) {

                // fieldsynopsis varname value
                if (!isset($lang_classsynopsis[$i]['fieldsynopsis']['varname']['value'][$j])) {
                    $lang_classsynopsis[$i]['fieldsynopsis']['varname']['value'][$j] = '';
                }
                if ($en_classsynopsis[$i]['fieldsynopsis']['varname']['value'][$j] != $lang_classsynopsis[$i]['fieldsynopsis']['varname']['value'][$j] ) {
                    $this->addError(array(
                        "value_en"   => $en_classsynopsis[$i]['fieldsynopsis']['varname']['value'][$j],
                        "value_lang" => $lang_classsynopsis[$i]['fieldsynopsis']['varname']['value'][$j],
                        "type"       => "errorFieldsynopsisVarnameValue"
                    ));

                }

                // fieldsynopsis varname attr
                if (!isset($lang_classsynopsis[$i]['fieldsynopsis']['varname']['attr'][$j])) {
                    $lang_classsynopsis[$i]['fieldsynopsis']['varname']['attr'][$j] = '';
                }
                if ($en_classsynopsis[$i]['fieldsynopsis']['varname']['attr'][$j] != $lang_classsynopsis[$i]['fieldsynopsis']['varname']['attr'][$j] ) {
                    $this->addError(array(
                        "value_en"   => $en_classsynopsis[$i]['fieldsynopsis']['varname']['attr'][$j],
                        "value_lang" => $lang_classsynopsis[$i]['fieldsynopsis']['varname']['attr'][$j],
                        "type"       => "errorFieldsynopsisVarnameAttr"
                    ));

                }

                // fieldsynopsis type
                if (!isset($lang_classsynopsis[$i]['fieldsynopsis']['type'][$j])) {
                    $lang_classsynopsis[$i]['fieldsynopsis']['type'][$j] = '';
                }
                if ($en_classsynopsis[$i]['fieldsynopsis']['type'][$j] != $lang_classsynopsis[$i]['fieldsynopsis']['type'][$j] ) {
                    $this->addError(array(
                        "value_en"   => $en_classsynopsis[$i]['fieldsynopsis']['type'][$j],
                        "value_lang" => $lang_classsynopsis[$i]['fieldsynopsis']['type'][$j],
                        "type"       => "errorFieldsynopsisType"
                    ));

                }

                // fieldsynopsis modifier
                if (!isset($lang_classsynopsis[$i]['fieldsynopsis']['modifier'][$j])) { $lang_classsynopsis[$i]['fieldsynopsis']['modifier'][$j] = ''; }
                if ($en_classsynopsis[$i]['fieldsynopsis']['modifier'][$j] != $lang_classsynopsis[$i]['fieldsynopsis']['modifier'][$j] ) {
                    $this->addError(array(
                        "value_en"   => $en_classsynopsis[$i]['fieldsynopsis']['modifier'][$j],
                        "value_lang" => $lang_classsynopsis[$i]['fieldsynopsis']['modifier'][$j],
                        "type"       => "errorFieldsynopsisModifier"
                    ));

                }

                // fieldsynopsis initializer
                if (!isset($lang_classsynopsis[$i]['fieldsynopsis']['initializer'][$j])) { $lang_classsynopsis[$i]['fieldsynopsis']['initializer'][$j] = ''; }
                if ($en_classsynopsis[$i]['fieldsynopsis']['initializer'][$j] != $lang_classsynopsis[$i]['fieldsynopsis']['initializer'][$j] ) {
                    $this->addError(array(
                        "value_en"   => $en_classsynopsis[$i]['fieldsynopsis']['initializer'][$j],
                        "value_lang" => $lang_classsynopsis[$i]['fieldsynopsis']['initializer'][$j],
                        "type"       => "errorFieldsynopsisInitializer"
                    ));

                }

            }
        }
    }

    /**
     * Check Nb <*> tag
     * Add an entry into the error's stack if an error is found
     *
     */
    function nbTag()
    {

        // When you add a new tag here, you must add it too into error_type.php files (array => tags)
        $tags = array(
            'abbrev'      => 'Abbrev',
            'acronym'     => 'Acronym',
            'caution'     => 'Caution',
            'command'     => 'Command',
            'chapter'     => 'Chapter',
            'constant'    => 'Constant',
            'emphasis'    => 'Emphasis',
            'filename'    => 'Filename',
            'literal'     => 'Literal',
            'note'        => 'Note',
            'para'        => 'Para',
            'productname' => 'Productname',
            'simpara'     => 'Simpara',
            'tip'         => 'Tip',
            'userinput'   => 'Userinput',
            'varname'     => 'Varname',
            'warning'     => 'Warning'
        );

        foreach ($tags as $tag => $label) {

            $reg = '%<' . $tag . '(\s+[^>]*?)?>.*?</' . $tag . '>%su';

            $nb_en = 0;
            if (preg_match_all($reg, $this->en_content, $en_match)) {
                $nb_en = count($en_match[0]);
            }

            $nb_lang = 0;
            if (preg_match_all($reg, $this->lang_content, $lang_match)) {
                $nb_lang = count($lang_match[0]);
            }

            if ($nb_en != $nb_lang ) {
                $this->addError(array(
                    "value_en"   => $nb_en,
                    "value_lang" => $nb_lang,
                    "type"       => "nb" . $label . "Tag",
                    'additional_en' => $en_match[0],
                    'additional_lang' => $lang_match[0]
                ));
            }

        } // foreach

    }

    /**
     * Check attr in <row> tag
     * Add an entry into the error's stack if an error is found
     *
     */
    function nbElInTable()
    {

        $reg = '/<row(\s.*?)xml:id="(.*?)">/s';

        $en_attrRow = array();
        $lang_attrRow = array();
        $match = array();

        $lang_attrRow['xmlid']='';
        $en_attrRow['xmlid']='';

        if (preg_match_all($reg, $this->en_content, $match)) {
            $en_attrRow['xmlid'] = $match[2];
        }

        $match = array();
        if (preg_match_all($reg, $this->lang_content, $match)) {
            $lang_attrRow['xmlid'] = $match[2];
        }

        for ($i = 0; $i < count($en_attrRow['xmlid']); $i++) {

            if (!isset($en_attrRow['xmlid'][$i])) { $en_attrRow['xmlid'][$i] = ''; }
            if (!isset($lang_attrRow['xmlid'][$i])) { $lang_attrRow['xmlid'][$i] = ''; }

            if ($en_attrRow['xmlid'][$i] != $lang_attrRow['xmlid'][$i] ) {
                $this->addError(array(
                    'value_en'   => $en_attrRow['xmlid'][$i],
                    'value_lang' => $lang_attrRow['xmlid'][$i],
                    'type'       => 'attributXmlIdRow'
                ));

            }
        }

        $tags = array('row', 'thead', 'tbody', 'entry');

        foreach ($tags as $tag) {

            $reg = '/<' . $tag . '>/s';
            $en_tag = 0;
            $match = array();
            if (preg_match_all($reg, $this->en_content, $match)) {
                $en_tag = count($match[0]);
            }

            $lang_tag = 0;
            $match = array();
            if (preg_match_all($reg, $this->lang_content, $match)) {
                $lang_tag = count($match[0]);
            }

            if ($en_tag != $lang_tag) {
                $this->addError(array(
                    'value_en'   => $en_tag,
                    'value_lang' => $lang_tag,
                    'type'       => 'nb' . ucfirst($tag) . 'Tag'
                ));
            }
        }

    }

    /**
     * Check SeeAlso section : check nb member
     * Add an entry into the error's stack if an error is found
     *
     */
    function nbMemberInSeeAlso()
    {

        $reg  = '!<refsect1 role="seealso">(.*)</refsect1>!s';
        $reg2 = '!<member>(.*?)</member>!s';

        $match = array();
        $en_seeAlsoMember = 0;
        preg_match($reg, $this->en_content, $match);

        if (isset($match[1])) {
            $match2 = array();
            preg_match_all($reg2, $match[1], $match2);
            if (isset($match2[1])) {
                $en_seeAlsoMember = count($match2[1]);
            }
        }

        $match = array();
        $lang_seeAlsoMember = 0;
        preg_match($reg, $this->lang_content, $match);

        if (isset($match[1])) {
            $match2 = array();
            preg_match_all($reg2, $match[1], $match2);
            if (isset($match2[1])) {
                $lang_seeAlsoMember = count($match2[1]);
            }
        }

        if ($en_seeAlsoMember != $lang_seeAlsoMember ) {
            $this->addError(array(
                    "value_en"   => $en_seeAlsoMember,
                    "value_lang" => $lang_seeAlsoMember,
                    "type"       => "nbSeeAlsoMember"
            ));

        }
    }

    /**
     * Check methodsynopsis
     * Add an entry into the error's stack if an error is found
     *
     */
    function methodsynopsis()
    {

        $reg1 = '/<methodsynopsis>(\s.*?)<\/methodsynopsis>/s';
        $reg2 = '/<type>(.*?)<\/type>\s*?<methodname>(.*?)<\/methodname>/s';
        $reg3 = '/<methodparam\s*?((choice=\'opt\')|(choice="opt"))?>\s*?<type>(.*?)<\/type>\s*?<parameter\s*?((role=\'reference\')|(role="reference"))?>(.*?)<\/parameter>\s*?(<initializer>(.*?)<\/initializer>\s*?)?<\/methodparam>/s';


        $match = $en_methodsynopsis = array();
        preg_match_all($reg1, $this->en_content, $match);

        for ($i = 0; $i < count($match[1]); $i++) {

            $match2 = array();

            preg_match_all($reg2, $match[1][$i], $match2);

            if (isset($match2[2][0]) && isset($match2[1][0])) {

                $en_methodsynopsis[$i]['methodname']['name'] = $match2[2][0];
                $en_methodsynopsis[$i]['methodname']['type'] = $match2[1][0];

                $match2 = array();
                preg_match_all($reg3, $match[1][$i], $match2);

                $en_methodsynopsis[$i]['methodparam']['parameter']   = $match2[8];
                $en_methodsynopsis[$i]['methodparam']['initializer'] = $match2[10];
                $en_methodsynopsis[$i]['methodparam']['type']        = $match2[4];



                for ($j = 0; $j < count($match2[1]); $j++) {
                    if (trim($match2[1][$j]) == 'choice="opt"' || trim($match2[1][$j]) == "choice='opt'" ) {
                        $en_methodsynopsis[$i]['methodparam']['optionnel'][$j] = 1;
                    }
                    else {
                        $en_methodsynopsis[$i]['methodparam']['optionnel'][$j] = 0;
                    }
                }

                for ($j = 0; $j < count($match2[5]); $j++) {
                    if (trim($match2[5][$j]) == "role='reference'" || trim($match2[5][$j]) == 'role="reference"' ) {
                        $en_methodsynopsis[$i]['methodparam']['role'][$j] = 1;
                    }
                    else {
                        $en_methodsynopsis[$i]['methodparam']['role'][$j] = 0;
                    }
                }

            }
        }

        $match = $lang_methodsynopsis = array();
        preg_match_all($reg1, $this->lang_content, $match);

        for ($i = 0; $i < count($match[1]); $i++) {

            $match2 = array();
            preg_match_all($reg2, $match[1][$i], $match2);

            if (isset($match2[2][0]) && isset($match2[1][0])) {

                $lang_methodsynopsis[$i]['methodname']['name'] = $match2[2][0];
                $lang_methodsynopsis[$i]['methodname']['type'] = $match2[1][0];

                $match2 = array();
                preg_match_all($reg3, $match[1][$i], $match2);

                $lang_methodsynopsis[$i]['methodparam']['parameter']   = $match2[8];
                $lang_methodsynopsis[$i]['methodparam']['initializer'] = $match2[10];
                $lang_methodsynopsis[$i]['methodparam']['type']        = $match2[4];

                for ($j=0; $j < count($match2[1]); $j++) {

                    if (trim($match2[1][$j]) == 'choice="opt"' || trim($match2[1][$j]) == "choice='opt'" ) {
                        $lang_methodsynopsis[$i]['methodparam']['optionnel'][$j] = 1;
                    } else {
                        $lang_methodsynopsis[$i]['methodparam']['optionnel'][$j] = 0;
                    }

                }

                for ($j=0; $j < count($match2[5]); $j++) {
                    if (trim($match2[5][$j]) == "role='reference'" || trim($match2[5][$j]) == 'role="reference"' ) {
                        $lang_methodsynopsis[$i]['methodparam']['role'][$j] = 1;
                    } else {
                        $lang_methodsynopsis[$i]['methodparam']['role'][$j] = 0;
                    }

                }

            }

        }

        for ($i = 0; $i < count($en_methodsynopsis); $i++) {

            // Check on name
            if (isset($en_methodsynopsis[$i]['methodname']['name'])) {

                if (!isset($lang_methodsynopsis[$i]['methodname']['name'])) { $lang_methodsynopsis[$i]['methodname']['name'] = ''; }
                if ($en_methodsynopsis[$i]['methodname']['name'] != $lang_methodsynopsis[$i]['methodname']['name'] ) {
                    $this->addError(array(
                        'value_en'   => $en_methodsynopsis[$i]['methodname']['name'],
                        'value_lang' => $lang_methodsynopsis[$i]['methodname']['name'],
                        'type'       => 'errorMethodnameMethodsynopsis'
                    ));

                }

            }

            // Check on type
            if (isset($en_methodsynopsis[$i]['methodname']['type'])) {

                if (!isset($lang_methodsynopsis[$i]['methodname']['type'])) { $lang_methodsynopsis[$i]['methodname']['type'] = ''; }
                if ($en_methodsynopsis[$i]['methodname']['type'] != $lang_methodsynopsis[$i]['methodname']['type'] ) {
                    $this->addError(array(
                        'value_en'   => $en_methodsynopsis[$i]['methodname']['type'],
                        'value_lang' => $lang_methodsynopsis[$i]['methodname']['type'],
                        'type'       => 'errorTypeMethodsynopsis'
                    ));

                }
            }

            // Check on methodparam
            if (isset($en_methodsynopsis[$i]['methodparam']['parameter'])) {

                // Init
                if (!isset($en_methodsynopsis[$i]['methodparam']['parameter']))   { $en_methodsynopsis[$i]['methodparam']['parameter']=array(); }
                if (!isset($lang_methodsynopsis[$i]['methodparam']['parameter'])) { $lang_methodsynopsis[$i]['methodparam']['parameter']=array(); }

                // Check on Nb
                $nb_lang = count($lang_methodsynopsis[$i]['methodparam']['parameter']);
                $nb_en = count($en_methodsynopsis[$i]['methodparam']['parameter']);

                if ($nb_lang != $nb_en){
                    $this->addError(array(
                        'value_en'   => $nb_en,
                        'value_lang' => $nb_lang,
                        'type'       => 'errorNbMethodparamMethodsynopsis'
                    ));
                }

                for($j=0;$j<count($en_methodsynopsis[$i]['methodparam']['parameter']);$j++) {

                    // Check on parameter
                    if (isset($en_methodsynopsis[$i]['methodparam']['parameter'][$j])) {

                        if (!isset($lang_methodsynopsis[$i]['methodparam']['parameter'][$j])) { $lang_methodsynopsis[$i]['methodparam']['parameter'][$j]=''; }

                        if ($en_methodsynopsis[$i]['methodparam']['parameter'][$j] != $lang_methodsynopsis[$i]['methodparam']['parameter'][$j]) {
                            $this->addError(array(
                                'value_en'   => $en_methodsynopsis[$i]['methodparam']['parameter'][$j],
                                'value_lang' => $lang_methodsynopsis[$i]['methodparam']['parameter'][$j],
                                'type'       => 'errorParameterNameMethodsynopsis'
                            ));
                        }

                    }

                    // Check on type
                    if (isset($en_methodsynopsis[$i]['methodparam']['type'][$j])) {

                        if (!isset($lang_methodsynopsis[$i]['methodparam']['type'][$j])) { $lang_methodsynopsis[$i]['methodparam']['type'][$j]=''; }

                        if ($en_methodsynopsis[$i]['methodparam']['type'][$j]!=$lang_methodsynopsis[$i]['methodparam']['type'][$j]) {
                            $this->addError(array(
                                'value_en'   => $en_methodsynopsis[$i]['methodparam']['type'][$j],
                                'value_lang' => $lang_methodsynopsis[$i]['methodparam']['type'][$j],
                                'type'       => 'errorParameterTypeMethodsynopsis'
                            ));
                        }
                    }

                    // Check on initializer
                    if (isset($en_methodsynopsis[$i]['methodparam']['initializer'][$j])) {

                        if (!isset($lang_methodsynopsis[$i]['methodparam']['initializer'][$j])) { $lang_methodsynopsis[$i]['methodparam']['initializer'][$j]=''; }

                        if ($en_methodsynopsis[$i]['methodparam']['initializer'][$j]!=$lang_methodsynopsis[$i]['methodparam']['initializer'][$j]) {
                            $this->addError(array(
                                'value_en'   => $en_methodsynopsis[$i]['methodparam']['initializer'][$j],
                                'value_lang' => $lang_methodsynopsis[$i]['methodparam']['initializer'][$j],
                                'type'       => 'errorParameterInitializerMethodsynopsis'
                            ));
                        }
                    }

                    // Check on optionnel
                    if (isset($en_methodsynopsis[$i]['methodparam']['optionnel'][$j])) {

                        if (!isset($lang_methodsynopsis[$i]['methodparam']['optionnel'][$j])) {
                            $lang_methodsynopsis[$i]['methodparam']['optionnel'][$j] = '';
                        }

                        if ($en_methodsynopsis[$i]['methodparam']['optionnel'][$j] != $lang_methodsynopsis[$i]['methodparam']['optionnel'][$j]) {
                            $tmp1 = ($en_methodsynopsis[$i]['methodparam']['optionnel'][$j] == 0) ? $en_methodsynopsis[$i]['methodparam']['parameter'][$j] . ' <strong>ISN\'T</strong> optional' : $en_methodsynopsis[$i]['methodparam']['parameter'][$j].' <strong>IS</strong> optional (choice="opt")';

                            $tmp2 = ($lang_methodsynopsis[$i]['methodparam']['optionnel'][$j] == 0) ? $lang_methodsynopsis[$i]['methodparam']['parameter'][$j].' <strong>ISN\'T</strong> optional' : $lang_methodsynopsis[$i]['methodparam']['parameter'][$j].' <strong>IS</strong> optional (choice="opt")';

                            $this->addError(array(
                                'value_en'   => $tmp1,
                                'value_lang' => $tmp2,
                                'type'       => 'errorOptionalMethodsynopsis'
                            ));
                        }
                    }

                    // Check on role
                    if (isset($en_methodsynopsis[$i]['methodparam']['role'][$j])) {

                        if (!isset($lang_methodsynopsis[$i]['methodparam']['role'][$j])) {
                            $lang_methodsynopsis[$i]['methodparam']['role'][$j]='';
                        }

                        if ($en_methodsynopsis[$i]['methodparam']['role'][$j] != $lang_methodsynopsis[$i]['methodparam']['role'][$j] ) {
                            $tmp1 = ($en_methodsynopsis[$i]['methodparam']['role'][$j] == 0) ? $en_methodsynopsis[$i]['methodparam']['parameter'][$j].' <strong>ISN\'T</strong> reference' : $en_methodsynopsis[$i]['methodparam']['parameter'][$j].' <strong>IS</strong> reference (role="reference")';
                            $tmp2 = ($lang_methodsynopsis[$i]['methodparam']['role'][$j]==0) ? $lang_methodsynopsis[$i]['methodparam']['parameter'][$j].' <strong>ISN\'T</strong> reference' : $lang_methodsynopsis[$i]['methodparam']['parameter'][$j].' <strong>IS</strong> reference (role="reference")';

                            $this->addError(array(
                                'value_en'   => $tmp1,
                                'value_lang' => $tmp2,
                                'type'       => 'errorRoleMethodsynopsis'
                            ));
                        }
                    }
                }
            }
        }
    }

}
