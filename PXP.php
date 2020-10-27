<?php
class PXP {
    function __construct($file)
    {
        $this->lastResult = NULL;
        $this->lastError = NULL;
        $this->sheetId = 1;
        $this->isHeader = FALSE;
        $this->headerRow = 1;
        $this->Sheet = NULL;
        $this->Strings = NULL;
        $this->SheetXml = NULL;
        $this->StringsXml = NULL;
        if (!file_exists($file)) {
            $this->lastError = 'File not found';
            return FALSE;
        }
        $zip = new ZipArchive;
        if ($zip->open($file)) {
            $this->Sheet = $zip->getFromName('xl/worksheets/sheet' . $this->sheetId . '.xml');
            $this->Strings = $zip->getFromName('xl/sharedStrings.xml');
            $zip->close();
        }
        else {
            $this->lastError = 'File open error';
            return FALSE;
        }
        if (!$this->Sheet) {
            $this->lastError = "Can't get table data";
            return FALSE;
        }
        if (!$this->Strings) {
            $this->lastError = "Can't get table values";
            return FALSE;
        }
        $this->SheetXml = simplexml_load_string($this->Sheet);
        if (!$this->SheetXml) {
            $this->lastError = "Error converting table data";
            return FALSE;
        }
        $this->StringsXml = simplexml_load_string($this->Strings);
        if (!$this->StringsXml) {
            $this->lastError = "Error converting table values";
            return FALSE;
        }
    }

    public function GetArray () {
		if (!empty($this->lastError)) {
			return FALSE;
		}
        $result = array();
        $headerArr = array();
        if ($this->isHeader) {
            foreach ($this->SheetXml->sheetData->row[$this->headerRow-1]->c as $col) {
                $i =  intval($col->v);
                $val = (array)$this->StringsXml->si[$i]->t;
                $headerArr[preg_replace('/\d/','',$col['r'])] = $val[0];
            }
        }
        else {
            foreach ($this->SheetXml->sheetData->row[0]->c as $col) {
                $headerArr[preg_replace('/\d/','',$col['r'])] = preg_replace('/\d/','',$col['r']);
            }
        }
    
        foreach ($this->SheetXml->sheetData->row as $row) {
            if ($this->isHeader && $row['r']<=$this->headerRow) {
                continue;
            }
            foreach ($row->c as $col) {
                $i =  intval($col->v);
                $val = (array)$this->StringsXml->si[$i]->t;
                $colId = preg_replace('/\d/','',$col['r']);
                $rowId = intval($row['r']);
                $result[$rowId][$colId] = $val[0];
            }
        }
        $this->lastResult = $result;
        return $this->lastResult;
    }
}
