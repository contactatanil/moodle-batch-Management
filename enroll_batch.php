/**
 * Enroll batch in course page
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('classes/form/enroll_batch_form.php');

require_login();
require_capability('local/batchmanager:manage', context_system::instance());

$courseid = optional_param('courseid', 0, PARAM_INT);
$batchid = optional_param('batchid', 0, PARAM_INT);

if ($courseid) {
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $PAGE->set_context(context_course::instance($courseid));
    $PAGE->set_heading($course->fullname);
} else {
    $PAGE->set_context(context_system::instance());
    $PAGE->set_heading(get_string('enrollbatch', 'local_batchmanager'));
}

$PAGE->set_url('/local/batchmanager/enroll_batch.php', array('courseid' => $courseid, 'batchid' => $batchid));
$PAGE->set_title(get_string('enrollbatch', 'local_batchmanager'));

$form = new enroll_batch_form(null, array('courseid' => $courseid, 'batchid' => $batchid));

// Set defaults if provided
$defaults = array();
if ($batchid) $defaults['batchid'] = $batchid;
if ($courseid) $defaults['courseid'] = $courseid;
if (!empty($defaults)) {
    $form->set_data($defaults);
}

if ($form->is_cancelled()) {
    $returnurl = $courseid ? new moodle_url('/course/view.php', array('id' => $courseid)) 
                          : new moodle_url('/local/batchmanager/index.php');
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    if (isset($data->courseids)) {
        // Multiple courses
        $result = local_batchmanager_enroll_batch_multiple($data->batchid, $data->courseids, $data->roleid);
        $message = get_string('allenrolled', 'local_batchmanager') . ' (' . $result['total'] . ' enrollments)';
        $returnurl = new moodle_url('/local/batchmanager/index.php');
    } else {
        // Single course
        $enrolled = local_batchmanager_enroll_batch($data->batchid, $data->courseid, $data->roleid);
        $message = get_string('studentsenrolled', 'local_batchmanager') . ' (' . $enrolled . ' students)';
        $returnurl = new moodle_url('/course/view.php', array('id' => $data->courseid));
    }
    
    redirect($returnurl, $message);
}

echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('enrollbatch', 'local_batchmanager'));
$form->display();
echo $OUTPUT->footer();