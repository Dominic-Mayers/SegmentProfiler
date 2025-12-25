<?php
namespace Codeforweb\SegmentNotes\EventListener;

use Psr\Log\LoggerInterface;
use Codeforweb\SegmentNotes\Segment;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

class TerminateListener
{          

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }
    
    public function onKernelTerminate(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();
        $url_array = array_filter(explode('/', $pathInfo));
        $name = end($url_array); 
        try {
           Segment::writeSegment("$name.profile");
        } catch (\Exception $e) {
            $this->logger("Error in trying to write profile $name." . $e->getMessage()); 
        }
    }
}
