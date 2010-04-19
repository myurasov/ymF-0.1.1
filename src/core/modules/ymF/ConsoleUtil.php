<?php

/**
 * Command-line interface utility routines
 *
 * @copyright 2010 Misha Yurasov
 * @package ymF
 */

namespace ymF;

use ymF\Storage\Storage;
use ymF\Storage\HostInterface as StorageHostInterface;
use ymF\Exception;

class ConsoleUtil implements StorageHostInterface
{
  // <Constants>

  // Parameter type constants

  const PARAM_TYPE_AUTO     = 0;
  const PARAM_TYPE_INTEGER  = 1;
  const PARAM_TYPE_STRING   = 2;
  const PARAM_TYPE_BOOLEAN  = 3;
  const PARAM_TYPE_ARRAY    = 4;
  const PARAM_TYPE_TIME_SEC = 5;  // Time interval [seconds]: #s/m/h/d/w

  // Output message types

  const MESSAGE_ERROR       = 'e';  // Error messages
  const MESSAGE_STATUS      = 's';  // Startup and finish messages
  const MESSAGE_INFORMATION = 'i';  // Process information messages

  // Verbocity-specific flags

  const VERB_SILENSE      = '-';  // Be quiet
  const VERB_PROGRESS     = 'p';  // Progress

  // Logging-specific flags

  const LOG_DISABLE     = '-';  // Disable logging
  const LOG_PROGRESS    = 'p';  // Create progress file
  const LOG_OVERWRITE   = 'o';  // Overwrite log file (otherwise - append)

  // <Variables>

  private $declared_parameters = array();       // Expected script parameters
  private $parameters_read = false;             // Are parameters already read?
  private $progress_last_item = 0;              // Last item number of progress update
  private $progress_last_time = 0.0;            // Last time of progress update
  private $progress_start_time = 0.0;           // Start time of progress
  private $verbocity_options = array();         // Parsed verbopcity options
  private $logging_options = array();           // Parsed logging options
  private $progress_tags = array();             // Progress format tags
  private $progress_tag_names = array();        // Tags names cache
  private $progress_last_console_output = null; // Progress console output cache
  private $progress_last_file_output = '';      // Progress file output cache
  private $progress_rotator_index = 0;          // Progress rotator element index
  private $progress_refresh_interval = null;    // Progrtess refresh interval
  private $message_log_fp = null;               // Message log file pointer
  private $message_log_start_time = 0.0;        // Message log start time
  private $time_started = 0.0;                  // Start time
  private $time_total = 0.0;                    // Total time
  private $started = false;                     // start() called
  private $ended = false;                       // end() called
  
  // <editor-fold defaultstate="collapsed" desc="Default options">

  private $default_options = array(

    // Script name
    'script_name' => '',

    // Script version
    'script_version' => '',

    // Script detailed description
    'script_description' => '',

    // Maximum width of text output
    'max_output_width' => 80,

    // Default logging options
    'logging_default' => null,

    // Default verbocity options
    'verbocity_default' => null,

    // Log file path
    'log_file' => null,

    // Progress file path
    'progress_file' => null,

    // Progress bar format. Also possible: %item%, %total%, %time_passed%, %rotator%
    'progress_console_format' => '%percent% done [%bar%] left: %eta% %rotator%',

    // Digits after point in percents
    'progress_percents_precision' => 1,

    // Digits after point in speed
    'progress_speed_precision' => 2,

    // Digits after point in time
    'progress_time_precision' => 0,

    // Minimum refresh time of progress bar in console
    'progress_console_refresh_interval' => 0.5,

    // Minimum refresh time or progress file
    'progress_file_refresh_interval' => 5,

    // Total number of items for progress tracking
    'progress_items_total' => 0,

    // Rotator chars
    'progress_rotator_sequence' => array('|', '/', '-', '\\'),

    // Progress operation name
    'progress_operation_title' => '',

    // Progress file format
    'progress_log_format' => '',

    // Automatic start and end status messages (%time_current% for time, %time_passed% for passed time)
    'status_start_message' => 'Started at %time_current%',
    'status_end_message' => 'Finished at %time_current% (+%time_passed%)',

    // Status messages time format (accepted by date() function)
    'status_time_format' => 'r', // RFC 2822 formatted date

    // Status message time passed precision
    'status_time_precision' => 3
  );

  // </editor-fold>

  // <Properties>

  /**
   * Options storage
   *
   * @property-read $options
   * @var Storage
   */
  private $options;

  // <Accessors>

  // Script parameters

  private $parameters = array();

  /**
   * Get parameter by name
   *
   * @param string $name
   * @return ?
   */
  public function getParameter($name)
  {
    if (!$this->parameters_read)
      $this->_read_parameters();
    
    if (array_key_exists($name, $this->parameters))
    {
      return $this->parameters[$name];
    }
    else
    {
      throw new Exception("Parameter '$name' is not declared or read", ERROR_MISC);
    }
  }

  /**
   * Get parameters as array
   *
   * @return array
   */
  public function getParameters()
  {
    if (!$this->parameters_read)
      $this->_read_parameters();

    return $this->parameters;
  }

  //

  /**
   * Get time passed since start() or __construct()
   *
   * @param boolean $return_as_string
   * @return float|string Time passed
   */
  public function getTimePassed($return_as_string = false)
  {
    // Update time passed
    
    if (!$this->ended)
      $this->time_total = microtime(true) - $this->time_started;

    return $return_as_string
      ? Utils::formatTime($this->time_total, $this->options['status_time_precision'], true, 1, true)
      : $this->time_total;
  }

  // <Public functions>

  /**
   * Constructor
   *
   * @param $options array Options
   */
  public function __construct($options = array())
  {
    // Save start time (assumed to be overwritten by $this->start() call)
    $this->time_started = microtime(true);

    // Set initial looging and verbocity options
    $this->_init_logging_options();
    $this->_init_verbocity_options();

    // Initialize user options

    $this->_init_default_options();
    $this->options = new Storage($this->default_options);
    $this->options->set($options);

    // Declare standard parameters

    $this->declareParameter('help', '?', false, self::PARAM_TYPE_BOOLEAN, 'Display help');

    $this->declareParameter('logging', 'l', $this->options['logging_default'],
      self::PARAM_TYPE_STRING, 'Logging options');

    $this->declareParameter('verbocity', 'v', $this->options['verbocity_default'],
      self::PARAM_TYPE_STRING, 'Verbocity options');
  }

  /**
   * Destructor
   */
  public function  __destruct()
  {
    if ($this->started && !$this->ended)
      $this->end();

    // Close log file
    $this->_log_end();
  }

  /**
   * Properties getter
   */
  public function __get($name)
  {
    switch ($name)
    {
      case 'options':
        return $this->$name;
        break;

      default:
        throw new Exception("Property '" . __CLASS__ . "::$name' doesn't exist", ERROR_MISC);
        break;
    }
  }

  /**
   * Called when option is changed
   * 
   * @param string $name
   * @param mixed $value
   * @return boolean
   */
  public function validateField(Storage $storage, $name, $value)
  {
    return true;
  }

  /**
   * Should be called before *any* work is started
   */
  public function start()
  {
    // Save start time
    $this->time_started = microtime(true);

    // Output start message

    if ($this->options['status_start_message'] != '')
      $this->status(str_replace('%time_current%', 
        date($this->options['status_time_format']),
        $this->options['status_start_message']));

    // Sarted
    $this->started = true;
  }

  /**
   * Called after *all* work is done
   * Logging is not shut down at this point
   */
  public function end()
  {
    // Save total work time
    $this->time_total = microtime(true) - $this->time_started;

    // Erase progress
    $this->_erase_progress();

    // Output end message

    if ($this->options['status_end_message'] != '')
      $this->status(str_replace(
        array('%time_current%', '%time_passed%'),
        array(date($this->options['status_time_format']), $this->getTimePassed(true)),
        $this->options['status_end_message']));

    // end() already called
    $this->ended = true;
  }

  /**
   * Declare required parameter
   *
   * @param string $name
   * @param string $alias
   * @param mixed $default_value
   * @param mixed $type
   * @param string $description
   */
  public function declareParameter($name, $alias, $default_value = null, $type = self::PARAM_TYPE_AUTO, $description = '')
  {
    $this->declared_parameters[$name] = array('alias' => $alias,
      'default' => $default_value, 'type' => $type, 'desc' => $description);
    $this->parameters_read = false; // Parameters need to be read
  }

  /*
    Test CLI util v. 0.1
    --------------------

      Lorem ipsum dolor sit amet lorem ipsum dolor sit amet ipsum dolor sit amet
      ipsum dolor sit amet ipsum dolor sit amet ipsum dolor sit amet

    Parameters: name (alias) [type]; default: default value
    -------------------------------------------------------

      help (?) [boolean]; default: true

        Display this help

      verbocity (v) [string]; default: sei

        Verbocity level
  */

  /**
   * Display help message
   */
  public function displayHelp()
  {
    // Name and version

    $text = sprintf("%s v. %s", $this->options['script_name'], $this->options['script_version']);
    $underline = str_repeat('-', strlen($text));
    echo "$underline\n$text\n$underline";

    // Description

    if ($this->options['script_description'] != '')
    {
      $text = Utils::textAlign($this->options['script_description'], Utils::TEXT_ALIGN_LEFT,
        $this->options['max_output_width'] - 2, "\n", true, 1, 0);
      $text = Utils::textIndent($text, '  ', 1, "\n");
      echo "\n\n$text";
    }

    // Parameters

    if (count($this->declared_parameters) > 0)
    {
      $text = "Parameters";
      $underline = str_repeat('-', strlen($text));
      echo "\n\n$text\n$underline";

      foreach ($this->declared_parameters as $declared_parameter_name => $declared_parameter)
      {
        switch ($this->_get_declared_parameter_type($declared_parameter_name))
        {
          case self::PARAM_TYPE_INTEGER:
          {
            $type_name = 'integer';
            $default_value = strval($declared_parameter['default']);
            break;
          }

          case self::PARAM_TYPE_STRING:
          {
            $type_name = 'string';
            $default_value = '"'. $declared_parameter['default'] . '"';
            break;
          }

          case self::PARAM_TYPE_BOOLEAN:
          {
            $type_name = 'boolean';
            $default_value = $declared_parameter['default'] ? 'true' : 'false';
            break;
          }

          case self::PARAM_TYPE_ARRAY:
          {
            $type_name = 'array';
            $default_value = $declared_parameter['default'];
            break;
          }

          case self::PARAM_TYPE_TIME_SEC:
          {
            $type_name = 'time in seconds';
            $default_value = $declared_parameter['default'] .
              ' (' . Utils::formatTime($this->_time_sec_to_int($declared_parameter['default'])) . ')';
            break;
          }

          default:
          {
            throw new Exception('Wrong parameter type for "' . $declared_parameter_name . '"', ERROR_MISC);
            break;
          }
        }

        // Parameter usage
        $text = printf("\n\n  * %s (%s) [%s]; default: %s",
          $declared_parameter_name, $declared_parameter['alias'], $type_name, $default_value);
        $text = Utils::textAlign($text, Utils::TEXT_ALIGN_LEFT,
          $this->options['max_output_width'] - 2, "\n", true, 1, 0);

        // Parameter description
        $text = Utils::textAlign($declared_parameter['desc'], Utils::TEXT_ALIGN_LEFT,
          $this->options['max_output_width'] - 4, "\n", true, 1, 0);
        $text = Utils::textIndent($text, '  ', 2);
        echo "\n\n$text";
      }
    }
  }

  /**
   * Reset progress
   *
   * @param array $options
   */
  public function resetProgress($options = array())
  {
    if ($this->logging_options[self::LOG_PROGRESS] 
      || $this->verbocity_options[self::VERB_PROGRESS])
    {
      // Update options:

      $this->options->set($options);

      // Reset progress variables
      
      $this->progress_tags['%total%'] = strval($this->options->get('progress_items_total'));
      $this->progress_tags['%title%'] = $this->options->get('progress_operation_title');
      $this->progress_last_time = 0.0;
      $this->progress_last_console_output = null;
      $this->progress_last_file_output = '';
      $this->progress_rotator_index = 0;

      // Calculate progress refresh interval
      $this->_calculate_progress_refresh_intertval();
    }
  }

  /**
   * Update progress information
   *
   * @param integer $current_item
   */
  public function updateProgress($current_item)
  {
    // $this->progress_refresh_interval === null if no progress options are set
    if (!is_null($this->progress_refresh_interval))
    {
      // Time passed since last call
      $progress_info_time_diff = microtime(true) - $this->progress_last_time;

      // Is progress refresh interval passed?
      $time_for_progress = $progress_info_time_diff >= $this->progress_refresh_interval;

      // Is last item reached?
      $last_item_reached = $current_item >= $this->options->get('progress_items_total');

      if ($time_for_progress  || $last_item_reached)
      {
        // Calculate progress information
        if ($this->progress_last_time == 0) // First call
        {
          // Reset progress
          $this->resetProgress();

          $this->progress_start_time = microtime(true); // Start time
          $this->progress_last_time = $this->progress_start_time; // Last time
          $this->progress_last_item = $current_item; // Last percent

          // Mark progress information as unknown

          $progress_info_eta = -1;
          $progress_info_speed_curr = -1;
          $progress_info_speed_avg = -1;
          $progress_info_time_passed = -1;
          $progress_info_time_diff = -1;

          // Refresh progress (in console and in file)
          $refresh_progress = true;
        }
        else // Sequential call
        {
          $progress_info_time_diff = microtime(true) - $this->progress_last_time; // Current time difference

          $this->progress_last_time += $progress_info_time_diff; // progress_last_time = microtime()
          $items_diff = $current_item - $this->progress_last_item; // Number difference
          $this->progress_last_item = $current_item; // Last item

          $progress_info_time_passed = $this->progress_last_time - $this->progress_start_time; // Total time difference
          $progress_info_speed_avg = $current_item / $progress_info_time_passed; // [% / sec]
          $progress_info_speed_curr = $items_diff / $progress_info_time_diff; // [% / sec]
          $progress_info_eta = ($this->options['progress_items_total'] - $current_item)
            / $progress_info_speed_avg; // Estimated time left [sec]
        }

        // Compose progress text parts:

        // Done part
        $progress_info_done_part = $current_item / $this->options['progress_items_total'];

        // %percent% (41.5%)
        $this->progress_tags['%percent%'] = sprintf('%.'
          . $this->options['progress_percents_precision'] . 'f%%', $progress_info_done_part * 100);

        // %eta% - time left
        $this->progress_tags['%eta%'] = $progress_info_eta == -1 ? '?'
          : Utils::formatTime($progress_info_eta, $this->options['progress_time_precision'], true, 1, true);

        // %time_passed% - time passed
        $this->progress_tags['%time_passed%'] = $progress_info_time_passed == -1 ? '?'
          : Utils::formatTime($progress_info_time_passed, $this->options['progress_time_precision'], true, 1, true);

        // %item% - current item
        $this->progress_tags['%item%'] = strval($current_item);

        // %speed_avg% - average speed
        $this->progress_tags['%speed_avg%'] = $progress_info_speed_avg == -1 ? '?'
          : sprintf('%.' . $this->options['progress_speed_precision'] . 'f/s',
            $progress_info_speed_avg);

        // %speed_cur% - current speed
        $this->progress_tags['%speed_cur%'] = $progress_info_speed_curr == -1 ? '?'
          : sprintf('%.' . $this->options['progress_speed_precision'] . 'f/s',
            $progress_info_speed_curr);

        // %rotator%
        $this->progress_tags['%rotator%'] = $this->options['progress_rotator_sequence'][$this->progress_rotator_index];
        $this->progress_rotator_index = ++$this->progress_rotator_index % count($this->options['progress_rotator_sequence']);

        // Draw progress bar

        if ($this->verbocity_options[self::VERB_PROGRESS] &&
          ($progress_info_time_passed >= $this->options['progress_console_refresh_interval']))
            $this->_display_progress($progress_info_done_part);

        // Write progress file

        if ($this->logging_options[self::LOG_PROGRESS] &&
          ($progress_info_time_passed >= $this->options['progress_file_refresh_interval']))
            $this->_log_progress($progress_info_done_part);
      }
    }
  }

  /**
   * Output status message
   *
   * @param string $message
   */
  public function status($message)
  {
    $this->out(self::MESSAGE_STATUS, $message);
  }

  /**
   * Output info message
   *
   * @param string $message
   */
  public function info($message)
  {
    $this->out(self::MESSAGE_INFORMATION, $message);
  }

  /**
   * Output error message
   *
   * @param string $message
   */
  public function error($message)
  {
    $this->out(self::MESSAGE_ERROR, $message);
  }

  /**
   * Output message to console and log
   * Checks if message is allowed by verbocity and logging options
   *
   * @param string $meesage_type
   * @param string $message
   */
  public function out($message_type, $message)
  {
    if ($this->verbocity_options[$message_type])
    {
      // Erase progress
      $this->_erase_progress();

      // Output message to console
      echo $message . "\n";
    }

    if ($this->logging_options[$message_type])
    {
      $this->_log($message);
    }
  }

  // <Private functions>

  /**
   * Is message log enabled?
   * @return boolean
   */
  private function _message_log_enabled()
  {
    return $this->logging_options[self::MESSAGE_STATUS] ||
      $this->logging_options[self::MESSAGE_ERROR] || 
      $this->logging_options[self::MESSAGE_INFORMATION];
  }

  /**
   *
   * @param <type> $message Log message
   */
  private function _log($message)
  {
    // Open log file on first call

    if (is_null($this->message_log_fp))
      $this->_log_start();

    // Log message

    if ($this->message_log_fp)
    {
      fwrite($this->message_log_fp,
        sprintf("[+%.3fs] %s\n", microtime(true) - $this->message_log_start_time, $message));
    }
  }

  /**
   * Start logging
   */
  private function _log_start()
  {
    if ($this->_message_log_enabled())
    {
      $new_log_file = file_exists($this->options['log_file'])
        && (filesize($this->options['log_file']) > 0);

      if (!($this->message_log_fp = @fopen($this->options['log_file'], $this->logging_options[self::LOG_OVERWRITE] ? 'w' : 'a')))
      {
        trigger_error("Failed to open log file '{$this->options['log_file']}' for writing", E_USER_WARNING);
      }
      else
      {
        $this->message_log_start_time = microtime(true);

        // Separator
        if (!$this->logging_options[self::LOG_OVERWRITE] && $new_log_file)
          fwrite($this->message_log_fp, "\n---\n\n");

        // Write header
        fwrite($this->message_log_fp, '[Log started at ' . date('r') . "]\n");
      }
    }
  }

  /**
   * End logging
   */
  private function _log_end()
  {
    if ($this->_message_log_enabled() && $this->message_log_fp)
    {
      // Write footer
      fwrite($this->message_log_fp,
        sprintf("[Log finished at %s (+ %.3fs)]\n",
          date('r'), microtime(true) - $this->message_log_start_time));

      // Close log file
      fclose($this->message_log_fp);

      // Unset log file pointer
      $this->message_log_fp = null;
    }
  }

  /**
   * Read parameters
   *
   * @return array Parameters array || false
   */
  private function _read_parameters()
  {
    // Read arguments
    $args = $this->_read_arguments();

    if (count($this->declared_parameters) > 0)
    {
      foreach ($this->declared_parameters as $param_name => $param_info)
      {
        $arg = null;

        // Read argument by parameter name or alias

        if (array_key_exists($param_name, $args))
        {
          $arg = $args[$param_name];
        }
        else if ($param_info['alias'] != '' && array_key_exists($param_info['alias'], $args))
        {
          $arg = $args[$param_info['alias']];
        }

        // Read parameter
        $this->parameters[$param_name] = $this->_read_parameter_value($arg, $param_name);

        // Create alias for parameter
        $this->parameters[$param_info['alias']] =& $this->parameters[$param_name];
      }

      // Initialization after parameters are read
      $this->_init_from_parameters();

      // Parameters already read
      $this->parameters_read = true;

      return $this->parameters;
    }
  }

  /**
   * Set initial logging options
   */
  private function _init_logging_options()
  {
    // Initial logging options

    $this->logging_options[self::MESSAGE_STATUS] = false;
    $this->logging_options[self::MESSAGE_ERROR] = false;
    $this->logging_options[self::MESSAGE_INFORMATION]= false;
    $this->logging_options[self::LOG_PROGRESS] = false;
    $this->logging_options[self::LOG_OVERWRITE] = false;
  }

  /**
   * Set initial verbocity options
   */
  private function _init_verbocity_options()
  {
    // Initial logging options

    $this->verbocity_options[self::MESSAGE_STATUS] = false;
    $this->verbocity_options[self::MESSAGE_ERROR] = false;
    $this->verbocity_options[self::MESSAGE_INFORMATION]= false;
    $this->verbocity_options[self::VERB_PROGRESS]= false;
  }

  /**
   * Initialization after parameters are read
   */
  private function _init_from_parameters()
  {
    // Read verbocity options

    $this->verbocity_options[self::MESSAGE_STATUS] = $this->_have_verbocity_flag(self::MESSAGE_STATUS);
    $this->verbocity_options[self::MESSAGE_ERROR] = $this->_have_verbocity_flag(self::MESSAGE_ERROR);
    $this->verbocity_options[self::MESSAGE_INFORMATION]= $this->_have_verbocity_flag(self::MESSAGE_INFORMATION);
    $this->verbocity_options[self::VERB_PROGRESS]= $this->_have_verbocity_flag(self::VERB_PROGRESS);

    // Read logging options

    $this->logging_options[self::MESSAGE_STATUS] = $this->_have_logging_flag(self::MESSAGE_STATUS);
    $this->logging_options[self::MESSAGE_ERROR] = $this->_have_logging_flag(self::MESSAGE_ERROR);
    $this->logging_options[self::MESSAGE_INFORMATION]= $this->_have_logging_flag(self::MESSAGE_INFORMATION);
    $this->logging_options[self::LOG_PROGRESS] = $this->_have_logging_flag(self::LOG_PROGRESS);
    $this->logging_options[self::LOG_OVERWRITE] = $this->_have_logging_flag(self::LOG_OVERWRITE);

    // Initialize progress
    if ($this->logging_options[self::LOG_PROGRESS] || $this->verbocity_options[self::VERB_PROGRESS])
      $this->_init_progress();
  }

  /**
   * Initialise progress-related varibles
   */
  private function _init_progress()
  {
    // Init text parts array:

    // Text parts' values
    $this->progress_tags = array(
      '%total%' => '',
      '%percent%' => '',
      '%eta%' => '',
      '%time_passed%' => '',
      '%item%' => '',
      '%speed_avg%' => '',
      '%speed_cur%' => '',
      '%rotator%' => '',
      '%title%' => ''
    );

    // Text parts' keys
    $this->progress_tag_names =
      array_keys($this->progress_tags);

    // Progress title
    $this->options['progress_operation_title'] = $this->options['script_name'];

    // Calculate progress refresh interval
    $this->_calculate_progress_refresh_intertval();
  }

  /**
   * Calculate progress refresh interval
   */
  private function _calculate_progress_refresh_intertval()
  {
    if ($this->logging_options[self::LOG_PROGRESS])
    {
      if ($this->verbocity_options[self::VERB_PROGRESS])
      {
        $this->progress_refresh_interval =
          min($this->options['progress_console_refresh_interval'],
          $this->options['progress_file_refresh_interval']);
      }
      else
      {
        $this->progress_refresh_interval =
          $this->options->get('progress_file_refresh_interval');
      }
    }
    else
    {
      if ($this->verbocity_options[self::VERB_PROGRESS])
      {
        $this->progress_refresh_interval =
          $this->options->get('progress_console_refresh_interval');
      }
      else
      {
        $this->progress_refresh_interval = null; // No progress
      }
    }
  }

  /**
   * Display progress in console
   */
  private function _display_progress($progress_info_done_part)
  {
    // Default: '%percent% [%bar%] Left: %eta%'
    // Also possible: %item%, %total%, %time_passed%, %speed_avg%, %speed_cur%
    $progress_text = $this->options['progress_console_format'];

    // Replace tags
    $progress_text = str_replace($this->progress_tag_names, $this->progress_tags, $progress_text);

    if (strstr($progress_text, '%bar%') !== false)
    {
      // Insert progress bar:

      // Progress bar length
      $progress_bar_length = $this->options['max_output_width'] -
        strlen($progress_text) -  5 /* == strlen('%bar%') */;

      // Create progress bar
      $progress_bar = $this->_create_progress_bar($progress_info_done_part, $progress_bar_length);

      // Insert bar
      $progress_text = str_replace('%bar%', $progress_bar, $progress_text);
    }
    else
    {
      // Pad with spaces to $this->options['max_output_width']
      $progress_text = str_pad($progress_text, $this->options['max_output_width'], ' ', STR_PAD_RIGHT);
    }

    // Output progress text to console

    if ($this->progress_last_console_output != $progress_text) // Prevent output of the same text
    {
      // Erase progress
      $this->_erase_progress();

      // Print progress
      echo $progress_text;

      $this->progress_last_console_output = $progress_text;
    }
  }

  /**
   * Erase console progress
   */
  private function _erase_progress()
  {
    if (!is_null($this->progress_last_console_output))
    {
      echo "\x0D"; // CR (caret return)
      echo str_repeat(' ', strlen($this->progress_last_console_output));
      echo "\x0D";
    }
  }

  /**
   * Log progress
   *
   * @param float $progress_info_done_part
   */
  private function _log_progress($progress_info_done_part)
  {
    // Create progress text:

    $progress_text = $this->options['progress_log_format'];

    // Replace tags
    $progress_text = str_replace($this->progress_tag_names, $this->progress_tags, $progress_text);

    // Insert progress bar

    if (strstr($progress_text, '%bar%') !== false)
    {
      // Determine progress bar length

      $m = array();
      preg_match('/^.*%bar%.*$/m', $progress_text, $m);
      $progress_bar_length = $this->options['max_output_width'] - strlen($m[0]) -  5 /* == strlen('%bar%') */;

      // Create progress  bar
      $progress_bar = $this->_create_progress_bar($progress_info_done_part, $progress_bar_length);

      // Insert bar
      $progress_text = str_replace('%bar%', $progress_bar, $progress_text);
    }

    // Write to file

    if ($fp = @fopen($this->options['progress_file'], 'w'))
    {
      fwrite($fp, $progress_text);
      fclose($fp);
    }
    else
    {
      trigger_error("Failed opening progress file '{$this->options['progress_file']}'", E_USER_WARNING);
    }
  }

  /**
   * Create progress bar
   *
   * @param float $done_part
   * @param integer $length
   * @return string
   */
  private function _create_progress_bar($done_part, $length)
  {
    if ($length > 0)
    {
      $done_length = round($done_part * $length);
      return str_repeat('#', $done_length) . str_repeat('-', $length - $done_length);
    }
    else
    {
      return '';
    }
  }

  /**
   * Initialize options' default values
   * They are independent of options passed by user
   */
  protected function _init_default_options()
  {
    // Logging options
    $this->default_options['logging_default'] = self::MESSAGE_STATUS . self::MESSAGE_ERROR
      . self::MESSAGE_INFORMATION . self::LOG_OVERWRITE;

    // Verbocity options
    $this->default_options['verbocity_default'] = self::MESSAGE_STATUS . self::MESSAGE_ERROR
      . self::VERB_PROGRESS;

    // Log file
    $this->default_options['log_file'] = PATH_LOGS . '/' . pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME) . '.log';

    // Progress file
    $this->default_options['progress_file'] = PATH_LOGS . '/' . pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME) . '.progress';

    // Progress file format
    $this->default_options['progress_log_format'] = "%title%\n\n%item%/%total% [%bar%] %percent%\n\n" .
      "Speed (cur):  %speed_cur%\nSpeed (avg):  %speed_avg%\nTime elapsed:\t%time_passed%\nTime left:    ~ %eta%";
  }

  /**
   * Check if verbocity option is set
   * 
   * @param string $name
   * @return boolean 
   */
  private function _have_verbocity_flag($name)
  {
    return strstr($this->parameters['verbocity'], $name) !== false;
  }

  /**
   * Check if logging option is set
   *
   * @param string $name
   * @return boolean
   */
  private function _have_logging_flag($name)
  {
    return strstr($this->parameters['logging'], $name) !== false;
  }

  /**
   * Convert parameter value from string representation to php variable
   *
   * @param ? $arg_value
   * @param string $declared_parameter_name
   * @return number|string|boolean|array
   */
  private function _read_parameter_value($arg_value, $declared_parameter_name)
  {
    $declared_parameter = $this->declared_parameters[$declared_parameter_name];
    $type = $this->_get_declared_parameter_type($declared_parameter_name); // Get parameter type

    if (is_null($arg_value))
    {
      $arg_value = $declared_parameter['default'];
    }

    if ($type == self::PARAM_TYPE_INTEGER)
    {
      return intval($arg_value);
    }
    else if ($type == self::PARAM_TYPE_STRING)
    {
      return strval($arg_value);
    }
    else if ($type == self::PARAM_TYPE_BOOLEAN)
    {
      if ($arg_value === '')
      {
        // Boolean parameter without value ==> true.
        // Example: test.php boolean_val
        return true;
      }
      else
      {
        return Utils::str2Bool($arg_value);
      }
    }
    else if ($type == self::PARAM_TYPE_ARRAY)
    {
      return Utils::explodeString($arg_value, '+');
    }
    else if ($type == self::PARAM_TYPE_TIME_SEC) // Time in seconds
    {
      return $this->_time_sec_to_int($arg_value);
    }
    else
    {
      throw new Exception('Wrong parameter type for "' . $declared_parameter_name . '"', ERROR_MISC);
    }
  }

  /**
   * Converts PARAM_TYPE_TIME_SEC to integer number of seconds
   *
   * @param string $time_sec
   * @return integer
   */
  private function _time_sec_to_int($time_sec)
  {
    $time_val = 0;
    $time_unit = '';
    sscanf($time_sec, '%d%s', $time_val, $time_unit);

    $time_units_in_seconds = array('' => 1, 's' => 1, 'm' => 60,
      'h' => 60 * 60, 'd' => 60 * 60 * 24, 'w' => 60 * 60 * 24 * 7);
    $time_sec = $time_val * $time_units_in_seconds[strtolower($time_unit)];

    return $time_sec;
  }

  /**
   * Detects declared parameter type
   *
   * @param string $declared_parameter_name
   * @return integer
   */
  private function _get_declared_parameter_type($declared_parameter_name)
  {
    $declared_parameter = $this->declared_parameters[$declared_parameter_name];
    $type = $declared_parameter['type'];
    $default = $declared_parameter['default'];

    if ($declared_parameter['type'] == self::PARAM_TYPE_AUTO)
    {
      if (is_null($declared_parameter['default']))
      {
        throw new Exception('Unknown parameter type for "' . $declared_parameter_name . '"', ERROR_MISC);
      }
      else
      {
        if (is_integer($declared_parameter['default']))
        {
          return self::PARAM_TYPE_INTEGER;
        }
        else if (is_string($declared_parameter['default']))
        {
          return self::PARAM_TYPE_STRING;
        }
        else if (is_bool($declared_parameter['default']))
        {
          return self::PARAM_TYPE_BOOLEAN;
        }
        else if (is_array($declared_parameter['default']))
        {
          return self::PARAM_TYPE_ARRAY;
        }
      }
    }
    else
    {
      return $declared_parameter['type'];
    }
  }

  /**
   *  Reads command line arguments (arg1:val1 arg2:val2 ...)
   *
   * @return integer Number of arguments
   */
  private function _read_arguments()
  {
    global $_SERVER;

    $args = array();

    for ($i = 1; $i <= $_SERVER['argc'] - 1; $i++)
    {
      $c_arg = $_SERVER['argv'][$i];

      if (preg_match('/^(.*?):(.*?)$/', $c_arg, $regs))
      {
        $arg_name = $regs[1];
        $arg_val = $regs[2];
      }
      else
      {
        $arg_name = $c_arg;
        $arg_val = '';
      }

      $args[$arg_name] = $arg_val;
    }

    return $args;
  }
}