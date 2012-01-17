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

  protected $milestones = array();
  protected $openBugsCount = 0;

  protected $workdays;
  protected $holidays;
  protected $team;
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

    $this->updateBasecampAPI();
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
    if($this->fetchBugsFromSpecialTodoList() &&
        substr(strtolower($tmpTodolist['name']), 0, 3) == $this->bugTodoListName &&
        $tmpTodolist['complete'] == 'false')
    {
      $this->openBugsCount += $tmpTodolist['uncompleted-count'];
    }

    $milestoneId = $tmpTodolist['milestone-id'];

    // No related milestone
    if(is_array($milestoneId))
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
      $this->milestones[$milestoneId]->processTodoList($tmpTodolist);
    }

  }


}