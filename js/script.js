let theoryCourseCount = 0;
let practicalCourseCount = 0;

function addTheoryCourse() {
    const theoryCoursesDiv = document.getElementById('theoryCourses');
    theoryCourseCount++;
    
    const courseHtml = `
        <div class="theory-course" id="theory-${theoryCourseCount}">
            <span class="remove-course" onclick="removeCourse('theory-${theoryCourseCount}')">&times;</span>
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Course Name</label>
                        <input type="text" class="form-control" name="theory_course_name[]" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Teacher Name</label>
                        <input type="text" class="form-control" name="theory_teacher_name[]" required>
                    </div>
                </div>
            </div>

            <div class="percentage-box mb-3">
                <label>Your percentage in the Class</label>
                <div class="radio-group">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="percentage_${theoryCourseCount}" value="above_80">
                        <label class="form-check-label">Above 80%</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="percentage_${theoryCourseCount}" value="65_80">
                        <label class="form-check-label">65% to 80%</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="percentage_${theoryCourseCount}" value="50_65">
                        <label class="form-check-label">50% to 65%</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="percentage_${theoryCourseCount}" value="below_50">
                        <label class="form-check-label">Below 50%</label>
                    </div>
                </div>
            </div>

            <div class="evaluation-questions">
                ${generateTheoryQuestions(theoryCourseCount)}
            </div>
        </div>
    `;
    
    theoryCoursesDiv.insertAdjacentHTML('beforeend', courseHtml);
}

function generateTheoryQuestions(count) {
    const questions = [
        "How many lectures were conducted?",
        "How much is the syllabus coverage?",
        "Whether the lectures were organized as per time table and regularly held during the semester?",
        "Whether the lectures were well prepared, organized and course material is well structured?",
        "Was the Blackboard writing clear and organized?",
        "Were any Audio-Visual Aids used?",
        "Were the lectures delivered with emphasize of fundamental concepts and with illustrative examples?",
        "Whether difficult topics were taught with adequate attention and ease?",
        "Did the faculty provide you new knowledge and has command over the subject?",
        "Was the instructor enthusiastic about teaching?",
        "Was the instructor able to deliver lectures with good communication skill?",
        "Were you encouraged to ask questions, to make lectures interactive and lively?",
        "Did the course improve your understanding of concepts, principles in this field and motivated you to think and learn?",
        "Were the assignments and tests challenging? (with new & novel problem solving approach)",
        "Was the evaluation fair and impartial? And did it help you to improve?",
        "Whether the teacher was effective in preparing students for exams?",
        "Whether teacher was always accessible to the students for counseling, guidance and solving queries off the classroom hours."
    ];

    return questions.map((q, index) => `
        <div class="rating-group">
            <div class="rating-label">${q}</div>
            <div class="rating-options">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="theory_q${count}_${index}" value="4">
                    <label class="form-check-label">4</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="theory_q${count}_${index}" value="3">
                    <label class="form-check-label">3</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="theory_q${count}_${index}" value="2">
                    <label class="form-check-label">2</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="theory_q${count}_${index}" value="1">
                    <label class="form-check-label">1</label>
                </div>
            </div>
        </div>
    `).join('');
}

function addPracticalCourse() {
    const practicalCoursesDiv = document.getElementById('practicalCourses');
    practicalCourseCount++;
    
    const courseHtml = `
        <div class="practical-course" id="practical-${practicalCourseCount}">
            <span class="remove-course" onclick="removeCourse('practical-${practicalCourseCount}')">&times;</span>
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Course Name</label>
                        <input type="text" class="form-control" name="practical_course_name[]" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Teacher Name</label>
                        <input type="text" class="form-control" name="practical_teacher_name[]" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Laboratory Name</label>
                        <input type="text" class="form-control" name="practical_lab_name[]" required>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Laboratory Assistant/Programmer</label>
                        <input type="text" class="form-control" name="practical_lab_assistant[]">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Is Lab maintained regularly?</label>
                        <select class="form-control" name="practical_lab_maintained[]">
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="evaluation-questions">
                ${generatePracticalQuestions(practicalCourseCount)}
            </div>
        </div>
    `;
    
    practicalCoursesDiv.insertAdjacentHTML('beforeend', courseHtml);
}

function generatePracticalQuestions(count) {
    const questions = [
        "Was the selection of experiment commensurate with the theory?",
        "Performance of the experiment:",
        "a) Whether experimental set-up was well maintained, fully operational & adequate?",
        "b) Whether teacher helped you in understanding the experimental observations/outcome and explaining the difficulties raised while performing the experiment?",
        "c) Was the experiment leading towards proper conclusions/interpretations?",
        "d) Whether the experiment could trigger you for any creative idea?",
        "Submission of Experiment:",
        "a) Whether precise, updated & self-explanatory lab manuals were provided?",
        "b) Whether submission of experimental write-up was routine & repetitive?",
        "c) Whether teacher does assessment of experiment regularly and gives feedback?",
        "Whether the entire lab session was useful in clarifying your knowledge of the theory?",
        "Whether you are confident with the use of the concepts, instruments and their application in further studies?"
    ];

    return questions.map((q, index) => `
        <div class="rating-group">
            <div class="rating-label">${q}</div>
            <div class="rating-options">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="practical_q${count}_${index}" value="4">
                    <label class="form-check-label">4</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="practical_q${count}_${index}" value="3">
                    <label class="form-check-label">3</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="practical_q${count}_${index}" value="2">
                    <label class="form-check-label">2</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="practical_q${count}_${index}" value="1">
                    <label class="form-check-label">1</label>
                </div>
            </div>
        </div>
    `).join('');
}

function removeCourse(id) {
    document.getElementById(id).remove();
}

// Initialize general feedback sections
document.addEventListener('DOMContentLoaded', function() {
    initializeGeneralFeedback();
});

function initializeGeneralFeedback() {
    const sections = [
        { name: "Principal", id: "principal" },
        { name: "Head of the Department", id: "hod" },
        { name: "Library", id: "library" },
        { name: "Training and Placement", id: "training" },
        { name: "Student Section", id: "student" },
        { name: "Accounts Section", id: "accounts" },
        { name: "Site Section", id: "site" },
        { name: "Sports", id: "sports" }
    ];

    const evaluationAreas = [
        "Availability to students",
        "Attending the difficulties/demands of students",
        "Behavior with students",
        "Service to students",
        "Satisfaction of expectation of students",
        "General Evaluation"
    ];

    const generalFeedbackDiv = document.querySelector('#feedbackForm .card-body');
    
    sections.forEach(section => {
        const sectionHtml = `
            <div class="section-feedback mb-4">
                <h5 class="section-title">${section.name}</h5>
                <table class="evaluation-table">
                    <thead>
                        <tr>
                            <th>Evaluation Area</th>
                            <th>4</th>
                            <th>3</th>
                            <th>2</th>
                            <th>1</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${evaluationAreas.map(area => `
                            <tr>
                                <td>${area}</td>
                                <td><input type="radio" name="${section.id}_${area.toLowerCase().replace(/[^a-z0-9]/g, '_')}" value="4"></td>
                                <td><input type="radio" name="${section.id}_${area.toLowerCase().replace(/[^a-z0-9]/g, '_')}" value="3"></td>
                                <td><input type="radio" name="${section.id}_${area.toLowerCase().replace(/[^a-z0-9]/g, '_')}" value="2"></td>
                                <td><input type="radio" name="${section.id}_${area.toLowerCase().replace(/[^a-z0-9]/g, '_')}" value="1"></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
        generalFeedbackDiv.insertAdjacentHTML('beforeend', sectionHtml);
    });
}

// Form validation
document.getElementById('feedbackForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Add your validation logic here
    
    // If validation passes, submit the form
    this.submit();
}); 