<?php

/**
 * TCPDI importer class for TCPDF
 * 
 * @package    tcpdi
 * @subpackage tcpdi.importer
 * @author     Daniel Stenberg
 * @license    LGPLv2
 */

require_once(dirname(__FILE__) . '/tcpdi_parser.php');

class TCPDI_Importer
{

    /**
     * @var object PDF parser object.
     */
    protected $parsers = array();

    /**
     * @var object TCPDF object.
     */
    protected $tcpdf = null;

    /**
     * @var integer Current parser id
     */
    protected $current_parser = -1;

    /**
     * Constructor.
     *
     * @param TCPDF $tcpdf TCPDF instance
     */
    public function __construct(&$tcpdf)
    {
        $this->tcpdf = &$tcpdf;
    }

    /**
     * Load a PDF document and setup parser.
     *
     * @param string $filename Path to input PDF file.
     * @return integer parser id
     */
    public function loadPdf($filename)
    {

        $id = count($this->parsers);

        $parser = new TCPDI_Parser($filename);
        $this->parsers[$id] = $parser;

        $this->current_parser = $id;

        return $id;
    }

    /**
     * Get the number of pages in the PDF.
     *
     * @param integer $parserId
     * @return integer
     */
    public function getPageCount($parserId = null)
    {

        if ($parserId === null) {
            $parserId = $this->current_parser;
        }

        return $this->parsers[$parserId]->getPageCount();
    }

    /**
     * Import a page.
     *
     * @param integer $pageNumber
     * @param integer $parserId
     * @return string template id
     */
    public function importPage($pageNumber, $parserId = null)
    {

        if ($parserId === null) {
            $parserId = $this->current_parser;
        }

        $parser = $this->parsers[$parserId];

        $tpl = $parser->importPage($pageNumber);

        return $tpl;
    }

    /**
     * Get the resource dictionary of a page.
     *
     * @param integer $pageNumber
     * @param integer $parserId
     * @return array
     */
    public function getPageResources($pageNumber, $parserId = null)
    {

        if ($parserId === null) {
            $parserId = $this->current_parser;
        }

        return $this->parsers[$parserId]->getPageResources($pageNumber);
    }

    /**
     * Get the content stream of a page.
     *
     * @param integer $pageNumber
     * @param integer $parserId
     * @return string
     */
    public function getPageContent($pageNumber, $parserId = null)
    {

        if ($parserId === null) {
            $parserId = $this->current_parser;
        }

        return $this->parsers[$parserId]->getPageContent($pageNumber);
    }
}
