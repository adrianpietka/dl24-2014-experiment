<?php

class ResponseFormatter {
    public function oneLineToArrayWithNamedKeys($line, $format) {
        $formatedData = [];
        $elements = explode(' ', $line);
        
        foreach($format as $index => $key) {
            $formatedData[$key] = $elements[$index];
        }
        
        return $formatedData;
    }
    
    public function linesToArraysWithNamedKeys($lines, $format) {
        $output = [];
        
        foreach($lines as $line) {
            $output[] = $this->oneLineToArrayWithNamedKeys($line, $format);
        }
        
        return $output;
    }
}