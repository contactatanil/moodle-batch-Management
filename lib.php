/**
* Library functions for batch manager
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Get all batches
 */
function local_batchmanager_get_batches() {
    global $DB;
    
    $sql = "SELECT b.*, u.firstname, u.lastname, 
                   COUNT(s.userid) as studentcount
            FROM {local_batchmanager_batches} b
            LEFT JOIN {user} u ON b.createdby = u.id
            LEFT JOIN {local_batchmanager_students} s ON b.id = s.batchid
            GROUP BY b.id, u.firstname, u.lastname
            ORDER BY b.timecreated DESC";
    
    return $DB->get_records_sql($sql);
}

/**
 * Create a new batch
 */
function local_batchmanager_create_batch($name, $description, $userids) {
    global $DB, $USER;
    
    $batch = new stdClass();
    $batch->name = $name;
    $batch->description = $description;
    $batch->timecreated = time();
    $batch->timemodified = time();
    $batch->createdby = $USER->id;
    
    $batchid = $DB->insert_record('local_batchmanager_batches', $batch);
    
    // Add students to batch
    foreach ($userids as $userid) {
        $student = new stdClass();
        $student->batchid = $batchid;
        $student->userid = $userid;
        $student->timecreated = time();
        $DB->insert_record('local_batchmanager_students', $student);
    }
    
    return $batchid;
}

/**
 * Get students in a batch
 */
function local_batchmanager_get_batch_students($batchid) {
    global $DB;
    
    $sql = "SELECT u.*, s.timecreated as dateadded
            FROM {local_batchmanager_students} s
            JOIN {user} u ON s.userid = u.id
            WHERE s.batchid = ?
            ORDER BY u.lastname, u.firstname";
    
    return $DB->get_records_sql($sql, array($batchid));
}

/**
 * Enroll batch students in course
 */
function local_batchmanager_enroll_batch($batchid, $courseid, $roleid) {
    global $DB;
    
    $students = local_batchmanager_get_batch_students($batchid);
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $context = context_course::instance($courseid);
    
    $enrolled = 0;
    foreach ($students as $student) {
        if (!is_enrolled($context, $student->id)) {
            enrol_try_internal_enrol($courseid, $student->id, $roleid);
            $enrolled++;
        }
    }
    
    return $enrolled;
}

/**
 * Enroll batch students in multiple courses
 */
function local_batchmanager_enroll_batch_multiple($batchid, $courseids, $roleid) {
    global $DB;
    
    $students = local_batchmanager_get_batch_students($batchid);
    $totalenrolled = 0;
    $results = array();
    
    foreach ($courseids as $courseid) {
        $course = $DB->get_record('course', array('id' => $courseid));
        if (!$course) continue;
        
        $context = context_course::instance($courseid);
        $enrolled = 0;
        
        foreach ($students as $student) {
            if (!is_enrolled($context, $student->id)) {
                enrol_try_internal_enrol($courseid, $student->id, $roleid);
                $enrolled++;
                $totalenrolled++;
            }
        }
        
        $results[$courseid] = array(
            'course' => $course,
            'enrolled' => $enrolled,
            'total' => count($students)
        );
    }
    
    return array('total' => $totalenrolled, 'details' => $results);
}

/**
 * Import students from CSV
 */
function local_batchmanager_import_csv($csvdata, $batchid) {
    global $DB;
    
    $lines = explode("\n", $csvdata);
    $header = str_getcsv(array_shift($lines));
    
    // Validate header
    $requiredfields = array('email', 'firstname', 'lastname');
    foreach ($requiredfields as $field) {
        if (!in_array($field, $header)) {
            throw new Exception('Missing required field: ' . $field);
        }
    }
    
    $imported = 0;
    $errors = array();
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        $data = str_getcsv($line);
        if (count($data) != count($header)) continue;
        
        $row = array_combine($header, $data);
        
        // Find user by email
        $user = $DB->get_record('user', array('email' => $row['email'], 'deleted' => 0));
        
        if (!$user) {
            // Create new user if not exists
            $user = new stdClass();
            $user->email = $row['email'];
            $user->firstname = $row['firstname'];
            $user->lastname = $row['lastname'];
            $user->username = $row['email'];
            $user->password = hash_internal_user_password(generate_password());
            $user->confirmed = 1;
            $user->timecreated = time();
            $user->timemodified = time();
            
            try {
                $user->id = $DB->insert_record('user', $user);
            } catch (Exception $e) {
                $errors[] = 'Could not create user: ' . $row['email'];
                continue;
            }
        }
        
        // Check if student already in batch
        $exists = $DB->get_record('local_batchmanager_students', 
                                array('batchid' => $batchid, 'userid' => $user->id));
        
        if (!$exists) {
            $student = new stdClass();
            $student->batchid = $batchid;
            $student->userid = $user->id;
            $student->timecreated = time();
            $DB->insert_record('local_batchmanager_students', $student);
            $imported++;
        }
    }
    
    return array('imported' => $imported, 'errors' => $errors);
}

/**
 * Import students from Moodle groups
 */
function local_batchmanager_import_groups($groupids, $batchid) {
    global $DB;
    
    $imported = 0;
    
    foreach ($groupids as $groupid) {
        $members = groups_get_members($groupid);
        
        foreach ($members as $member) {
            // Check if student already in batch
            $exists = $DB->get_record('local_batchmanager_students', 
                                    array('batchid' => $batchid, 'userid' => $member->id));
            
            if (!$exists) {
                $student = new stdClass();
                $student->batchid = $batchid;
                $student->userid = $member->id;
                $student->timecreated = time();
                $DB->insert_record('local_batchmanager_students', $student);
                $imported++;
            }
        }
    }
    
    return $imported;
}

/**
 * Get available courses for enrollment
 */
function local_batchmanager_get_available_courses() {
    global $DB;
    
    $sql = "SELECT id, fullname, shortname, category
            FROM {course} 
            WHERE id > 1 AND visible = 1
            ORDER BY fullname";
    
    return $DB->get_records_sql($sql);
}

/**
 * Get available groups
 */
function local_batchmanager_get_available_groups() {
    global $DB;
    
    $sql = "SELECT g.id, g.name, c.fullname as coursename
            FROM {groups} g
            JOIN {course} c ON g.courseid = c.id
            WHERE c.visible = 1
            ORDER BY c.fullname, g.name";
    
    return $DB->get_records_sql($sql);
}