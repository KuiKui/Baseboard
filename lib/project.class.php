<?php

/**
 * Basecamp project object
 */
class project
{
  protected $name;

  protected $basecampUrl;
  protected $basecampToken;
  protected $basecampId;

  protected $hudsonUrl;
  protected $hudsonJobs;

  protected $display;

  protected $milestones = array();
  protected $openBugsCount = 0;
  protected $openBugsList = array();

  protected $workdays;
  protected $holidays;
  protected $team;
  protected $bugsResolvingTeammates = array();
  protected $bugTodoListName;

  protected $basecampAPI;

  /**
   * Builds a project given its name and the project settings
   *
   * @param $name string name of the project
   * @param $properties array settings of the project
   */
  public function __construct($name, $properties)
  {
    $this->name           = $name;

    if(isset($properties['basecamp-url']))
    {
      $this->basecampUrl    = $properties['basecamp-url'];
    }
    if(isset($properties['basecamp-token']))
    {
      $this->basecampToken  = $properties['basecamp-token'];
    }
    if(isset($properties['basecamp-id']))
    {
      $this->basecampId     = $properties['basecamp-id'];
    }

    if(isset($properties['hudson-url']))
    {
      $this->hudsonUrl      = $properties['hudson-url'];
    }
    if(isset($properties['hudson-jobs']))
    {
      $this->hudsonJobs     = $properties['hudson-jobs'];
    }

    if(isset($properties['bug-todolist-name']))
    {
      $this->bugTodoListName     = $properties['bug-todolist-name'];
    }

    if(isset($properties['display']))
    {
      $this->display     = $properties['display'];
    }

    $this->updateBasecampAPI();
  }

  /**
   * Return the project's name
   */
  public function __toString()
  {
    return (string) $this->name;
  }


  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }

  public function setMilestones($milestones)
  {
    $this->milestones = $milestones;
  }
  public function getMilestones()
  {
    return $this->milestones;
  }
  public function getFullUrl()
  {
    return $this->basecampUrl . "projects/" . $this->basecampId;
  }

  /**
   * Returns the currently open bugs count in this project.
   * Depending on the configuration, these bugs will be either the open items in a special "bug" todolist or the open bug flagged items in all todolists
   *
   * @return int open bugs count
   */
  public function getOpenBugsCount()
  {
    // return bugs that are defined in todoitems
    if(!$this->fetchBugsFromSpecialTodoList())
    {
      $openBugs = 0;
      foreach($this->milestones as $milestone)
      {
        if($milestone->isPending())
          $openBugs += $milestone->getOpenBugsCount();
      }
      return $openBugs;
    }

    // return items in "bug" todolist
    return $this->openBugsCount;
  }

  /**
   * Returns the teammates currently working on bugs on this project
   * Depending on the configuration, these bugs will be either the open items in a special "bug" todolist or the open bug flagged items in all todolists
   *
   * @return int open bugs count
   */
  public function getBugsResolvingTeammates()
  {
    // return bugs that are defined in todoitems
    if(!$this->fetchBugsFromSpecialTodoList())
    {
      $teammates = array();
      foreach($this->milestones as $milestone)
      {
        if($milestone->isPending())
          $teammates += $milestone->getBugsResolvingTeammates();
      }
      return $teammates;
    }

    // return items in "bug" todolist
    return $this->bugsResolvingTeammates;
  }

  public function getOpenBugsList()
  {
    // return bugs that are defined in todoitems
    if(!$this->fetchBugsFromSpecialTodoList())
    {
      $openBugsList = array();
      foreach($this->milestones as $milestone)
      {
        if($milestone->isPending())
          $openBugsList += $milestone->getOpenBugsList();
      }
      return $openBugsList;
    }

    // return items in "bug" todolist
    return $this->openBugsList;
  }

  public function setWorkdays($workdays)
  {
    $this->workdays = $workdays;
  }
  public function getWorkdays()
  {
    return $this->workdays;
  }

  public function setHolidays($holidays)
  {
    $this->holidays = $holidays;
  }
  public function getHolidays()
  {
    return $this->holidays;
  }

  public function setTeam($team)
  {
    $this->team = $team;
  }
  public function getTeam()
  {
    return $this->team;
  }

  public function setBasecampUrl($basecampUrl)
  {
    $this->basecampUrl = $basecampUrl;
    $this->updateBasecampAPI();
  }
  public function getBasecampUrl()
  {
    return $this->basecampUrl;
  }

  public function setBasecampToken($basecampToken)
  {
    $this->basecampToken = $basecampToken;
    $this->updateBasecampAPI();
  }
  public function getBasecampToken()
  {
    return $this->basecampToken;
  }

  public function setBasecampId($basecampId)
  {
    $this->basecampId = $basecampId;
  }
  public function getBasecampId()
  {
    return $this->basecampId;
  }

  public function setHudsonUrl($hudsonUrl)
  {
    $this->hudsonUrl = $hudsonUrl;
  }
  public function getHudsonUrl()
  {
    return $this->hudsonUrl;
  }

  public function setHudsonJobs($hudsonJobs)
  {
    $this->hudsonJobs = $hudsonJobs;
  }
  public function getHudsonJobs()
  {
    return $this->hudsonJobs;
  }

  /**
   * @return basecampAPI object used to issue REST requests to basecamp API
   */
  public function getBasecampAPI()
  {
    return $this->basecampAPI;
  }

  /**
   * @return bool true if we should count the bugs count from the special "bug" todolist
   */
  private function fetchBugsFromSpecialTodoList()
  {
    return isset($this->bugTodoListName);
  }

  /**
   * Updates the basecampAPI object if basecampUrl ou basecampToken changed
   */
  protected function updateBasecampAPI()
  {
    $requestHeader = array('Accept: application/xml', 'Content-Type: application/xml');
    $this->basecampAPI = new basecampAPI(new RESTConnection($this->getBasecampUrl(), $requestHeader, $this->getBasecampToken(), 'X'));
  }


  /**
   * @return array array of teammates that are currently working on this project (ie affected to open items in pending todolists)
   */
  public function getWorkingTeammates()
  {
    $teammates = array();
    foreach($this->milestones as $milestone)
    {
      $teammates += $milestone->getWorkingTeammates();
    }
    //$teammates = array_unique($teammates);

    return $teammates;
  }

  /**
   * @return bool true if the current project contains at least one active milestone or one open bug
   */
  public function isActive()
  {
    foreach($this->milestones as $milestone)
    {
      if($milestone->isActive())
      {
        return true;
      }
    }
    return $this->openBugsCount>0;
  }

  /**
   * @return bool true if we should display the current project
   */
  public function shouldDisplay()
  {
    if($this->display=="always")
    {
      return true;
    }
    else if ($this->display=="never")
    {
      return false;
    }

    return $this->isActive();
  }

  /**
   * Issues a request to basecamp API so as to load all of the project milestones.
   * Obviously, this might lead to load unneeded milestones but depending on your project, it vastly reduces the number
   * of calls to basecamp API, thus improving load time.
   */
  public function loadMilestones()
  {
    $tmpMilestones = $this->basecampAPI->get(sprintf('projects/%s/calendar_entries/milestones.xml', $this->getBasecampId()));
    if(is_null($tmpMilestones))
    {
      return;
    }

    foreach($tmpMilestones as $tmpMilestone)
    {
      $milestone = new milestone($this);
      if($milestone->init($tmpMilestone))
      {
        $this->milestones[$milestone->getId()] = $milestone;
      }
    }
  }

  /**
   * Issues a request to basecamp API so as to load all of the project todolists.
   *
   * @param string $filter string filter used to fetch the todolists ('all', 'pending', 'finished')
   */
  public function loadTodoLists($filter = '')
  {
    $tmpTodolists = $this->basecampAPI->get(sprintf('projects/%s/todo_lists.xml?filter=%s', $this->basecampId, $filter));
    if(is_null($tmpTodolists))
    {
      return;
    }

    foreach($tmpTodolists as $tmpTodolist)
    {
      $this->processTodoList($tmpTodolist);
    }
  }


  /**
   * Parses a basecamp todolist and updates the project properties accordingly
   *
   * @param $tmpTodolist array json representation of a basecamp todolist
   */
  public function processTodoList($tmpTodolist)
  {
    $todoList = new todolist($this);

    if(!$todoList->init($tmpTodolist))
    {
      return;
    }

    // Update bugsCount + bugsResolvingTeammates for special todolist
    if($this->fetchBugsFromSpecialTodoList() &&
      substr(strtolower($todoList->getName()), 0, 3) == $this->bugTodoListName &&
      !$todoList->getComplete())
    {
      $todoList->loadTodoItems();
      $this->openBugsCount += $todoList->getUncompletedCount();
      $this->bugsResolvingTeammates += $todoList->getWorkingTeammates();
      $this->openBugsList += $todoList->getRemainingItems();
      return;
    }


    $milestoneId = $todoList->getMilestoneId();

    // No related milestone
    if(empty($milestoneId))
    {
      return;
    }

    // Add milestone if not already loaded
    if(!array_key_exists($milestoneId, $this->milestones))
    {
      $milestone = new milestone($this);

      if(!$milestone->load($milestoneId))
      {
        return;
      }

      $this->milestones[$milestoneId] = $milestone;
    }

    // Put todolist info into the milestone if it's pending
    if($this->milestones[$milestoneId]->isPending())
    {
      $this->milestones[$milestoneId]->processTodoList($todoList);
    }


  }


}