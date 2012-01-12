<?php

class milestone
{
  protected $id;
  protected $name;
  protected $todoLists = array();
  protected $completed;
  protected $deadline;
  protected $startAt;
  protected $totalCotation = 0;
  protected $completedCotation = 0;
  protected $percentCotation = 0;
  protected $totalBug = 0;
  protected $completedBug = 0;
  protected $openedBug = 0;
  protected $outdated = false;
  protected $lateCssClass = '';
  protected $teammates = array();

  protected $project;

  public function __construct($id, $project)
  {
    $this->id = $id;
    $this->project = $project;
  }

  public function load()
  {
    $requestHeader = array('Accept: application/xml', 'Content-Type: application/xml');
    $basecampAPI = new basecampAPI(new RESTConnection($this->project->getBasecampUrl(), $requestHeader, $this->project->getBasecampToken(), 'X'));

    $tmpMilestone = $basecampAPI->get(sprintf('projects/%s/calendar_entries/%s.xml', $this->project->getBasecampId(), $this->id));

    if(is_null($tmpMilestone) || is_array($tmpMilestone['start-at']) || is_array($tmpMilestone['deadline']))
    {
      return false;
    }

    if($tmpMilestone['completed'] == 'true' || strtotime($tmpMilestone['start-at']) > strtotime('now'))
    {
      return false;
    }

    $this->name = self::customStrip($tmpMilestone['title'], 40);
    $this->completed = $tmpMilestone['completed'];
    $this->deadline = $tmpMilestone['deadline'];
    $this->startAt = $tmpMilestone['start-at'];

    return true;
  }


  public function updateProperties()
  {
    if($this->totalCotation > 0)
    {
      $this->percentCotation = round($this->completedCotation / $this->totalCotation * 100);
    }
    $this->openedBug = $this->totalBug - $this->completedBug;
    $this->outdated = (strtotime(date('c')) > strtotime($this->deadline.' 23:59:59'));

    if($this->percentCotation == 100)
    {
      $this->lateCssClass = 'done';
    }
    else
    {
      $timeDiff = self::getDiffTimestamp($this->startAt, $this->deadline, $this->project->getWorkdays(), $this->project->getHolidays());
      if($timeDiff > 0)
      {
        $lastDay = new DateTime('-1 day');
        $theoricalCompletedCotation = self::getDiffTimestamp($this->startAt, $lastDay->format('Y-m-d'), $this->project->getWorkdays(), $this->project->getHolidays()) * $this->totalCotation / $timeDiff;
        $cotationGap = $this->completedCotation - $theoricalCompletedCotation;
        $byDay = $this->totalCotation / $timeDiff;
        if($cotationGap <= 0 - $byDay)
        {
          $this->lateCssClass = 'late';
        }
        else if($cotationGap > $byDay)
        {
          $this->lateCssClass = 'early';
        }
      }
    }
  }

  public function setTodoLists($todoLists)
  {
    $this->todoLists = $todoLists;
  }

  public function getProperties()
  {
    return array(
      'id' => $this->id,
      'name' => $this->name,
      'todoLists' => $this->todoLists,
      'completed' => $this->completed,
      'deadline' => $this->deadline,
      'startAt' => $this->startAt,
      'totalCotation' => $this->totalCotation,
      'completedCotation' => $this->completedCotation,
      'percentCotation' => $this->percentCotation,
      'totalBug' => $this->totalBug,
      'completedBug' => $this->completedBug,
      'openedBug' => $this->openedBug,
      'outdated' => $this->outdated,
      'lateCssClass' => $this->lateCssClass,
      'teammates' => $this->teammates
    );
  }

  public static function customStrip($str, $length = null)
  {
    $retour = ucfirst(preg_replace('/^.*:[ ]+/i', '', trim($str)));

    if(!is_null($length) && is_int($length) && $length < strlen($retour))
    {
      $retour = mb_substr($retour, 0, $length - 3, 'UTF-8') . "...";
    }

    return $retour;
  }


  public static function getDiffTimestamp($start, $end, $workdays, $holidays)
  {
    $startDate = new DateTime(substr($start, 0, 10));
    $endDate = new DateTime(substr($end, 0, 10).' + 1 day');
    $interval = DateInterval::createFromDateString('1 day');

    $total = 0;

    $days = new DatePeriod($startDate, $interval, $endDate);
    foreach ( $days as $day ) {
      // Week End
      if(isset($workdays) && !in_array($day->format('w'), $workdays))
      {
        continue;
      }

      // Holidays
      if(isset($holidays) && in_array($day->format('d/m/y'), $holidays))
      {
        continue;
      }

      $total++;
    }

    return $total;
  }
}
