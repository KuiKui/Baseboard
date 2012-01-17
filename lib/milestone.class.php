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
  protected $workingTeammates = array();

  protected $startAt;
  protected $deadline;
  protected $completed;
  protected $outdated = false;
  protected $progressState = '';

  protected $totalQuotation = 0;
  protected $completedQuotation = 0;

  protected $totalBugsCount = 0;
  protected $completedBugsCount = 0;



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

  public function setCompletedQuotation($completedCotation)
  {
    $this->completedQuotation = $completedCotation;
  }

  public function getCompletedQuotation()
  {
    return $this->completedQuotation;
  }

  public function getPercentQuotation()
  {
    if($this->totalQuotation > 0)
    {
      return round($this->completedQuotation / $this->totalQuotation * 100);
    }

    return 0;
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

  public function setTotalQuotation($totalCotation)
  {
    $this->totalQuotation = $totalCotation;
  }
  public function getTotalQuotation()
  {
    return $this->totalQuotation;
  }

  public function setCompletedBugsCount($completedBugsCount)
  {
    $this->completedBugsCount = $completedBugsCount;
  }

  public function getCompletedBugsCount()
  {
    return $this->completedBugsCount;
  }

  public function getOpenBugsCount()
  {
    return $this->totalBugsCount - $this->completedBugsCount;
  }

  public function setTotalBugsCount($totalBugsCount)
  {
    $this->totalBugsCount = $totalBugsCount;
  }

  public function getTotalBugsCount()
  {
    return $this->totalBugsCount;
  }

  public function setWorkingTeammates($workingTeammates)
  {
    $this->workingTeammates = $workingTeammates;
  }

  public function getWorkingTeammates()
  {
    return $this->workingTeammates;
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
  public function processTodoList($tmpTodolist)
  {
    // Update todolist
    $this->todoLists = array( 'id' => $tmpTodolist['id'],
                              'name' => $tmpTodolist['name'],
                              'complete' => ($tmpTodolist['complete'] == 'true'));

    $this->loadTodoItems($tmpTodolist['id']);

    $this->updateProgressState();
  }

  /**
   * Issues a request to basecamp API so as to load todolist items
   *
   * @param $todolistId int Identifier of the todolist whose items are to load
   */
  public function loadTodoItems($todolistId)
  {
    $todoItems = $this->project->getBasecampAPI()->get(sprintf('todo_lists/%s/todo_items.xml', $todolistId));

    // No todoitems
    if(is_null($todoItems))
    {
      return;
    }

    foreach($todoItems as $todoItem)
    {
      $this->processTodoItem($todoItem);
    }
  }

  /**
   * Parses a basecamp todoitem and updates the milestone properties accordingly
   *
   * @param $todoItem array json representation of a basecamp todoitem
   */
  public function processTodoItem($todoItem)
  {
    $quotation = 0;
    $isCompleted = ($todoItem['completed'] == 'true');

    // Get cotation
    preg_match('/ ((\d+)?\.*(\d+)*)$/', $todoItem['content'], $matches);
    if(count($matches) > 1)
    {
      $quotation = $matches[1];
    }

    // Get type (bug ?)
    preg_match('/^bug/i', $todoItem['content'], $matches);
    $isBug = (count($matches) > 0);

    if($isBug)
    {
      $this->totalBugsCount++;
      $this->completedBugsCount += ($isCompleted) ? 1 : 0;
    }
    else
    {
      $this->totalQuotation += $quotation;
      $this->completedQuotation += ($isCompleted) ? $quotation : 0;
    }

    if(!$isCompleted)
    {
      $team = $this->project->getTeam();
      if(isset($todoItem['responsible-party-id']) && isset($team[$todoItem['responsible-party-id']]))
      {
        if(!isset($this->workingTeammates[$todoItem['responsible-party-id']]))
        {
          $this->workingTeammates[$todoItem['responsible-party-id']] = $team[$todoItem['responsible-party-id']];
        }
      }
    }
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
        $theoricalCurrentCompletedCotation = $spentDaysCount * $this->totalQuotation / $totalDaysCount;
        $theoricalRemainingDaysCount = $this->completedQuotation - $theoricalCurrentCompletedCotation;
        $perDay = $this->totalQuotation / $totalDaysCount;
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
