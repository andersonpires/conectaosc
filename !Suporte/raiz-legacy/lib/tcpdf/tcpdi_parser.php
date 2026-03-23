<?php

class TCPDIParser
{
    protected $filename;
    protected $pdf;

    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->pdf = new FPDI_PDF_Parser($filename);
    }

    public function getPageCount()
    {
        return $this->pdf->getPageCount();
    }

    public function parsePage($pageno)
    {
        return $this->pdf->getPageData($pageno);
    }
}
