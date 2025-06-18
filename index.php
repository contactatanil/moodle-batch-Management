/**
 * Main batch management dashboard
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('local/batchmanager:view', context_system::instance());

$PAGE->set_url('/local/batchmanager/index.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('batchmanager', 'local_batchmanager'));
$PAGE->set_heading(get_string('batchmanager', 'local_batchmanager'));

// Handle delete action
$delete = optional_param('delete', 0, PARAM_INT);
if ($delete && confirm_sesskey()) {
    $DB->delete_records('local_batchmanager_students', array('batchid' => $delete));
    $DB->delete_records('local_batchmanager_batches', array('id' => $delete));
    redirect($PAGE->url, get_string('batchdeleted', 'local_batchmanager'));
}

echo $OUTPUT->header();

echo html_writer::tag('h2', get_string('managebatches', 'local_batchmanager'));

// Action buttons
echo html_writer::start_div('mb-3');
if (has_capability('local/batchmanager:manage', context_system::instance())) {
    echo html_writer::link(
        new moodle_url('/local/batchmanager/create_batch.php'),
        get_string('createbatch', 'local_batchmanager'),
        array('class' => 'btn btn-primary mr-2')
    );
    echo html_writer::link(
        new moodle_url('/local/batchmanager/quick_enroll.php'),
        get_string('quickenroll', 'local_batchmanager'),
        array('class' => 'btn btn-success mr-2')
    );
}
echo html_writer::end_div();

// Display batches
$batches = local_batchmanager_get_batches();

if (empty($batches)) {
    echo html_writer::tag('p', get_string('nobatches', 'local_batchmanager'), array('class' => 'alert alert-info'));
} else {
    foreach ($batches as $batch) {
        echo html_writer::start_div('card mb-3');
        echo html_writer::start_div('card-body');
        
        echo html_writer::start_div('d-flex justify-content-between align-items-center mb-2');
        echo html_writer::tag('h5', format_string($batch->name), array('class' => 'card-title mb-0'));
        echo html_writer::tag('span', get_string('studentcount', 'local_batchmanager', $batch->studentcount), 
                             array('class' => 'badge badge-primary'));
        echo html_writer::end_div();
        
        if ($batch->description) {
            echo html_writer::tag('p', format_text($batch->description), array('class' => 'card-text'));
        }
        
        echo html_writer::tag('small', 'Created: ' . userdate($batch->timecreated), 
                             array('class' => 'text-muted'));
        
        echo html_writer::start_div('mt-3');
        
        // View students button
        echo html_writer::link(
            new moodle_url('/local/batchmanager/view_students.php', array('id' => $batch->id)),
            get_string('viewstudents', 'local_batchmanager'),
            array('class' => 'btn btn-sm btn-outline-primary mr-2')
        );
        
        // Import students button
        if (has_capability('local/batchmanager:manage', context_system::instance())) {
            echo html_writer::link(
                new moodle_url('/local/batchmanager/import_students.php', array('batchid' => $batch->id)),
                get_string('importstudents', 'local_batchmanager'),
                array('class' => 'btn btn-sm btn-info mr-2')
            );
        }
        
        // Quick enroll button (multiple courses)
        echo html_writer::link(
            new moodle_url('/local/batchmanager/quick_enroll.php', array('batchid' => $batch->id)),
            get_string('enrollmultiple', 'local_batchmanager'),
            array('class' => 'btn btn-sm btn-success mr-2')
        );
        
        // Delete button
        if (has_capability('local/batchmanager:manage', context_system::instance())) {
            echo html_writer::link(
                new moodle_url('/local/batchmanager/index.php', 
                              array('delete' => $batch->id, 'sesskey' => sesskey())),
                get_string('deletebatch', 'local_batchmanager'),
                array('class' => 'btn btn-sm btn-danger',
                      'onclick' => 'return confirm("' . get_string('confirmdelete', 'local_batchmanager') . '")')
            );
        }
        
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
}

echo $OUTPUT->footer();