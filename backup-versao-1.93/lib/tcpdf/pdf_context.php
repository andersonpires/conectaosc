<?php

class pdf_context {
    protected $file;

    public function __construct($file)
    {
        $this->file = fopen($file, "rb");
    }

    public function getFile()
    {
        return $this->file;
    }
}
