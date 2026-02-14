<?php

namespace Codeforweb\SegmentNotes;

class Segment {
    static $fh = null;
    static string $name = ""; 
    static int $idNumber = 1;
    protected int $timeFct;
    protected string $segmentId; 
        
    static public function openProfileHandle() {
        if (! isset(self::$fh) ) {
            self::$name = hrtime(true). ".profile";
            if (file_exists(self::$name) ) {
                echo "Error: file ". self::$name . " exists";
                exit(); 
            }
            try {
                $path = __DIR__ . "/../../input/" . self::$name; 
                self::$fh = fopen($path , "a");
            } catch (Exception $e) {
                echo "Could not create handle on $path"; 
                exit(); 
            }
        }
    }

    static public function getId() : string {
        return self::$idNumber++; 
    }
    
    static public function startSegment($startName) {
         $segment = new Segment();
         $segment->segmentId = self::getId();
         $segment->timeFct = - hrtime(true); 
         Segment::openProfileHandle();
         fwrite(self::$fh, $segment->segmentId . ":startName=" . $startName . PHP_EOL);
         return $segment;
    }
    
    static public function endSegment($segment, $endName="none") {
        $segment->timeFct +=  hrtime(true);
        fwrite(self::$fh, $segment->segmentId . ":timeInclusive=" . $segment->timeFct . PHP_EOL .
                          $segment->segmentId . ":endName=" . $endName . PHP_EOL);
    }
    
    static public function writeSegment($segment, $endName="none") {
            
    }

}
