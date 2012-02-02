<?php

  /**
   * Basecamp Todolist object
   */
class todolist
{
  protected $id;
  protected $name;
  protected $complete;
  protected $project;

  protected $remainingItems = array();
  protected $uncompletedCount;
  protected $milestoneId;

  protected $workingTeammates = array();
  protected $bugsResolvingTeammates = array();
  protected $openBugsList = array();

  protected $totalQuotation = 0;
  protected $completedQuotation = 0;

  protected $totalBugsCount = 0;
  protected $completedBugsCount = 0;


  public function setComplete($complete)
  {
    $this->complete = $complete;
  }

  public function getComplete()
  {
    return $this->complete;
  }

  public function setId($id)
  {
    $this->id = $id;
  }

  public function getId()
  {
    return $this->id;
  }

  public function setMilestoneId($milestoneId)
  {
    $this->milestoneId = $milestoneId;
  }

  public function getMilestoneId()
  {
    return $this->milestoneId;
  }

  public function setName($name)
  {
    $this->name = $name;
  }

  public function getName()
  {
    return $this->name;
  }

  public function setUncompletedCount($uncompletedCount)
  {
    $this->uncompletedCount = $uncompletedCount;
  }

  public function getUncompletedCount()
  {
    return $this->uncompletedCount;
  }

  public function setProject($project)
  {
    $this->project = $project;
  }

  public function getProject()
  {
    return $this->project;
  }

  public function setCompletedBugsCount($completedBugsCount)
  {
    $this->completedBugsCount = $completedBugsCount;
  }

  public function setTotalBugsCount($totalBugsCount)
  {
    $this->totalBugsCount = $totalBugsCount;
  }

  public function getCompletedBugsCount()
  {
    return $this->completedBugsCount;
  }

  public function getOpenBugsCount()
  {
    return $this->totalBugsCount - $this->completedBugsCount;
  }

  public function getTotalBugsCount()
  {
    return $this->totalBugsCount;
  }

  public function setCompletedQuotation($completedQuotation)
  {
    $this->completedQuotation = $completedQuotation;
  }

  public function getCompletedQuotation()
  {
    return $this->completedQuotation;
  }

  public function setTotalQuotation($totalQuotation)
  {
    $this->totalQuotation = $totalQuotation;
  }

  public function getTotalQuotation()
  {
    return $this->totalQuotation;
  }

  public function setWorkingTeammates($workingTeammates)
  {
    $this->workingTeammates = $workingTeammates;
  }

  public function getWorkingTeammates()
  {
    return $this->workingTeammates;
  }

  public function getBugsResolvingTeammates()
  {
    return $this->bugsResolvingTeammates;
  }

  public function getOpenBugsList()
  {
    return $this->openBugsList;
  }

  public function getRemainingItems()
  {
    return $this->remainingItems;
  }

  public function getFullUrl()
  {
    return $this->project->getFullUrl() . "/todo_lists/" . $this->id;
  }

  /**
   * @return bool true if the current todolist contains at least one remainingItem
   */
  public function isActive()
  {
    return !empty($this->remainingItems);
  }


  /**
   * Builds a todolist
   *
   * @param $project project containing this todolist
   */
  public function __construct($project)
  {
    $this->project=$project;
  }

  /**
   * Return the todolist's name
   */
  public function __toString()
  {
    return (string) $this->name;
  }


  /**
   * Initializes the current todolist given its json basecamp representation
   *
   * @param $tmpTodolist array json representation of a basecamp todolist
   * @return bool false if the initialization fails, true otherwise
   */
  public function init($tmpTodolist)
  {
    if( empty($tmpTodolist) || !is_array($tmpTodolist) || !isset($tmpTodolist['id']))
    {
      return false;
    }

    $this->id = $tmpTodolist['id'];
    $this->name = $tmpTodolist['name'];
    $this->complete = $tmpTodolist['complete'] == 'true';
    $this->uncompletedCount = $tmpTodolist['uncompleted-count'];
    $this->milestoneId = $tmpTodolist['milestone-id'];

    return true;
  }

  /**
   * Issues a request to basecamp API so as to load todolist items
   */
  public function loadTodoItems()
  {
    if(!$this->id)
      return;

    $todoItems = $this->project->getBasecampAPI()->get(sprintf('todo_lists/%s/todo_items.xml', $this->id));

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
   * Parses a basecamp todoitem and updates the object properties accordingly
   *
   * @param $todoItem array json representation of a basecamp todoitem
   */
  public function processTodoItem($todoItem)
  {
    if(!isset($todoItem['id']))
      return;

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
      if(!$isCompleted)
      {
        $this->openBugsList[$todoItem['id']] = array('name'=>$todoItem['content']);
      }
    }
    else
    {
      $this->totalQuotation += $quotation;
      $this->completedQuotation += ($isCompleted) ? $quotation : 0;
    }

    if(!$isCompleted)
      $this->remainingItems[$todoItem['id']] = array('name'=>$todoItem['content']);

    if(!$isCompleted)
    {
      if(isset($todoItem['responsible-party-id']))
      {
        // Add working teammate
        $this->addTeammate($this->workingTeammates, $todoItem['responsible-party-id']);

        // Add bug resolving teammate
        if($isBug)
          $this->addTeammate($this->bugsResolvingTeammates, $todoItem['responsible-party-id']);

      }
    }
  }

  protected function addTeammate(&$teamArray, $teammateId)
  {
    $team = $this->project->getTeam();
    if(!is_null($teammateId) && isset($team[$teammateId]))
    {
      if(!isset($teamArray[$teammateId]))
      {
        $teamArray[$teammateId] = $team[$teammateId];
      }
    }
  }

}