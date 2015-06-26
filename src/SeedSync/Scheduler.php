<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 12/07/14
 * Time: 20:57
 */

namespace SeedSync;

use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Reader;

class Scheduler
{
    /**
     * @var \DateTimeZone
     */
    private $timeZone;
    private $calendar = null;

    public function __construct($zone = 'Europe/London')
    {
        $this->timeZone = new \DateTimeZone($zone);
    }

    /**
     * @return \DateTimeZone
     */
    public function getTimeZone()
    {
        return $this->timeZone;
    }

    /**
     * @param \DateTimeZone $timeZone
     * @return $this
     */
    public function setTimeZone($timeZone)
    {
        $this->timeZone = $timeZone;
        return $this;
    }

    /**
     *
     * @param string|null $time Timestamp, or leave empty to use current time
     * @return array An array of all events at the scheduled time. Currently containing the 'SUMMARY' property.
     * @throws \InvalidArgumentException
     */
    public function checkSchedule($time = null)
    {
        if ($time === null) {
            $time = time();
        }

        if (is_numeric($time) === false) {
            throw new \InvalidArgumentException("Time provided is nonsense", 400);
        }

        # Get the array of schedule information
        $schedule = $this->getCalendar();

        # No schedule found? Return default.
        if ($schedule === null) {
            throw new \InvalidArgumentException("Calendar needs to be passed", 400);
        }


        $currentTime = new \DateTime('@' . $time, $this->timeZone);

        $calendar = Reader::read($schedule);

        /** @var VEvent[] $currentEvents */
        $currentEvents = array();


        foreach ($calendar->VEVENT as $event) {
            /** @var VEvent $event */
            if ($event->isInTimeRange($currentTime, $currentTime)) {
                $descriptionJson = json_decode($event->DESCRIPTION->getValue());
                if(is_object($descriptionJson)){
                    $description = $descriptionJson;
                }else{
                    $description = $event->DESCRIPTION->getValue();
                }

                // This event is happening right now!
                $currentEvents[] = array(
                    'summary' => $event->SUMMARY->getValue(),
                    'description' => $description,
                );
            }
        }

        return $currentEvents;
    }

    public function getEventType ($type)
    {
        $events = $this->checkSchedule();

        $formattedEvents = array();

        foreach($events as $event){
            if(is_object($event['description']) == false || $event['description']->action != $type){
                continue;
            }
            unset($event['description']->action);
            $formattedEvents[] = $event['description'];
        }
        return $formattedEvents;
    }

    /**
     * @return string|null
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /**
     * Set a calendar to compare again
     *
     * @param string $calendar
     * @return $this
     */
    public function setCalendar($calendar)
    {
        $this->calendar = $calendar;
        return $this;
    }
}