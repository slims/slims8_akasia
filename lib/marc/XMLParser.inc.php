<?php
# @Author: Waris Agung Widodo <user>
# @Date:   2017-09-19T11:40:55+07:00
# @Email:  ido.alit@gmail.com
# @Filename: Parser.inc.php
# @Last modified by:   user
# @Last modified time: 2017-09-20T12:19:25+07:00



namespace Marc;

/**
 * Parser record Marc XML
 */
class XMLParser
{

  // store parsed xml file
  protected $source;
  // Counter for index record
  protected $counter;
  // set error
  protected $error = false;
  // set messages
  protected $message;

  function __construct($source, $type = 'file', $namespace = '', $isPrefix = false)
  {

    $this->counter = 0;

    switch ($type) {
      case 'string':
        $this->source = \simplexml_load_string($source, 'SimpleXMLElement', 0, $namespace, $isPrefix);
        break;

      default:
        $this->source = \simplexml_load_file($source, 'SimpleXMLElement', 0, $namespace, $isPrefix);
        break;
    }

    if (!$this->source) {
      $this->error = true;
      $this->messagge = 'Can\'t load MARC Source.';
    }
  }

  public function isError()
  {
    return $this->error;
  }

  public function getMessage()
  {
    return $this->messagge;
  }

  public function count()
  {
    return count($this->source->record);
  }

  public function next()
  {
    if (isset($this->source->record[$this->counter])) {
      $record = $this->source->record[$this->counter++];
    } elseif ($this->source->getName() == 'record' && $this->counter == 0) {
      $record = $this->source;
      $this->counter++;
    } else {
      return false;
    }

    if ($record) {
      return $this->parsing($record);
    } else {
      return false;
    }
  }

  public function get($index = null)
  {
    if (!is_null($index)) {
      $this->counter = --$index;
    }
    return $this->next();
  }

  public function parsing($data)
  {

    $record = new \Marc\Record;

    // save leader
    $record->setLeader((string)$data->leader);

    // Parsing control Field
    foreach ($data->controlfield as $controlfield) {
      // get Controlfield Attributes
      $cfAttr = $controlfield->attributes();
      // store control field data
      $record->addField(new \Marc\ControlField((string)$cfAttr['tag'], (string)$controlfield));
    }

    // Parsing datafield
    foreach ($data->datafield as $datafield) {
      // get data field attributes
      $dfAttr = $datafield->attributes();
      // store subfield data
      $subfieldData = array();
      foreach ($datafield->subfield as $subfield) {
        // get subfield attributes
        $sfAttr = $subfield->attributes();
        $subfieldData[] = new \Marc\SubField((string)$sfAttr['code'], (string)$subfield);
      }

      // save to datafield
      $record->addField(new \Marc\DataField((string)$dfAttr['tag'], $subfieldData, (string)$dfAttr['ind1'], (string)$dfAttr['ind2']));
    }

    return $record;
  }

  public function debug()
  {
    echo '<pre>'; print_r($this->source); echo '</pre>';
  }
}
