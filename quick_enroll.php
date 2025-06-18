/**
 * Quick enroll batch in multiple courses
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('classes/form/enroll_batch_form.php');

require_login();
require_capability('local/batchmanager:manage', context_system::instance());

$batchid = optional_param('batchid', 0, PARAM_INT);

$PAGE->set_url('/local/batchmanager/quick_enroll.php', array('batchid' => $batchid));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('quickenroll', 'local_batchmanager'));
$PAGE->set_heading(get_string('quickenroll', 'local_batchmanager'));

$form = new enroll_batch_form(null, array('batchid' => $batchid));

if ($batchid) {
    $form->set_data(array('batchid' => $batchid));
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/batchmanager/index.php'));
} else if ($data = $form->get_data()) {
    if (isset($data->courseids)) {
        // Multiple courses
        $result = local_batchmanager_enroll_batch_multiple($data->batchid, $data->courseids, $data->roleid);
        $message = get_string('allenrolled', 'local_batchmanager') . ' (' . $result['total'] . ' enrollments)';
    } else {
        // Single course
        $enrolled = local_batchmanager_enroll_batch($data->batchid, $data->courseid, $data->roleid);
        $message = get_string('studentsenrolled', 'local_batchmanager') . ' (' . $enrolled . ' students)';
    }
    
    redirect(new moodle_url('/local/batchmanager/index.php'), $message);
}

echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('enrollmultiple', 'local_batchmanager'));
$form->display();
echo $OUTPUT->footer();

// =============================================================================
// FILE: import_students.php
// =============================================================================
/**
 * Import students to existing batch
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('classes/form/import_students_form.php');

require_login();
require_capability('local/batchmanager:manage', context_system::instance());

$batchid = required_param('batchid', PARAM_INT);

$batch = $DB->get_record('local_batchmanager_batches', array('id' => $batchid), '*', MUST_EXIST);

$PAGE->set_url('/local/batchmanager/import_students.php', array('batchid' => $batchid));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('importstudents', 'local_batchmanager'));
$PAGE->set_heading(get_string('importstudents', 'local_batchmanager') . ': ' . format_string($batch->name));

$form = new import_students_form(null, array('batchid' => $batchid));

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/batchmanager/index.php'));
} else if ($data = $form->get_data()) {
    $importcount = 0;
    $errors = array();
    
    if ($data->importmethod == 'csv' && !empty($data->csvfile)) {
        // Handle CSV import
        $csvdata = $form->get_file_content('csvfile');
        $result = local_batchmanager_import_csv($csvdata, $batchid);
        $importcount = $result['imported'];
        $errors = $result['errors'];
        $message = get_string('csvimported', 'local_batchmanager', $importcount);
    } else if ($data->importmethod == 'groups' && !empty($data->groups)) {
        // Handle group import
        $importcount = local_batchmanager_import_groups($data->groups, $batchid);
        $message = get_string('groupsimported', 'local_batchmanager', $importcount);
    }
    
    redirect(new moodle_url('/local/batchmanager/view_students.php', array('id' => $batchid)), $message);
}

echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('importstudents', 'local_batchmanager'));
echo html_writer::tag('h3', format_string($batch->name), array('class' => 'text-muted'));
$form->display();
echo $OUTPUT->footer();