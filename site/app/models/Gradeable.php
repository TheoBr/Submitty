<?php

namespace app\models;

use app\exceptions\NotImplementedException;
use app\libraries\Core;
use app\libraries\FileUtils;
use app\libraries\GradeableType;
use app\libraries\Utils;

/**
 * Class Gradeable
 *
 * Model of the current gradeable being looked at for submission by the student. This information is a combination of
 * the info contained in the form json file or database, various result json files, and the version history file in the
 * submission folder. This abstract class is extended by GradeableFile (which loads the form json file) and
 * GradeableDb (which loads the data from the database). Additionally, it'll hold the relevant information necessary
 * for the TA to be able to grade this.
 */
abstract class Gradeable {

    /** @var Core */
    protected $core;
    
    /** @var string $id Id of the gradeable (must be unique) */
    protected $id;
    
    /** @var string $name Name of the gradeable */
    protected $name;
    
    /** @var int $type GradeableType set for this Gradeable */
    protected $type;
    
    /** @var string $ta_instructions Instructions to give to TA for grading */
    protected $ta_instructions = "";
    
    /** @var bool $team_gradeable Is this a team gradeable */
    protected $team_gradeable = false;
    
    /** @var string $bucket Iris Bucket to place gradeable */
    protected $bucket = null;
    
    /** @var int $minimum_grading_group Minimum group that's allowed to submit grades for this gradeable */
    protected $minimum_grading_group = 1;

    /** @var \DateTime|null $ta_view_date Date for when grading can view */
    protected $ta_view_date = null;

    /** @var \DateTime|null $grade_start_date Date for when grading can start */
    protected $grade_start_date = null;

    /** @var \DateTime|null $grade_released_date Date for when the grade will be released to students */
    protected $grade_released_date = null;
    
    protected $ta_grades_released = false;

    /** @var bool Should the gradeable be graded by registration section (or by rotating section) */
    protected $grade_by_registration = true;
    
    
    /* Config variables that are only for electronic submissions */
    protected $has_config = false;
    
    /** @var \DateTime|null $open_date When is an electronic submission open to students */
    protected $open_date = null;

    /** @var \DateTime|null $due_date Due date for an electronic submission */
    protected $due_date = null;

    /** @var bool $is_repository Is the electronic submission a SVN repository or allow uploads */
    protected $is_repository = false;

    /** @var string $subdirectory What is the subdirectory for SVN */
    protected $subdirectory = "";

    /** @var int $late_days Number of days you can submit */
    protected $late_days = 0;

    /** @var string Url to any instructions for the gradeable for students */
    protected $instructions_url = "";

    /** @var string Path to the config.json file used to build the config/build/build_XXX.json file */
    protected $config_path = "";

    /** @var float Precision to allow for inputting points when grading (such that precision of 0.5 then allows grades
     * of 0, 0.5, 1, 1.5, etc.) */
    protected $point_precision = 0;

    /** @var bool Is there any TA grading to be done for this gradeable (ie. any rubric questions) */
    protected $ta_grading = false;
    protected $questions = array();
    
    /* Config variables that are only for checkpoints */
    protected $checkpoints = array();
    
    /* Config variables that are only for numeric/text types */
    protected $numerics = array();
    protected $texts = array();
    
    /* Config variables that are for both checkpoints and numeric/text types */
    protected $optional_ta_message = false;
    
    /* Config variables for submission details for this gradeable */
    /** @var int Max size (in bytes) allowed for the submission */
    protected $max_size = 50000;
    /** @var int Max number of submission allowed before a student starts suffering deductions every 10 submissions */
    protected $max_submissions = 20;
    
    /** @var float Non hidden, non extra credit points */
    protected $normal_points = 0;
   
    /**  @var float Non hidden points (including extra credit) */
    protected $non_hidden_points = 0;
    
    /** @var GradeableTestcase[] Autograding testcases for the gradeable */
    protected $testcases = array();
    
    /** @var string Message to show for the gradeable above all submission results */
    protected $message = "";
    
    /** @var int  */
    protected $num_parts = 1;

    /** @var string[] */
    protected $part_names = array();
    
    /* Variables for submission details (such as attempts used, etc.) */
    protected $submissions = 0;
    
    /**
     * @var int $active  The set active version for the assignment
     * @var int $current The current version of the assignment being viewed
     * @var int $highest Highest version submitted for an assignment
     */
    protected $active = -1;
    protected $current = -1;
    protected $highest = 0;
    
    protected $history = array();
    protected $versions = array();

    
    /** @var array Array of all files for a specified submission number where each key is a previous file and then each element
     * is an array that contains filename, file path, and the file size. */
    protected $submitted_files = array();
    protected $svn_files = array();
    protected $meta_files = array();
    protected $previous_files = array();
    
    protected $result_details;
    
    protected $grade_file = null;
    
    protected $in_interactive_queue = false;
    protected $grading_interactive_queue = false;
    protected $in_batch_queue = false;
    protected $grading_batch_queue = false;
    
    public function __construct(Core $core, $id) {
        $this->core = $core;
        $this->id = $id;
    }
    
    /**
     * Loads the config/build/build_*.json file for a gradeable
     */
    protected function loadGradeableConfig() {
        if ($this->type !== GradeableType::ELECTRONIC_FILE) {
            return;
        }
        
        $course_path = $this->core->getConfig()->getCoursePath();
        $details = FileUtils::readJsonFile($course_path."/config/build/build_".$this->id.".json");
        
        // Was there actually a config file to read from
        if ($details === false) {
            return;
        }
    
        $this->has_config = true;
        
        if (isset($details['max_submission_size'])) {
            $this->max_size = floatval($details['max_submission_size']);
        }
        
        if (isset($details['max_submissions'])) {
            $this->max_submissions = intval($details['max_submissions']);
        }
        
        if (isset($details['assignment_message'])) {
            $this->message = Utils::prepareHtmlString($details['assignment_message']);
        }
        
        if (isset($details['num_parts'])) {
            $this->num_parts = intval($details['num_parts']);
            if ($this->num_parts < 1) {
                $this->num_parts = 1;
            }
        }
        
        for ($i = 1; $i <= $this->num_parts; $i++) {
            $this->previous_files[$i] = array();
            $j = $i - 1;
            if (isset($details['part_names']) && isset($details['part_names'][$j]) &&
                trim($details['part_names'][$j]) !== "") {
                $this->part_names[$i] = $details['part_names'][$j];
            }
            else {
                $this->part_names[$i] = "Part ".$i;
            }
        }

        if (isset($details['testcases'])) {
            foreach ($details['testcases'] as $idx => $testcase) {
                $testcase = new GradeableTestcase($this->core, $testcase, $idx);
                $this->testcases[] = $testcase;
                if ($testcase->getNormalPoints() >= 0) {
                  $this->normal_points += $testcase->getNormalPoints();
                }
                if ($testcase->getNonHiddenPoints() >= 0) {
                  $this->non_hidden_points += $testcase->getNonHiddenPoints();
                }
            }
        }
    }
    
    /**
     * Sets the grading queue status of the gradeable. We don't really care
     */
    public function setQueueStatus() {
        $interactive_queue = $this->core->getConfig()->getSubmittyPath()."/to_be_graded_interactive";
        $batch_queue = $this->core->getConfig()->getSubmittyPath()."/to_be_graded_batch";
        
        $queue_file = implode("__", array($this->core->getConfig()->getSemester(),
                                          $this->core->getConfig()->getCourse(), $this->id,
                                          $this->core->getUser()->getId(), $this->current));
        $grade_file = "GRADING_".$queue_file;
        
        $this->in_interactive_queue = file_exists($interactive_queue."/".$queue_file);
        $this->in_batch_queue = file_exists($batch_queue."/".$queue_file);
        $this->grading_interactive_queue = file_exists($interactive_queue."/".$grade_file);
        $this->grading_batch_queue = file_exists($batch_queue."/".$grade_file);
    }
    
    /**
     * Loads submission details about an electronic submission from the submissions/ and
     * results/ directories and their respective json files.
     */
    public function loadResultDetails() {
        if ($this->type !== GradeableType::ELECTRONIC_FILE) {
            return;
        }
        
        if (!$this->hasConfig()) {
            return;
        }
        
        $course_path = $this->core->getConfig()->getCoursePath();

        $submission_path = $course_path."/submissions/".$this->id."/".$this->core->getUser()->getId();
        $svn_path = $course_path."/checkout/".$this->id."/".$this->core->getUser()->getId();
        $results_path = $course_path."/results/".$this->id."/".$this->core->getUser()->getId();
        
        if (is_file($submission_path."/user_assignment_settings.json")) {
            $settings = FileUtils::readJsonFile($submission_path."/user_assignment_settings.json");
            $this->active = intval($settings['active_version']);
            $this->history = $settings['history'];
        }

        $versions = array_map("intval", FileUtils::getAllDirs($submission_path));
        $this->highest = Utils::getLastArrayElement($versions);
        if ($this->highest === null) {
            $this->highest = 0;
        }
        
        foreach ($versions as $version) {
            if (!is_dir($results_path."/".$version)) {
                $this->versions[$version]['status'] = false;
                $this->versions[$version]['days_late'] = 0;
                $this->versions[$version]['points'] = 0;
                $this->versions[$version]['testcases'] = array();
                continue;
            }
            
            $this->versions[$version] = FileUtils::readJsonFile($results_path."/".$version."/results.json");

            $this->versions[$version]['status'] = true;

            $results_history = FileUtils::readJsonFile($results_path."/".$version."/results_history.json");
            if ($results_history !== false) {
                $last_results_timestamp = $results_history[count($results_history)-1];
            }
            else {
                $last_results_timestamp = array('submission_time' => "UNKNOWN", "grade_time" => "UNKOWN",
                    "wait_time" => "UNKNOWN");
            }

            $this->versions[$version] = array_merge($this->versions[$version], $last_results_timestamp);

            $this->versions[$version]['days_late'] = isset($this->versions[$version]['days_late_before_extensions']) ?
                intval($this->versions[$version]['days_late_before_extensions']) : 0;
            if ($this->versions[$version]['days_late'] < 0) {
                $this->versions[$version]['days_late'] = 0;
            }
            $this->versions[$version]['num_autogrades'] = count($results_history);

            $this->versions[$version]['points'] = 0;

            for ($i = 0; $i < count($this->testcases); $i++) {
                if (!$this->testcases[$i]->isHidden()) {
                  $this->versions[$version]['points'] += $this->versions[$version]['testcases'][$i]['points_awarded'];
                }
            }
            // Clamp to zero (no negative total!)
            if ($this->versions[$version]['points'] < 0) {
              $this->versions[$version]['points'] = 0;
            }
        }
        
        $this->submissions = count($this->versions);

        if ($this->active < 0 && $this->active > $this->submissions) {
            $this->active = $this->submissions;
        }

        if (isset($_REQUEST['gradeable_version'])) {
            $this->current = intval($_REQUEST['gradeable_version']);
        }

        if ($this->current < 0 && $this->active >= 0) {
            $this->current = $this->active;
        }
        else if ($this->current > $this->submissions) {
            $this->current = $this->active;
        }
        
        $this->setQueueStatus();

        $submission_current_path = $submission_path."/".$this->current;
        $submitted_files = FileUtils::getAllFiles($submission_current_path, array(), true);
        foreach ($submitted_files as $file => $details) {
            if (substr(basename($file), 0, 1) === '.') {
                $this->meta_files[$file] = $details;
            }
            else {
                $this->submitted_files[$file] = $details;
            }
        }
    
        $svn_current_path = $svn_path."/".$this->current;
        $svn_files = FileUtils::getAllFiles($svn_current_path, array(), true);
        foreach ($svn_files as $file => $details) {
            $this->svn_files[$file] = $details;
        }
        
        if ($this->getNumParts() > 1) {
            for ($i = 1; $i <= $this->getNumParts(); $i++) {
                $this->previous_files[$i] = array();
                foreach ($this->submitted_files as $file => $details) {
                    if (substr($file, 0, strlen("part{$i}/")) === "part{$i}/") {
                        $this->previous_files[$i][$file] = $details;
                    }
                }
            }
        }
        else {
            $this->previous_files[1] = $this->submitted_files;
        }
    
        if ($this->current > 0 && $this->versions[$this->current]['status'] !== false) {
            $this->result_details = $this->versions[$this->current];
            for ($i = 0; $i < count($this->result_details['testcases']); $i++) {
                $this->testcases[$i]->addResultTestcase($this->result_details['testcases'][$i], $results_path."/".$this->current);
            }
        }
        
        $grade_file = $this->core->getConfig()->getCoursePath()."/reports/".$this->getId()."/".$this->core->getUser()->getId().".txt";
        if (is_file($grade_file)) {
            $this->grade_file = htmlentities(file_get_contents($grade_file));
        }
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }
    
    public function getType() {
        return $this->type;
    }

    public function getNumParts() {
        return $this->num_parts;
    }

    public function getPartsNames() {
        return $this->part_names;
    }

    public function getHighestVersion() {
        return $this->highest;
    }

    public function getActiveVersion() {
        return $this->active;
    }

    public function getCurrentVersion() {
        return $this->current;
    }

    public function getPreviousFiles($part = 1) {
        $part = ($this->getNumParts() < $part || $part < 1) ? 1 : $part;
        return $this->previous_files[$part];
    }
    
    public function getMaxSubmissions() {
        return $this->max_submissions;
    }
    
    public function getMaxSize() {
        return $this->max_size;
    }
    
    public function getSubmissionCount() {
        return $this->submissions;
    }
    
    public function getAllowedLateDays() {
        return $this->late_days;
    }
    
    public function getVersions() {
        return $this->versions;
    }
    
    /**
     * Returns the total number of points for testcases that are not hidden nor are extra credit
     * @return int
     */
    public function getNormalPoints() {
        return $this->normal_points;
    }
    
    public function getTotalHiddenPoints() {
        throw new NotImplementedException();
    }
    
    public function getExtraCreditPoints() {
        throw new NotImplementedException();
    }
    
    public function getHiddenExtraCreditPoints() {
        throw new NotImplementedException();
    }
    
    public function getDueDate() {
        return $this->due_date;
    }

    public function getTAViewDate(){
        return $this->ta_view_date;
    }

    public function getGradeStartDate(){
        return $this->grade_start_date;
    }
    
    public function getGradeReleasedDate(){
        return $this->grade_released_date;
    }
    
    public function getOpenDate() {
        return $this->open_date;
    }
    
    public function getDaysLate() {
        return ($this->hasResults()) ? $this->result_details['days_late'] : 0;
    }
    
    public function getInstructionsURL(){
        return $this->instructions_url;
    }
    
    /**
     * Check to see if we have the result_details array from the results directory.
     * If false, we don't want to display any result details to the user about the
     * version.
     *
     * @return bool
     */
    public function hasResults() {
        return isset($this->result_details);
    }
    
    public function getResults() {
        return $this->result_details;
    }
    
    public function getSubmittedFiles() {
        return $this->submitted_files;
    }
    
    public function getSvnFiles() {
        return $this->svn_files;
    }
    
    public function getTestcases() {
        return $this->testcases;
    }
    
    public function hasAssignmentMessage() {
        return trim($this->message) !== "";
    }
    
    public function getAssignmentMessage() {
        return $this->message;
    }
    
    public function useSvnCheckout() {
        return $this->is_repository;
    }
    
    public function hasGradeFile() {
        return $this->grade_file !== null;
    }
    
    public function getGradeFile() {
        return $this->grade_file;
    }
    
    public function useTAGrading() {
        return $this->ta_grading;
    }
    
    public function taGradesReleased() {
        return $this->ta_grades_released;
    }
    
    public function hasConfig() {
        return $this->has_config;
    }
    
    public function inInteractiveQueue() {
        return $this->in_interactive_queue;
    }
    
    public function beingGradedInteractiveQueue() {
        return $this->grading_interactive_queue;
    }
    
    public function inBatchQueue() {
        return $this->in_batch_queue;
    }
    
    public function beingGradedBatchQueue() {
        return $this->grading_batch_queue;
    }
}