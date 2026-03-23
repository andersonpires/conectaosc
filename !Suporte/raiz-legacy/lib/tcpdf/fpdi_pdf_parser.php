<?php

class fpdi_pdf_parser
{
    protected $filename;
    protected $pages = [];

    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->parseFile();
    }

    protected function parseFile()
    {
        $content = file_get_contents($this->filename);
        preg_match_all('/\/Type\s*\/Page\b/', $content, $matches);
        $pageCount = count($matches[0]);

        for ($i = 1; $i <= $pageCount; $i++) {
            $this->pages[$i] = [
                "width" => 210,
                "height" => 297,
                "content" => "<svg></svg>"
            ];
        }
    }

    protected function _getPage($pageNo)
    {
        if (!isset($this->pages[$pageNo])) {
            throw new Exception("Página $pageNo não encontrada no PDF.");
        }
        return $this->pages[$pageNo];
    }
}
