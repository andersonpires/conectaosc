<?php

class FPDI_PDF_Parser extends fpdi_pdf_parser
{
    public function getPageCount()
    {
        return count($this->pages);
    }

    public function getPageData($pageNo)
    {
        return $this->_getPage($pageNo);
    }
}
