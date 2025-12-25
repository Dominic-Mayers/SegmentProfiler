<?php
require_once __DIR__ . '/../../profilecode/src/Segment.php';
use Codeforweb\SegmentNotes\Segment; 

$s1 = Segment::startSegment('A');
Segment::endSegment($s1);

$s1 = Segment::startSegment('B');

    $s2 = Segment::startSegment('A');
        $s3 = Segment::startSegment('B');
            $s4 = Segment::startSegment('A');
            Segment::endSegment($s4);
        Segment::endSegment($s3);
    Segment::endSegment($s2);

    $s2 = Segment::startSegment('C');
        $s3 = Segment::startSegment('B');
            $s4 = Segment::startSegment('C');
            Segment::endSegment($s4);
        Segment::endSegment($s3);
    Segment::endSegment($s2);

Segment::endSegment($s1);
