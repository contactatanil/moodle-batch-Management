 /**
 * Form for creating batches
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class create_batch_form extends moodleform {
    
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        
        // Batch name
        $mform->addElement('text', 'name', get_string('batchname', 'local_batchmanager'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        
        // Description
        $mform->addElement('textarea', 'description', get_string('batchdescription', 'local_batchmanager'));
        $mform->setType('description', PARAM_TEXT);
        
        // Import method selection
        $importoptions = array(
            'manual' => 'Manual Selection',
            'csv' => 'Import from CSV',
            'groups' => 'Import from Groups'
        );
        $mform->addElement('select', 'importmethod', get_string('importmethod', 'local_batchmanager'), $importoptions);
        $mform->setDefault('importmethod', 'manual');
        
        // Manual student selection
        $users = $DB->get_records_sql("
            SELECT id, firstname, lastname, email 
            FROM {user} 
            WHERE deleted = 0 AND suspended = 0 AND id > 1
            ORDER BY lastname, firstname
        ");
        
        $options = array();
        foreach ($users as $user) {
            $options[$user->id] = fullname($user) . ' (' . $user->email . ')';
        }
        
        $select = $mform->addElement('autocomplete', 'students', get_string('addstudents', 'local_batchmanager'));
        $select->setMultiple(true);
        $mform->addOptions($select, $options);
        $mform->hideIf('students', 'importmethod', 'neq', 'manual');
        
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
        
        $this->add_action_buttons();
    }
    
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        if ($data['importmethod'] == 'manual' && empty($data['students'])) {
            $errors['students'] = 'Please select at least one student';
        }
        
        if ($data['importmethod'] == 'csv' && empty($data['csvfile'])) {
            $errors['csvfile'] = 'Please upload a CSV file';
        }
        
        if ($data['importmethod'] == 'groups' && empty($data['groups'])) {
            $errors['groups'] = 'Please select at least one group';
        }
        
        return $errors;
    }
}