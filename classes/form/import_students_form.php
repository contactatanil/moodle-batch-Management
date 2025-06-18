/**
 * Form for importing students to existing batch
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class import_students_form extends moodleform {
    
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        $batchid = $this->_customdata['batchid'];
        
        // Import method selection
        $importoptions = array(
            'csv' => get_string('importfromcsv', 'local_batchmanager'),
            'groups' => get_string('importfromgroups', 'local_batchmanager')
        );
        $mform->addElement('select', 'importmethod', get_string('importmethod', 'local_batchmanager'), $importoptions);
        
        // CSV file upload
        $mform->addElement('filepicker', 'csvfile', get_string('csvfile', 'local_batchmanager'), 
                          null, array('accepted_types' => array('.csv')));
        $mform->addElement('static', 'csvformat', '', get_string('csvformat', 'local_batchmanager'));
        $mform->hideIf('csvfile', 'importmethod', 'neq', 'csv');
        $mform->hideIf('csvformat', 'importmethod', 'neq', 'csv');
        
        // Group selection
        $groups = local_batchmanager_get_available_groups();
        $groupoptions = array();
        foreach ($groups as $group) {
            $groupoptions[$group->id] = $group->coursename . ' - ' . $group->name;
        }
        
        $groupselect = $mform->addElement('autocomplete', 'groups', get_string('selectgroups', 'local_batchmanager'));
        $groupselect->setMultiple(true);
        $mform->addOptions($groupselect, $groupoptions);
        $mform->hideIf('groups', 'importmethod', 'neq', 'groups');
        
        $mform->addElement('hidden', 'batchid', $batchid);
        $mform->setType('batchid', PARAM_INT);
        
        $this->add_action_buttons(true, get_string('importstudents', 'local_batchmanager'));
    }
    
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        if ($data['importmethod'] == 'csv' && empty($data['csvfile'])) {
            $errors['csvfile'] = 'Please upload a CSV file';
        }
        
        if ($data['importmethod'] == 'groups' && empty($data['groups'])) {
            $errors['groups'] = 'Please select at least one group';
        }
        
        return $errors;
    }
}