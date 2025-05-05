<?php
require_once('../../config.php');

// Ensure the user is logged in and has the required capability.
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url(new moodle_url('/local/time_analysis/manage_time.php'));
$PAGE->set_context($context);
$PAGE->set_title('Set Minimum Time for Courses');
$PAGE->set_heading('Manage Course Time Requirements');

global $DB, $OUTPUT;

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['course_id'])) {
    $course_id = required_param('course_id', PARAM_INT);
    $category_id = required_param('category_id', PARAM_INT);
    $hours = required_param('hours', PARAM_INT);
    $minutes = required_param('minutes', PARAM_INT);

    // Convert hours & minutes to total minutes
    $time_to_spent = ($hours * 60) + $minutes;

    // Check if entry exists
    $record = $DB->get_record('mdl_time', ['course_id' => $course_id]);

    if ($record) {
        $record->time_to_spent = $time_to_spent;
        $record->updated_at = time();
        $DB->update_record('mdl_time', $record);
    } else {
        $newrecord = new stdClass();
        $newrecord->course_id = $course_id;
        $newrecord->category_id = $category_id;
        $newrecord->time_to_spent = $time_to_spent;
        $newrecord->created_at = time();
        $newrecord->updated_at = time();
        $DB->insert_record('mdl_time', $newrecord);
    }

    redirect(new moodle_url('/local/time_analysis/manage_time.php'), 'Time saved successfully!', null, \core\output\notification::NOTIFY_SUCCESS);
}

// Fetch courses and categories
$courses = $DB->get_records_sql("SELECT c.id, c.fullname, c.category FROM {course} c");
$categories = $DB->get_records_sql("SELECT id, name FROM {course_categories}");
?>

<?php echo $OUTPUT->header(); ?>

<h2>Set Required Time for Courses</h2>

<form method="POST">
    <label for="category_id">Category:</label>
    <select name="category_id" id="category_id" required>
        <option value="">-- Select Category --</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat->id ?>"><?= $cat->name ?></option>
        <?php endforeach; ?>
    </select>

    <label for="course_id">Course:</label>
    <select name="course_id" id="course_id" required disabled>
        <option value="">-- Select a category first --</option>
    </select>

    <label for="hours">Hours:</label>
    <input type="number" name="hours" id="hours" min="0" required>

    <label for="minutes">Minutes:</label>
    <input type="number" name="minutes" id="minutes" min="0" max="59" required>

    <button type="submit">Save</button>
</form>

<script>
    // Store all courses in a JavaScript object
    let courses = <?= json_encode($courses) ?>;

    document.getElementById("category_id").addEventListener("change", function () {
        let selectedCategory = this.value;
        let courseDropdown = document.getElementById("course_id");

        // Reset and enable course dropdown
        courseDropdown.innerHTML = '<option value="">-- Select Course --</option>';
        courseDropdown.disabled = false;

        // Populate courses based on selected category
        Object.values(courses).forEach(course => {
            if (course.category == selectedCategory) {
                let option = document.createElement("option");
                option.value = course.id;
                option.textContent = course.fullname;
                courseDropdown.appendChild(option);
            }
        });

        // If no courses found, show a message
        if (courseDropdown.options.length === 1) {
            let option = document.createElement("option");
            option.value = "";
            option.textContent = "No courses found";
            courseDropdown.appendChild(option);
        }
    });
</script>

<?php echo $OUTPUT->footer(); ?>
