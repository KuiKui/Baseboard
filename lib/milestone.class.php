<?php

/**
 * Basecamp Milestone object
 */
class milestone
{
  protected $id;
  protected $name;
  protected $project;

  protected $todoLists = array();

  protected $startAt;
  protected $deadline;
  protected $completed;
  protected $outdated = false;
  protected $progressState = '';



  public function setId($id)
  {
    $this->id = $id;
  }

  public function getId()
  {
    return $this->id;
  }

  public function setProgressState($lateCssClass)
  {
    $this->progressState = $lateCssClass;
  }

  public function getProgressState()
  {
    return $this->progressState;
  }

  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }

  public function setCompleted($completed)
  {
    $this->completed = $completed;
  }

  public function getCompleted()
  {
    return $this->completed;
  }

  public function setDeadline($deadline)
  {
    $this->deadline = $deadline;
  }

  public function getDeadline()
  {
    return $this->deadline;
  }

  public function getOutdated()
  {
    return (strtotime(date('c')) > strtotime($this->deadline.' 23:59:59'));
  }

  public function setProject($project)
  {
    $this->project = $project;
  }
  public function getProject()
  {
    return $this->project;
  }

  public function setStartAt($startAt)
  {
    $this->startAt = $startAt;
  }
  public function getStartAt()
  {
    return $this->startAt;
  }

  public function getTotalQuotation()
  {
    $totalQuotation = 0;
    foreach($this->todoLists as $todolist)
    {
      $totalQuotation += $todolist->getTotalQuotation();
    }
    return $totalQuotation;
  }

  public function getCompletedQuotation()
  {
    $completedQuotation = 0;
    foreach($this->todoLists as $todolist)
    {
      $completedQuotation += $todolist->getCompletedQuotation();
    }
    return $completedQuotation;
  }

  public function getPercentQuotation()
  {
    if($this->getTotalQuotation() > 0)
    {
      return round($this->getCompletedQuotation() / $this->getTotalQuotation() * 100);
    }

    return 0;
  }

  public function getTotalBugsCount()
  {
    $totalBugsCount = 0;
    foreach($this->todoLists as $todolist)
    {
      $totalBugsCount += $todolist->getTotalBugsCount();
    }
    return $totalBugsCount;
  }

  public function getCompletedBugsCount()
  {
    $completedBugsCount = 0;
    foreach($this->todoLists as $todolist)
    {
      $completedBugsCount += $todolist->getCompletedBugsCount();
    }
    return $completedBugsCount;
  }

  public function getOpenBugsCount()
  {
    $openBugsCount = 0;
    foreach($this->todoLists as $todolist)
    {
      $openBugsCount += $todolist->getOpenBugsCount();
    }
    return $openBugsCount;
  }

  public function getWorkingTeammates()
  {
    $teammates = array();
    foreach($this->todoLists as $todolist)
    {
      $teammates += $todolist->getWorkingTeammates();
    }
    //$teammates = array_unique($teammates);

    return $teammates;
  }

  public function getBugResolvingTeammates()
  {
    $teammates = array();
    foreach($this->todoLists as $todolist)
    {
      $teammates += $todolist->getBugResolvingTeammates();
    }
    //$teammates = array_unique($teammates);

    return $teammates;
  }

  /**
   * Builds a milestone given its related project
   *
   * @param $project project project related to this milestone
   */
  public function __construct($project)
  {
    $this->project = $project;
  }


  /**
   * Issues a request to basecamp API so as to load a milestone and initializes the properties accordingly
   *
   * @param $id int Identifier of the milestone to load
   * @return bool false if the load/initialization fails, true otherwise
   */
  public function load($id)
  {
    $tmpMilestone =  $this->project->getBasecampAPI()->get(sprintf('projects/%s/calendar_entries/%s.xml', $this->project->getBasecampId(), $id));
    return $this->init($tmpMilestone);
  }

  /**
   * Initializes the current milestone given its json basecamp representation
   *
   * @param $tmpMilestone array json representation of a basecamp milestone
   * @return bool false if the initialization fails, true otherwise
   */
  public function init($tmpMilestone)
  {
    if(is_null($tmpMilestone))
    {
      return false;
    }

    $this->id = $tmpMilestone['id'];
    $this->name = $tmpMilestone['title'];
    $this->completed = $tmpMilestone['completed']=='true';
    $this->deadline = $tmpMilestone['deadline'];
    $this->startAt = $tmpMilestone['start-at'];

    return true;
  }


  /**
   * @return bool true if the current milestone is started but neither ended nor completed
   */
  public function isPending()
  {
    // invalid dates
    if(is_array($this->startAt) || is_array($this->deadline))
    {
      return false;
    }

    // if milestone is already flagged as completed or is not started yet
    if($this->completed || strtotime($this->startAt) > strtotime('now'))
    {
      return false;
    }

    return true;
  }

  /**
   *  Parses a basecamp todolist and updates the milestone properties accordingly
   *
   * @param $tmpTodolist array json representation of a basecamp todolist
   */
  public function processTodoList($todoList)
  {
    // Update todolist
    $this->todoLists[] = $todoList;
    $todoList->loadTodoItems();

    $this->updateProgressState();
  }


  /**
   * Updates the milestone progress state to one of the following : done, late, early
   */
  protected function updateProgressState()
  {
    if($this->getPercentQuotation() == 100)
    {
      $this->progressState = 'done';
    }
    else
    {
      $totalDaysCount = self::getDiffTimestamp($this->startAt, $this->deadline, $this->project->getWorkdays(), $this->project->getHolidays());
      if($totalDaysCount > 0)
      {
        $yesterday = new DateTime('-1 day');
        $spentDaysCount = self::getDiffTimestamp($this->startAt, $yesterday->format('Y-m-d'), $this->project->getWorkdays(), $this->project->getHolidays());
        $theoricalCurrentCompletedCotation = $spentDaysCount * $this->getTotalQuotation() / $totalDaysCount;
        $theoricalRemainingDaysCount = $this->getCompletedQuotation() - $theoricalCurrentCompletedCotation;
        $perDay = $this->getTotalQuotation() / $totalDaysCount;
        if($theoricalRemainingDaysCount <= 0 - $perDay)
        {
          $this->progressState = 'late';
        }
        else if($theoricalRemainingDaysCount > $perDay)
        {
          $this->progressState = 'early';
        }
      }
    }
  }


  /**
   * Returns the number of worked days between start and end
   *
   * @static
   * @param $start string starting day
   * @param $end string ending day
   * @param $workdays array list of worked days in the week
   * @param $holidays array list of holidays
   * @return int number of worked days between start and end
   */
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
