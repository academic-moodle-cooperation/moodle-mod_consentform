<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Table export class
 *
 * @package       local
 * @subpackage    printpreview
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Andreas Weninger
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_consentform;

defined('MOODLE_INTERNAL') || die();

// Global variable $CFG is always set, but with this little wrapper PHPStorm won't give wrong error messages!
if (isset($CFG)) {
    require_once($CFG->libdir . '/pdflib.php');
}

class mtablepdf extends \pdf {

    const OUTPUT_FORMAT_PDF = 0;
    const OUTPUT_FORMAT_XLSX = 1;
    const OUTPUT_FORMAT_XLS = 2;
    const OUTPUT_FORMAT_CSV_COMMA = 4;

    private $outputformat = self::OUTPUT_FORMAT_CSV_COMMA;

    private $rowsperpage = 0;
    private $showheaderfooter = false;
    private $columnwidths = array();
    private $titles = null;
    private $columnformat;
    private $data = array();

    public function __construct($columnwidths) {
        parent::__construct();

        // Set default configuration.
        $this->SetCreator('MedUni Wien');
        $this->SetMargins(10, 20, 10, true);
        $this->setHeaderMargin(7);
        $this->SetFont('freesans', '');
        $this->columnwidths = $columnwidths;

    }

    /**
     * Sets the titles for the columns in the file
     * @param String $titles
     */
    public function set_titles($titles) {
        if (count($titles) != count($this->columnwidths)) {
            echo "Error: Title count doesnt match column count";
            exit();
        }

        $this->titles = $titles;
    }

    public function set_outputformat($format) {
        $this->outputformat = $format;
    }

    /**
     * Defines how many rows are printed on each page
     * @param int $i > 0
     * @return true if ok
     */
    public function set_rowsperpage($rowsperpage) {
        if (is_number($rowsperpage) && $rowsperpage > 0) {
            $this->rowsperpage = $rowsperpage;
            return true;
        }

        return false;
    }

    /**
     * Adds a row to the pdf
     * @param array $row
     * @return boolean
     */
    public function add_row($row) {
        if (count($row) != count($this->columnwidths)) {

            var_dump($row);
            echo "Error: number of columns from row ("
                .count($row) . ") doenst match the number defined ("
                .count($this->columnwidths) . ")";
            return false;
        }

        $fastmode = false;
        foreach ($row as $r) {
            if (!is_null($r) && !is_array($r)) {
                $fastmode = true;
            }
        }

        if ($fastmode) {
            // Fast mode.
            $tmp = array();

            foreach ($row as $idx => $value) {
                if (is_array($value)) {
                    echo "Error: if you want to add a row using the fast mode, you cannot pass me an array";
                    exit();
                }

                $tmp[] = array("rowspan" => 0, "data" => $value);
            }

            $row = $tmp;
        } else {
            foreach ($row as $idx => $value) {
                if (!is_array($value)) {
                    $row[$idx] = array("rowspan" => 0, "data" => $value);
                } else if (!isset($value["data"])) {
                    echo "Error: you need to set a value for [\"data\"]";
                    exit();
                } else {
                    if (!isset($value["rowspan"])) {
                        $row[$idx]["rowspan"] = 0;
                    }
                }
            }
        }

        $this->data[] = $row;

        return true;
    }

    /*
     * Generate the file
     * */

    public function generate($filename) {

        if ($filename == '') {
            $filename = userdate(time());
        }

        $filename = clean_filename($filename);

        switch ($this->outputformat) {
            case self::OUTPUT_FORMAT_XLS:
                $this->get_xls($filename);
                break;
            case self::OUTPUT_FORMAT_XLSX:
                $this->get_xlsx($filename);
                break;
            case self::OUTPUT_FORMAT_CSV_COMMA:
                $this->get_csv($filename, ';');
                break;
            default:
                $this->get_csv($filename, ';');
        }
    }


    /**
     * fills workbook (either XLS or ODS) with data
     *
     * @param MoodleExcelWorkbook $workbook workbook to put data into
     */
    public function fill_workbook(&$workbook) {
        global $DB;

        $time = time();
        $time = userdate($time);
        $worksheet = $workbook->add_worksheet($time);

        $headlineprop = array('size' => 12,
            'bold' => 1,
            'HAlign' => 'center',
            'bottom' => 1,
            'VAlign' => 'vcenter');
        $headlineformat = $workbook->add_format($headlineprop);
        $headlineformat->set_left(1);
        $headlineformat->set_align('center');
        $headlineformat->set_align('vcenter');
        $headlinefirst = $workbook->add_format($headlineprop);
        $headlinefirst->set_align('center');
        $headlinefirst->set_align('vcenter');
        unset($headlineprop['bottom']);
        $hdrleft = $workbook->add_format($headlineprop);
        $hdrleft->set_align('right');
        $hdrleft->set_align('vcenter');
        unset($headlineprop['bold']);
        $hdrright = $workbook->add_format($headlineprop);
        $hdrright->set_align('left');
        $hdrright->set_align('vcenter');

        $textprop = array('size' => 10,
            'align' => 'left');
        $text = $workbook->add_format($textprop);
        $text->set_left(1);
        $text->set_align('vcenter');
        $textfirst = $workbook->add_format($textprop);
        $textfirst->set_align('vcenter');

        $line = 0;

        // Write header.
        for ($i = 0; $i < count($this->header); $i += 2) {
            $worksheet->write_string($line, 0, $this->header[$i], $hdrleft);
            $worksheet->write_string($line, 1, $this->header[$i + 1], $hdrright);
            $line++;
        }
        $line++;

        // Table header.
        $i = 0;
        $first = true;
        foreach ($this->titles as $key => $header) {
            if ($first) {
                $worksheet->write_string($line, $i, $header, $headlinefirst);
                $first = false;
            } else {
                $worksheet->write_string($line, $i, $header, $headlineformat);
                $first = false;
            }
            $i++;
        }

        // Data.
        $prev = $this->data[0];
        foreach ($this->data as $row) {
            $first = true;
            $line++;
            $i = 0;
            foreach ($row as $idx => $cell) {
                if (is_null($cell['data'])) {
                    $cell['data'] = $prev[$idx]['data'];
                }

                if ($first) {
                    $worksheet->write_string($line, $i, $cell['data'], $textfirst);
                    $first = false;
                } else {
                    $worksheet->write_string($line, $i, $cell['data'], $text);
                }

                $prev[$idx] = $cell;
                $i++;
            }
        }
    }

    public function get_xls($filename) {
        global $CFG;

        require_once($CFG->libdir . "/excellib.class.php");

        $workbook = new MoodleExcelWorkbook("-", 'excel5');

        $this->fill_workbook($workbook);

        $workbook->send($filename . '.xls');
        $workbook->close();
    }

    public function get_csv($filename, $sep = "\t") {

        $lines = array();

        // Table header.print
        $lines[] = join($sep, $this->titles);

        $prev = $this->data[0];

        // Data.
        foreach ($this->data as $row) {
            $r = array();
            foreach ($row as $idx => $cell) {
                if (is_null($cell['data'])) {
                    $cell['data'] = $prev[$idx]['data'];
                }

                $r[] = $cell['data'];
                $prev[$idx] = $cell;
            }

            $lines[] = join($sep, $r);
        }

        $filecontent = implode("\n", $lines);

        if ($filename != '') {
            if (substr($filename, strlen($filename) - 4) != ".csv") {
                $filename .= '.csv';
            }

            $filename = clean_filename($filename);
        }

        header('Content-Type: text/plain');
        header('Content-Length: ' . strlen($filecontent));
        header('Content-Disposition: attachment; filename="' . $filename . '"; filename*="' .rawurlencode($filename));
        header('Content-Transfer-Encoding: binary');

		echo($filecontent);

        die();
    }
}