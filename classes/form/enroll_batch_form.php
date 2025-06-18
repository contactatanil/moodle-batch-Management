/**
 * Form for enrolling batches in courses
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class enroll_batch_form extends moodleform {
    
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'] ?? null;
        $batchid = $this->_customdata['batchid'] ?? null;
        
        // Course selection mode
        if ($courseid) {
            // Single course mode
            $course = $DB->get_record('course', array('id' => $courseid));
            $mform->addElement('static', 'courseinfo', 'Course', 
                              format_string($course->fullname) . ' (' . $course->shortname . ')');
            $mform->addElement('hidden', 'courseid', $courseid);
            $mform->setType('courseid', PARAM_INT);
        } else {
            // Multiple course mode
            $courses = local_batchmanager_get_available_courses();
            $courseoptions = array();
            foreach ($courses as $course) {
                $courseoptions[$course->id] = $course->fullname . ' (' . $course->shortname . ')';
            }
            
            $courseselect = $mform->addElement('autocomplete', 'courseids', get_string('selectcourses', 'local_batchmanager'));
            $courseselect->setMultiple(true);
            $mform->addOptions($courseselect, $courseoptions);
            $mform->addRule('courseids', null, 'required', null, 'client');
        }
        
        // Batch selection
        $batches = local_batchmanager_get_batches();
        $batchoptions = array('' => get_string('selectbatch', 'local_batchmanager'));
        foreach ($batches as $batch) {
            $batchoptions[$batch->id] = $batch->name . ' (' . $batch->studentcount . ' students)';
        }
        
        $mform->addElement('select', 'batchid', get_string('selectbatch', 'local_batchmanager'), $batchoptions);
        $mform->addRule('batchid', null, 'required', null, 'client');
        
        if ($batchid) {
            $mform->setDefault('batchid', $batchid);
        }
        
        // Role selection
        $roles = get_assignable_roles(context_system::instance());
        $mform->addElement('select', 'roleid', get_string('enrollmentrole', 'local_batchmanager'), $roles);
        $mform->setDefault('roleid', 5); // Student role
        
        $buttontext = $courseid ? 'Enroll Batch' : 'Enroll in Selected Courses';
        $this->add_action_buttons(true, $buttontext);
    }
}
