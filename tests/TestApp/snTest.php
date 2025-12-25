<?php
require_once __DIR__ . '/../../profilecode/src/Segment.php';
use Codeforweb\SegmentNotes\Segment; 

for ( $i=0; $i<=1; $i++) {
    $s = Segment::startSegment('A');
    Segment::endSegment($s);

    $s = Segment::startSegment('B');
    $s1 = Segment::startSegment('A');
    Segment::endSegment($s1);

    $s1 = Segment::startSegment('B');
    Segment::endSegment($s1);

    $s1 = Segment::startSegment('A');
    Segment::endSegment($s1);

    $s1 = Segment::startSegment('C');
    Segment::endSegment($s1);
    
    $s1 = Segment::startSegment('B');
    Segment::endSegment($s1);

    Segment::endSegment($s);

    $s = Segment::startSegment('C');
    Segment::endSegment($s);

    $s = Segment::startSegment('A');
    Segment::endSegment($s);

    $s = Segment::startSegment('B');
    Segment::endSegment($s);

    $s = Segment::startSegment('C');
    Segment::endSegment($s);
}
