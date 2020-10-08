<?php
/**
 * Creator: Bryan Mayor
 * Company: Blue Nest Digital, LLC
 * License: (Blue Nest Digital LLC, All rights reserved)
 * Copyright: Copyright 2020 Blue Nest Digital LLC
 */

namespace Roost\HtmlReportGenerator;

class ReportGenerator {

	const ENUM_REPORT_COLUMN_MODE_STR_TO_UPPER = "ENUM_REPORT_COLUMN_MODE_STR_TO_UPPER";

	private $includedKeys = [];
	private $columnHeaders = [];
	private $title = null;
	private $columnMode = null;
	private $nestedValueMode = null;
	private $valueRetrieverCallable;
	private $rowRetrieverCallable;
	private $rows = [];

	public function addSection(array $rows, string $title) {
		$this->rows[$title] = $rows;
	}

	public function generate() {
        $html = "";

    	$html .= "<h1>" . $this->title . "</h1>";
    	$html .= "<p>Time generated: " . date("r") . "</p>";

        //if($this->rowRetrieverCallable !== null) {
		//	$rowExtractor = $this->rowRetrieverCallable;
		//	$sections = $rowExtractor($rows);
		//}

        foreach($this->rows as $sectionName => $rowsForSection) {

        	if($this->rowRetrieverCallable !== null) {
        		$rowExtractor = $this->rowRetrieverCallable;
        		$retrievedRows = [];
        		foreach($rowsForSection as $row) {
        			$retrievedRows = array_merge($retrievedRows, $rowExtractor($row));
				}
        		$rowsForSection = $retrievedRows;
			}

        	if(!is_array($rowsForSection)) {
        		throw new \RuntimeException("Expecting row array, got: " . print_r($rowsForSection, true));
			}

        	if(array_key_exists($sectionName, $this->includedKeys)) {
				$includedKeys = $this->includedKeys[$sectionName];
				foreach($rowsForSection as &$row) {
					$row = array_filter($row, function ($val, $key) use($includedKeys) {
						return in_array($key, $includedKeys);
					}, ARRAY_FILTER_USE_BOTH);
				}
			}
        	$html .= "<h2>" . $this->getFormattedHeader($sectionName) . "</h2>" . PHP_EOL;
        	$html .= "<table>";

			$html .= "<tr>";

			$headers = $rowsForSection[0];
			if($this->valueRetrieverCallable !== null) {
				$valueRetriever = $this->valueRetrieverCallable;
				$headers = $valueRetriever($headers);
			}

			foreach($headers as $col => $val) {
				$col = $this->getFormattedHeader($col);
				$html .= "<th>" . $col . "</th>" . PHP_EOL;
			}
			$html .= "</tr>";

			foreach($rowsForSection as $finalRow) {

				if($this->valueRetrieverCallable !== null) {
					$valueRetriever = $this->valueRetrieverCallable;
					$finalRow = $valueRetriever($finalRow);
				}

				$html .= "<tr>" . PHP_EOL;
				foreach($finalRow as $col => $val) {
					if(is_object($val) || is_array($val)) {
						if($this->nestedValueMode === "JSON") {
							$val = json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
						}
					}

					$html .= "<td>" . $val . "</td>" . PHP_EOL;
				}
				$html .= "</tr>";
			}
			$html .= "</table>";
		}

        return $html;
	}

	public function title($title) {
		$this->title = $title;
		return $this;
	}

	function columnMode($modeEnum) {
		$this->columnMode = $modeEnum;
		return $this;
	}
	
	public function setIncludedKeys($includedKeys, $section = null) {
		if($section === null) {
			$this->includedKeys[""] = $includedKeys;
		} else {
			$this->includedKeys[$section] = $includedKeys;
		}
		return $this;
	}

	function rowRetriever(callable $callable) {
		$this->rowRetrieverCallable = $callable;
		return $this;
	}

	function valueRetriever(callable $callable) {
		$this->valueRetrieverCallable = $callable;
		return $this;
	}

	function nestedValueMode($nestedValueMode) {
		$this->nestedValueMode = $nestedValueMode;
		return $this;
	}

	private function getFormattedHeader($key) {
		if($this->columnMode === static::ENUM_REPORT_COLUMN_MODE_STR_TO_UPPER) {
			$parts = preg_split("#[_-]#", $key);
			$parts = array_map(function($val) {
				return ucfirst($val);
			}, $parts);
			return implode(" ", $parts);
		} else {
			return $key;
		}
	}
}