/**
 * Create new batch page
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('classes/form/create_batch_form.php');

require_login();
require_capability('local/batchmanager:manage', context_system::instance());

$PAGE->set_url('/local/batchmanager/create_batch.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('createbatch', 'local_batchmanager'));
$PAGE->set_heading(get_string('createbatch', 'local_batchmanager'));

$form = new create_batch_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/batchmanager/index.php'));
} else if ($data = $form->get_data()) {
    // Create batch first
    $batch = new stdClass();
    $batch->name = $data->name;
    $batch->description = $data->description;
    $batch->timecreated = time();
    $batch->timemodified = time();
    $batch->createdby = $USER->id;
    
    $batchid = $DB->insert_record('local_batchmanager_batches', $batch);
    
    // Handle different import methods
    $importcount = 0;
    $errors = array();
    
    if ($data->importmethod == 'manual' && !empty($data->students)) {
        foreach ($data->students as $userid) {
            $student = new stdClass();
            $student->batchid = $batchid;
            $student->userid = $userid;
            $student->timecreated = time();
            $DB->insert_record('local_batchmanager_students', $student);
            $importcount++;
        }
    } else if ($data->importmethod == 'csv' && !empty($data->csvfile)) {
        // Handle CSV import
        $csvdata = $form->get_file_content('csvfile');
        $result = local_batchmanager_import_csv($csvdata, $batchid);
        $importcount = $result['imported'];
        $errors = $result['errors'];
    } else if ($data->importmethod == 'groups' && !empty($data->groups)) {
        // Handle group import
        $importcount = local_batchmanager_import_groups($data->groups, $batchid);
    }
    
    $message = get_string('batchcreated', 'local_batchmanager');
    if ($importcount > 0) {
        $message .= ' ' . get_string('csvimported', 'local_batchmanager', $importcount);
    }
    
    redirect(new moodle_url('/local/batchmanager/index.php'), $message);
}

echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('createbatch', 'local_batchmanager'));
$form->display();
echo $OUTPUT->footer();