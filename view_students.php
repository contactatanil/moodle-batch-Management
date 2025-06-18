/**
 * View students in batch
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('local/batchmanager:view', context_system::instance());

$batchid = required_param('id', PARAM_INT);
$remove = optional_param('remove', 0, PARAM_INT);

$batch = $DB->get_record('local_batchmanager_batches', array('id' => $batchid), '*', MUST_EXIST);

$PAGE->set_url('/local/batchmanager/view_students.php', array('id' => $batchid));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('viewstudents', 'local_batchmanager'));
$PAGE->set_heading(get_string('viewstudents', 'local_batchmanager') . ': ' . format_string($batch->name));

// Handle remove student
if ($remove && confirm_sesskey() && has_capability('local/batchmanager:manage', context_system::instance())) {
    $DB->delete_records('local_batchmanager_students', array('batchid' => $batchid, 'userid' => $remove));
    redirect($PAGE->url, 'Student removed from batch');
}

echo $OUTPUT->header();

echo html_writer::tag('h2', format_string($batch->name));
if ($batch->description) {
    echo html_writer::tag('p', format_text($batch->description), array('class' => 'lead'));
}

// Action buttons
echo html_writer::start_div('mb-3');
echo html_writer::link(
    new moodle_url('/local/batchmanager/index.php'),
    '← Back to Batches',
    array('class' => 'btn btn-secondary mr-2')
);

if (has_capability('local/batchmanager:manage', context_system::instance())) {
    echo html_writer::link(
        new moodle_url('/local/batchmanager/import_students.php', array('batchid' => $batchid)),
        get_string('importstudents', 'local_batchmanager'),
        array('class' => 'btn btn-info mr-2')
    );
}

echo html_writer::link(
    new moodle_url('/local/batchmanager/quick_enroll.php', array('batchid' => $batchid)),
    get_string('enrollmultiple', 'local_batchmanager'),
    array('class' => 'btn btn-success')
);
echo html_writer::end_div();

// Display students
$students = local_batchmanager_get_batch_students($batchid);

if (empty($students)) {
    echo html_writer::tag('p', 'No students in this batch yet.', array('class' => 'alert alert-info'));
} else {
    echo html_writer::tag('h3', 'Students (' . count($students) . ')');
    
    echo html_writer::start_tag('table', array('class' => 'table table-striped'));
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', 'Name');
    echo html_writer::tag('th', 'Email');
    echo html_writer::tag('th', 'Date Added');
    if (has_capability('local/batchmanager:manage', context_system::instance())) {
        echo html_writer::tag('th', 'Actions');
    }
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    
    echo html_writer::start_tag('tbody');
    foreach ($students as $student) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', fullname($student));
        echo html_writer::tag('td', $student->email);
        echo html_writer::tag('td', userdate($student->dateadded));
        
        if (has_capability('local/batchmanager:manage', context_system::instance())) {
            echo html_writer::start_tag('td');
            echo html_writer::link(
                new moodle_url('/local/batchmanager/view_students.php', 
                              array('id' => $batchid, 'remove' => $student->id, 'sesskey' => sesskey())),
                'Remove',
                array('class' => 'btn btn-sm btn-danger',
                      'onclick' => 'return confirm("Remove this student from the batch?")')
            );
            echo html_writer::end_tag('td');
        }
        
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
}

echo $OUTPUT->footer();

?>