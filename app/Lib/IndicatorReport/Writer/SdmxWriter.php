<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should
have received a copy of the GNU General Public License along with this program.  If not, see
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

//date_default_timezone_set('Asia/Singapore');

class SdmxWriter extends \XMLWriter{
    const OUTPUT_FILE = 1;
    const OUTPUT_ECHO = 2;
    const FILE_EXT = '.xml';

    public $head;
    public $body;
    private $xmlLang = 'en';
    private $filename;
    private $tmpFilename;
    private $timeFormat = 'Y-m-d H:i:s';                    # Time format.
    private $logType = array(                               # Log Type.
                            LOG_ALERT => "Alert",
                            LOG_CRIT => "critical",
                            LOG_ERR => "Error",
                            LOG_WARNING => "Warning",
                            LOG_NOTICE => "Notice",
                            LOG_INFO => "Info",
                            LOG_DEBUG => "Debug",
                        );

    function __construct() {
        $this->tmpFilename = tempnam(sys_get_temp_dir(), 'sdmx');
        $this->log("Init SDMX writer...", LOG_INFO);
//        print date($this->timeFormat)." Info: Init SDMX writer...\n";
//        $this->writer = new DOMDocument('1.0');
//        $this->writer = new $this;

        $this->openUri($this->tmpFilename);
//        $this->openMemory();
        $this->setIndent(true);
        $this->startDocument("1.0", 'utf-8');

        # Init
        $this->startElement("message:StructureSpecificTimeSeriesData");
        $this->writeAttribute("xmlns:generic", "http://www.sdmx.org/resources/sdmxml/schemas/v2_1/data/generic");
        $this->writeAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $this->writeAttribute("xmlns:structure", "http://www.sdmx.org/resources/sdmxml/schemas/v2_1/structure");
        $this->writeAttribute("xmlns:structurespecific", "http://www.sdmx.org/resources/sdmxml/schemas/v2_1/data/structurespecific");
        $this->writeAttribute("xmlns:common", "http://www.sdmx.org/resources/sdmxml/schemas/v2_1/common");
        $this->writeAttribute("xmlns:query", "http://www.sdmx.org/resources/sdmxml/schemas/v2_1/query");
        $this->writeAttribute("xmlns:registry", "http://www.sdmx.org/resources/sdmxml/schemas/v2_1/registry");
        $this->writeAttribute("xmlns:message", "http://www.sdmx.org/resources/sdmxml/schemas/v2_1/message");

    }

    public function generate($resultSet){
        $this->log("Creating SDMX file.", LOG_INFO);
        $this->createHeader();
        $this->startDataSet();
        $this->setFilenameFromIndicator(reset($resultSet));
        foreach($resultSet as $series){
            $attributes = array_merge($series->getAttributes(), $this->arrayifySubgroup($series->getSubgroups()));
            $this->startSeries($series->getIndicator(), $series->getUnit(), $series->getArea(), $series->getSource(), $attributes);
            foreach($series->getObservations() as $observation){
                $this->createObservation($observation->getValue(), $observation->getTimeperiod(), $observation->getDenominator(), $observation->getFootnote(), $observation->getAttributes());
            }
            $this->endSeries();
        }
        $this->output();
    }

    public function createHeader() {
        $this->log("Create <message:Header> tag...", LOG_INFO);
        $this->startElement("message:Header");
        $this->createId();
        $this->createTest();
        $this->setTime();
        $this->createSender();
        $this->createReceiver();
        $this->createStructure();
        $this->endElement();

    }

    public function startDataSet() {
        $this->log("Create <message:DataSet> tag...", LOG_INFO);
        $this->startElement("message:DataSet");
        $this->writeAttribute("structurespecific:dataScope", "DataStructure");
        $this->writeAttribute("structurespecific:structureRef", "DSD_DevInfo");
    }

    public function endDataSet() {

        $this->endElement();
    }

    public function createId($id="Header_ID"){
        $this->log("Create <message:ID> tag...", LOG_INFO);
        $this->startElement("message:ID");
        $this->text($id);
        $this->endElement();
    }

    public function createTest($isSet=true){
        $this->log("Create <message:Test> tag...", LOG_INFO);
        $this->startElement("message:Test");
        $this->text(($isSet)? "true":"false");
        $this->endElement();
    }

    public function createSender($id="Sender_Id", $name="Sender_Name", $contact=array()) {
        $this->log("Create <message:Sender> tag...", LOG_INFO);;
        $this->startElement("message:Sender"); # start element
        $this->writeAttribute("id", $id);

        $this->startElement("common:Name"); # start element
        $this->writeAttribute("xml:lang", $this->xmlLang);
        $this->text($name);
        $this->endElement(); # end element

        $this->createContact("Sender_Name", "Sender_Department", "Sender_Role", "Sender_Telephone", "Sender_Email", "Sender_Fax");

        $this->endElement(); # end element

    }

    public function createReceiver($id="Receiver_Id", $name="Receiver_Name", $contact=array()) {
        $this->log("Create <message:Receiver> tag...", LOG_INFO);
        $this->startElement("message:Receiver"); # start element
        $this->writeAttribute("id", $id);

        $this->startElement("common:Name"); # start element
        $this->writeAttribute("xml:lang", $this->xmlLang);
        $this->text($name);
        $this->endElement(); # end element

        $this->createContact("Receiver_Name", "Receiver_Department", "Receiver_Role", "Receiver_Telephone", "Receiver_Email", "Receiver_Fax");

        $this->endElement(); # end element

    }

    public function createContact($name="", $department="", $role="", $telephone="", $email="", $fax="") {
        $this->startElement("message:Contact"); #start element

        $this->startElement("common:Name"); #start element
        $this->writeAttribute("xml:lang", $this->xmlLang);
        $this->text($name);
        $this->endElement();

        $this->startElement("message:Department"); #start element
        $this->writeAttribute("xml:lang", $this->xmlLang);
        $this->text($department);
        $this->endElement();

        $this->startElement("message:Role"); #start element
        $this->writeAttribute("xml:lang", $this->xmlLang);
        $this->text($role);
        $this->endElement();

        $this->startElement("message:Telephone"); #start element
        $this->text($telephone);
        $this->endElement();

        $this->startElement("message:Email"); #start element
        $this->text($email);
        $this->endElement();

        $this->startElement("message:Fax"); #start element
        $this->text($fax);
        $this->endElement();


        $this->endElement(); #end element
    }

    public function createStructure() {
        $this->log("Create <message:Structure> tag...", LOG_INFO);
        $this->startElement("message:Structure");
        $this->writeAttribute("dimensionAtObservation", "TIME_PERIOD");
        $this->writeAttribute("structureID", "DSD_DevInfo");
        $this->writeAttribute("namespace", "http://www.devinfo.info/");

        $this->startElement("common:Structure");
        $this->writeAttribute("xsi:type", "common:DataStructureReferenceType");

        $this->startElement("Ref");
        $this->writeAttribute("xsi:type", "common:DataStructureRefType");
        $this->writeAttribute("agencyID", "MA_1");
        $this->writeAttribute("id", "DSD_DevInfo");
        $this->writeAttribute("version", "7.0");
        $this->endElement();

        $this->endElement();

        $this->endElement();

    }

    public function setTime() {
        $this->log("Create <message:Prepared> tag...", LOG_INFO);
        $this->startElement("message:Prepared");
        $this->text(date(DATE_ATOM,time()));
        $this->endElement();
    }

    public function startSeries($indicator, $unit, $area, $source, $attributes = array()){
        $this->log("Create <Series> tag...", LOG_INFO);

        $this->startElement('Series');
        $this->writeAttribute("INDICATOR", $indicator);
        $this->writeAttribute("UNIT", $unit);
        $this->writeAttribute("AREA", $area);
        foreach($attributes as $key => $value){
            $key = strtoupper($key);
            $key = trim($key);
            $key = str_replace(" ", "_", $key);
            $this->writeAttribute($key, trim($value));
        }
        $this->writeAttribute("SOURCE", $source);
    }

    public function endSeries(){
        $this->endElement();
        $this->flush();
    }

    public function createObservation($value , $timeperiod, $denominator, $footnote, $attributes = array()) {
//        print "Create <Obs> tag...".PHP_EOL;
        $this->startElement('Obs');
        $this->writeAttribute('OBS_VALUE', $value);
        $this->writeAttribute('TIME_PERIOD', $timeperiod);
        $this->writeAttribute('DENOMINATOR', $denominator);
        $this->writeAttribute('FOOTNOTES', $footnote);

        foreach($attributes as $key => $value){
            $key = strtoupper($key);
            $key = trim($key);
            $key = str_replace(" ", "_", $key);
            $this->writeAttribute($key, $value);
        }

        $this->endElement();
    }

    public function assemble ($type=self::OUTPUT_FILE, $filename=""){
        $this->log("Assembling SDMX...", LOG_INFO);
        $this->endDocument();
        $this->flush();
        $this->log("Finalising SDMX Assembly...", LOG_INFO);
        $this->log("Written SDMX file to disk.", LOG_INFO);
        $this->log("Location of SDMX file: {$this->filename}", LOG_INFO);
//        return $this->outputMemory(true);
    }

    private function log($message, $type){
//        print date($this->timeFormat). " {$this->logType[$type]}: {$message}".PHP_EOL;
    }

    private function arrayifySubgroup($subgroups){
        $arrayifiedSubgroup = array();
        foreach($subgroups as $subgroup){
            $arrayifiedSubgroup[$subgroup->getSubType()] = $subgroup->getName();
        }

        return $arrayifiedSubgroup;

    }

    public function setLang($lang="en"){
        $this->xmlLang = $lang;
    }

//    public function __destruct() {
    public function output() {
        $this->assemble();
        //content type
        header('Content-type: text/xml charset=utf-8');
//        header('Content-type: text/plain');
        //open/save dialog box
//        header('Content-Disposition: attachment; filename="sdmx_report_'.date('d_M_Y').'.xml"');
        header('Content-Disposition: attachment; filename="'.$this->filename.'"');
        header('Content-Length: ' . filesize($this->tmpFilename));
        ob_clean();
        flush();
        //read from server and write to buffer
        readfile($this->tmpFilename);
        unlink($this->tmpFilename);
        exit;
    }

    public function setFilenameFromIndicator($firstSeries){
        $filename = $firstSeries->getIndicator();
        $this->filename = str_ireplace(' ', '_', $filename).SdmxWriter::FILE_EXT;

    }

}