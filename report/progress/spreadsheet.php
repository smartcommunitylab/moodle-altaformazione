<?php

require('../../config.php');
require_once($CFG->dirroot.'/enrol/locallib.php');
require_once($CFG->libdir . '/completionlib.php');
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Get course
$id = required_param('course',PARAM_INT);
$course = $DB->get_record('course',array('id'=>$id));
if (!$course) {
    print_error('invalidcourseid');
}
$context = context_course::instance($course->id);
// Sort (default lastname, optionally firstname)
$sort = optional_param('sort','',PARAM_ALPHA);
$firstnamesort = $sort == 'firstname';
// Paging
$start   = optional_param('start', 0, PARAM_INT);
$sifirst = optional_param('sifirst', 'all', PARAM_NOTAGS);
$silast  = optional_param('silast', 'all', PARAM_NOTAGS);
$start   = optional_param('start', 0, PARAM_INT);
require_login($course);

// Check basic permission
require_capability('report/progress:view',$context);

// Get group mode
$group = groups_get_course_group($course,true); // Supposed to verify group
if ($group===0 && $course->groupmode==SEPARATEGROUPS) {
    require_capability('moodle/site:accessallgroups',$context);
}
// Get data on activities and progress of all users, and give error if we've
// nothing to display (no users or no activities)
$completion = new completion_info($course);
$activities = $completion->get_activities();

if ($sifirst !== 'all') {
    set_user_preference('ifirst', $sifirst);
}
if ($silast !== 'all') {
    set_user_preference('ilast', $silast);
}
if (!empty($USER->preference['ifirst'])) {
    $sifirst = $USER->preference['ifirst'];
} else {
    $sifirst = 'all';
}
if (!empty($USER->preference['ilast'])) {
    $silast = $USER->preference['ilast'];
} else {
    $silast = 'all';
}
// Generate where clause
$where = array();
$where_params = array();

if ($sifirst !== 'all') {
    $where[] = $DB->sql_like('u.firstname', ':sifirst', false, false);
    $where_params['sifirst'] = $sifirst.'%';
}

if ($silast !== 'all') {
    $where[] = $DB->sql_like('u.lastname', ':silast', false, false);
    $where_params['silast'] = $silast.'%';
}
// Get user match count
$total = $completion->get_num_tracked_users(implode(' AND ', $where), $where_params, $group);
// Total user count
$grandtotal = $completion->get_num_tracked_users('', array(), $group);
// Get user data
$progress = array();

if ($total) {
    $progress = $completion->get_progress_all(
        implode(' AND ', $where),
        $where_params,
        $group,
        $firstnamesort ? 'u.firstname ASC, u.lastname ASC' : 'u.lastname ASC, u.firstname ASC',
        0,
        0,
        $context
    );
}
// Spreadsheet configurations
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
// colors for activities status
$completed_activity = "90ee90";
$not_started_activity = "d3d3d3";
$started_activity = "ffffe0";
// colors for course status
$completed_course = "008000";
$under70_course = "ffffe0";
$over70_course = "90ee90";
$not_started_course = "d3d3d3";
$style_header = [
      'font' => [
          'bold' => true,
          'name' => "Cambria",
          'size' => 11
      ],
      'alignment' => [
          'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
      ],
      'borders' => [
          'top' => [
              'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
          ],
          'bottom' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        ],
      ],
      'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
            'startColor' => [
                'rgb' => 'a9a9a9',
            ],
            'endColor' => [
                'rgb' => 'a9a9a9',
            ],
        ]
];
$style_even = [
      'font' => [
          'bold' => false,
          'name' => "Cambria",
          'size' => 10
      ],
      'borders' => [
            'left' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ],
            'right' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        ],
      ],
      'fill' => [
          'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
          'startColor' => [
              'rgb' => '7fffd4',
          ],
          'endColor' => [
              'rgb' => '7fffd4',
          ],
      ],
];
$style_odd = ['font' => [
                'bold' => false,
                'name' => "Cambria",
                'size' => 10
            ],
            'borders' => [
                'left' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
                'right' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
                'startColor' => [
                    'rgb' => 'ffffff',
                ],
                'endColor' => [
                    'rgb' => 'ffffff',
                ],
            ]
];
function get_style($fillcolor, $isactivity=false){
    $border_side = ['top' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                    'bottom' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ]
                ];
    $style = [
            'font' => [
                'bold' => false,
                'name' => "Cambria",
                'size' => 10
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'left' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
                'right' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
                'startColor' => [
                    'rgb' => $fillcolor,
                ],
                'endColor' => [
                    'rgb' => $fillcolor,
                ],
            ],
    ];
    if($isactivity)
        $style["borders"] = $style["borders"] + $border_side;
    return $style;
}
// Generate Headers
$sheet->setCellValue('A2', 'Nome');
$sheet->setCellValue('B2', 'Ruolo');
$sheet->setCellValue('C2', 'Percorso');
$sheet->setCellValue('D2', 'Durata Percorso');
$sheet->setCellValue('E2', 'Completamento minimo');
$sheet->setCellValue('F2', 'Ore fruite');
$sheet->setCellValue('G2', '% completamento');
$sheet->getStyle("A2:G2")->applyFromArray($style_header);

// Generate rows 
$sheet->getColumnDimension('A')->setWidth(25);
$sheet->getColumnDimension('B')->setWidth(20);
$sheet->getColumnDimension('C')->setWidth(40);
$sheet->getColumnDimension('D')->setWidth(20);
$sheet->getColumnDimension('E')->setWidth(25);
$sheet->getColumnDimension('F')->setWidth(15);
$sheet->getColumnDimension('G')->setWidth(20);

//Activities
$count = 8;
$name_col = "H";   
$activity_weights = [];
$total_weight_sec = 0;
global $DB;
foreach($activities as $activity) {  
    $hours = 0;
    $mins = 0;
    if($activity->modname == "questionnaire") 
        continue;
    $name_col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($count);   
    $displayname = format_string($activity->name, true, array('context' => $activity->context));
    $sheet->getColumnDimension("{$name_col}")->setWidth(35);
    $sheet->setCellValue("{$name_col}2", $displayname);
    $weights = $DB->get_records_sql("
                SELECT intvalue
                FROM {customfield_data} d
                INNER JOIN {customfield_field} f ON d.fieldid=f.id
                WHERE
                    f.shortname in ('duration_hours', 'duration_mins') AND d.instanceid=?", array($activity->id));
    $i=0;
    foreach ($weights as $key => $value) {
        $total_weight_sec += $key;
        if($i==0)
            $hours = $key;
        else
            $mins = $key;
        $i++;
    }
    $activity_weights[$activity->id] = intval($hours) + intval($mins);
    $count++;
}
$sheet->getStyle("H2:{$name_col}2")->applyFromArray($style_header);

$sheet->setCellValue('H1', 'LEZIONI');
$sheet->MergeCells("H1:{$name_col}1");
$sheet->getStyle("H1:{$name_col}1")->applyFromArray($style_header);

// total of hours
$tot_result = gmdate('H:i', $total_weight_sec);
// only 70 % of hours
$total70 = 70 * $total_weight_sec / 100;
$tot_70 = gmdate('H:i', $total70);

// Row for each user
$count_row = 3;
foreach($progress as $user) {
    if($count_row % 2 == 0)
        $sheet->getStyle("A$count_row:E$count_row")->applyFromArray($style_even);
    else
        $sheet->getStyle("A$count_row:E$count_row")->applyFromArray($style_odd);
    // User name
    $sheet->setCellValue("A{$count_row}", fullname($user, has_capability('moodle/site:viewfullnames', $context)));  
    $sheet->setCellValue("C{$count_row}", $course->fullname);  
    // group name
    $ruolo = "";
    $sql = "SELECT name
            FROM {groups} g
            JOIN {groups_members} gm on gm.groupid = g.id
            WHERE gm.userid = ? and g.courseid=?";
    $param = [$user->id, $course->id];
    if($group != 0){
        $param[] = $group;
        $sql .= " and g.id=?";
    }
    $groups = $DB->get_records_sql($sql, $param);
    if(sizeof($groups) > 0){
        $roles = [];
        foreach($groups as $gr){
            $roles[] = $gr->name;
        } 
        $ruolo = implode(", ", $roles);      
    } else{
       // role
        $sql = "SELECT r.name, r.shortname
            FROM {role_assignments} ra
            JOIN {role} r on r.id = ra.roleid
            JOIN {context} c on ra.contextid = c.id
            WHERE ra.userid = ? and c.instanceid = ?";
        $userenrolments = $DB->get_records_sql($sql, [$user->id, $course->id]);
        $roles = [];
        foreach($userenrolments as $ue){
            $roles[] = $ue->name != '' ? $ue->name : $ue->shortname;
        } 
        $ruolo = implode(", ", $roles);
    }
    $sheet->setCellValue("B{$count_row}", $ruolo); 
    // Progress for each activity
    $count_col = 8;
    $count_complete = 0;
    $count_activities = 0;
    $total_done = 0;
    foreach($activities as $activity) {  
        if($activity->modname == "questionnaire") 
            continue;
        $count_activities++;
        // Get progress information and state
        if (array_key_exists($activity->id, $user->progress)) {
            $thisprogress = $user->progress[$activity->id];
            $state = $thisprogress->completionstate;  // TODO user viewed ?
        } else {
            $state = COMPLETION_UNKNOWN;
        }  
        // Work out how it corresponds to an icon
        $name_col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($count_col); 
        switch($state) {
            case COMPLETION_INCOMPLETE :
                $completiontype = 'Iniziato';
                $sheet->getStyle("{$name_col}{$count_row}")->applyFromArray(get_style($started_activity, $isactivity=true));
                break;
            case COMPLETION_COMPLETE :
                $count_complete++;
                $total_done += $activity_weights[$activity->id];
                $completiontype = 'Completato e superato';
                $sheet->getStyle("{$name_col}{$count_row}")->applyFromArray(get_style($completed_activity, $isactivity=true));
                break;
            case COMPLETION_COMPLETE_PASS :
                $completiontype = 'pass';
                break;
            case COMPLETION_COMPLETE_FAIL :
                $completiontype = 'fail';
                break;
            case COMPLETION_UNKNOWN :
                $completiontype = 'Non iniziato';
                $sheet->getStyle("{$name_col}{$count_row}")->applyFromArray(get_style($not_started_activity, $isactivity=true));
                break;
        }        
        $describe = $completiontype; //get_string('completion-' . $completiontype, 'completion');
        $sheet->setCellValue("{$name_col}{$count_row}", $describe);
        $count_col++;
    }
    // calculate hours of completed activities and their percentage    
    $percent_done = round($total_done/$total_weight_sec * 100, 2) ;
    if($percent_done == 100)
        $sheet->getStyle("F$count_row:G$count_row")->applyFromArray(get_style($completed_course));
    else if($percent_done > 70)
        $sheet->getStyle("F$count_row:G$count_row")->applyFromArray(get_style($over70_course)); 
    else if($count_complete == 0)
        $sheet->getStyle("F$count_row:G$count_row")->applyFromArray(get_style($not_started_course));
    else
        $sheet->getStyle("F$count_row:G$count_row")->applyFromArray(get_style($under70_course));   
    $sheet->setCellValue("D{$count_row}", ltrim($tot_result, 0));
    $sheet->setCellValue("E{$count_row}", ltrim($tot_70, 0));  
    $perc = gmdate('H:i', $total_done);
    $h = explode(":", $perc)[0];
    if(intval($h) == 0)
        $perc = 0 . ":" . explode(":", $perc)[1];
    else
        $perc = ltrim($perc, 0);
    $sheet->setCellValue("F{$count_row}", $perc);
    $sheet->setCellValue("G{$count_row}", $percent_done."%");   
    $count_row++;
}  

// Redirect output to a client’s web browser (Excel5)
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment;filename="Statistiche_del_'.date("Y_m_d").'.xlsx"');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header ('Pragma: public'); // HTTP/1.0
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
