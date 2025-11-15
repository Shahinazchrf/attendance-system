// Sample student data
let students = [
    {
        id: "001",
        lastName: "Ahmed",
        firstName: "Sara",
        course: "Computer Science",
        email: "sara.ahmed@student.univ-alger.dz",
        sessions: [
            { present: true, participated: true },
            { present: false, participated: false },
            { present: false, participated: false },
            { present: false, participated: false },
            { present: false, participated: false },
            { present: false, participated: false }
        ]
    },
    {
        id: "002",
        lastName: "Yacine",
        firstName: "Ali",
        course: "Mathematics",
        email: "ali.yacine@student.univ-alger.dz",
        sessions: [
            { present: true, participated: false },
            { present: false, participated: true },
            { present: true, participated: true },
            { present: true, participated: true },
            { present: true, participated: true },
            { present: true, participated: true }
        ]
    },
    {
        id: "003",
        lastName: "Houcine",
        firstName: "Rania",
        course: "Physics",
        email: "rania.houcine@student.univ-alger.dz",
        sessions: [
            { present: true, participated: true },
            { present: true, participated: false },
            { present: false, participated: true },
            { present: true, participated: true },
            { present: false, participated: false },
            { present: false, participated: false }
        ]
    }
];

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on the attendance page
    if (document.getElementById('attendance-table')) {
        // Populate the attendance table
        populateAttendanceTable();
        
        // Update statistics
        updateStatistics();
        
        // Set up event listeners
        setupEventListeners();
    }
    
    // Check if we're on the add student page
    if (document.getElementById('student-form')) {
        setupFormListeners();
    }
    
    // Initialize mobile navigation
    initMobileNav();
});

// Populate the attendance table with student data
function populateAttendanceTable() {
    const tableBody = document.querySelector('#attendance-table tbody');
    if (!tableBody) return;
    
    tableBody.innerHTML = '';
    
    students.forEach(student => {
        const row = document.createElement('tr');
        
        // Calculate absences and participations
        const absences = student.sessions.filter(session => !session.present).length;
        const participations = student.sessions.filter(session => session.participated).length;
        
        // Add student data to the row
        row.innerHTML = `
            <td>${student.id}</td>
            <td>${student.lastName}</td>
            <td>${student.firstName}</td>
            <td>${student.course}</td>
            ${student.sessions.map(session => `
                <td class="${session.present ? 'status-present' : 'status-absent'}">
                    <input type="checkbox" class="attendance-checkbox" ${session.present ? 'checked' : ''} data-student="${student.id}" data-session="${student.sessions.indexOf(session)}" data-type="present">
                </td>
                <td class="${session.participated ? 'status-present' : 'status-absent'}">
                    <input type="checkbox" class="participation-checkbox" ${session.participated ? 'checked' : ''} data-student="${student.id}" data-session="${student.sessions.indexOf(session)}" data-type="participated">
                </td>
            `).join('')}
            <td>${absences}</td>
            <td>${participations}</td>
            <td>${generateMessage(absences, participations)}</td>
        `;
        
        // Highlight row based on absences
        if (absences < 3) {
            row.classList.add('row-good');
        } else if (absences >= 3 && absences <= 4) {
            row.classList.add('row-warning');
        } else {
            row.classList.add('row-danger');
        }
        
        tableBody.appendChild(row);
    });
    
    // Apply jQuery effects
    applyJQueryEffects();
    
    // Add event listeners to checkboxes
    document.querySelectorAll('.attendance-checkbox, .participation-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const studentId = this.getAttribute('data-student');
            const sessionIndex = parseInt(this.getAttribute('data-session'));
            const type = this.getAttribute('data-type');
            
            // Find the student
            const student = students.find(s => s.id === studentId);
            if (student) {
                if (type === 'present') {
                    student.sessions[sessionIndex].present = this.checked;
                } else {
                    student.sessions[sessionIndex].participated = this.checked;
                }
                
                // Update the table
                populateAttendanceTable();
                updateStatistics();
            }
        });
    });
}

// Generate message based on absences and participations
function generateMessage(absences, participations) {
    if (absences < 3 && participations >= 4) {
        return "Good attendance – Excellent participation";
    } else if (absences >= 3 && absences <= 4) {
        return "Warning – attendance low – You need to participate more";
    } else if (absences >= 5) {
        return "Excluded – too many absences – You need to participate more";
    } else {
        return "Average performance";
    }
}

// Update statistics
function updateStatistics() {
    const totalStudents = students.length;
    const presentToday = students.filter(student => 
        student.sessions[0].present
    ).length;
    const absentToday = totalStudents - presentToday;
    const participationRate = Math.round((students.filter(student => 
        student.sessions.some(session => session.participated)
    ).length / totalStudents) * 100);
    
    document.getElementById('total-students').textContent = totalStudents;
    document.getElementById('present-students').textContent = presentToday;
    document.getElementById('absent-students').textContent = absentToday;
    document.getElementById('participation-rate').textContent = `${participationRate}%`;
}

// Set up all event listeners for attendance page
function setupEventListeners() {
    // Show report button
    const showReportBtn = document.getElementById('show-report');
    if (showReportBtn) {
        showReportBtn.addEventListener('click', showReport);
    }
    
    // Highlight excellent students button
    const highlightBtn = document.getElementById('highlight-excellent');
    if (highlightBtn) {
        highlightBtn.addEventListener('click', highlightExcellentStudents);
    }
    
    // Reset colors button
    const resetBtn = document.getElementById('reset-colors');
    if (resetBtn) {
        resetBtn.addEventListener('click', resetColors);
    }
}

// Set up form listeners for add student page
function setupFormListeners() {
    // Form submission
    const studentForm = document.getElementById('student-form');
    if (studentForm) {
        studentForm.addEventListener('submit', handleFormSubmit);
    }
}

// Initialize mobile navigation
function initMobileNav() {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
        
        // Close mobile menu when clicking on a link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                hamburger.classList.remove('active');
                navMenu.classList.remove('active');
            });
        });
    }
}

// Handle form submission
function handleFormSubmit(event) {
    event.preventDefault();
    
    // Reset previous error messages
    clearErrorMessages();
    
    // Validate form fields
    const isValid = validateForm();
    
    if (isValid) {
        // Get form values
        const studentId = document.getElementById('student-id').value;
        const lastName = document.getElementById('last-name').value;
        const firstName = document.getElementById('first-name').value;
        const email = document.getElementById('email').value;
        const course = document.getElementById('course').value;
        
        // Create new student object
        const newStudent = {
            id: studentId,
            lastName: lastName,
            firstName: firstName,
            email: email,
            course: course,
            sessions: [
                { present: false, participated: false },
                { present: false, participated: false },
                { present: false, participated: false },
                { present: false, participated: false },
                { present: false, participated: false },
                { present: false, participated: false }
            ]
        };
        
        // Add new student to the array
        students.push(newStudent);
        
        // Show confirmation message
        showConfirmationMessage(`Student ${firstName} ${lastName} added successfully!`);
        
        // Reset form
        document.getElementById('student-form').reset();
    }
}

// Validate form fields
function validateForm() {
    let isValid = true;
    
    // Validate Student ID
    const studentId = document.getElementById('student-id').value;
    const studentIdError = document.getElementById('student-id-error');
    if (studentId === '') {
        studentIdError.textContent = 'Student ID is required';
        isValid = false;
    } else if (!/^\d+$/.test(studentId)) {
        studentIdError.textContent = 'Student ID must contain only numbers';
        isValid = false;
    } else if (students.some(student => student.id === studentId)) {
        studentIdError.textContent = 'Student ID already exists';
        isValid = false;
    }
    
    // Validate Last Name
    const lastName = document.getElementById('last-name').value;
    const lastNameError = document.getElementById('last-name-error');
    if (lastName === '') {
        lastNameError.textContent = 'Last name is required';
        isValid = false;
    } else if (!/^[A-Za-z]+$/.test(lastName)) {
        lastNameError.textContent = 'Last name must contain only letters';
        isValid = false;
    }
    
    // Validate First Name
    const firstName = document.getElementById('first-name').value;
    const firstNameError = document.getElementById('first-name-error');
    if (firstName === '') {
        firstNameError.textContent = 'First name is required';
        isValid = false;
    } else if (!/^[A-Za-z]+$/.test(firstName)) {
        firstNameError.textContent = 'First name must contain only letters';
        isValid = false;
    }
    
    // Validate Email
    const email = document.getElementById('email').value;
    const emailError = document.getElementById('email-error');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email === '') {
        emailError.textContent = 'Email is required';
        isValid = false;
    } else if (!emailRegex.test(email)) {
        emailError.textContent = 'Please enter a valid email address';
        isValid = false;
    }
    
    // Validate Course
    const course = document.getElementById('course').value;
    if (course === '') {
        alert('Please select a course');
        isValid = false;
    }
    
    return isValid;
}

// Clear all error messages
function clearErrorMessages() {
    const errorMessages = document.querySelectorAll('.error-message');
    errorMessages.forEach(message => {
        message.textContent = '';
    });
}

// Show confirmation message
function showConfirmationMessage(message) {
    const confirmationElement = document.getElementById('confirmation-message');
    confirmationElement.textContent = message;
    confirmationElement.style.display = 'block';
    
    // Hide confirmation message after 3 seconds
    setTimeout(() => {
        confirmationElement.style.display = 'none';
    }, 3000);
}

// Show attendance report
function showReport() {
    const reportSection = document.getElementById('report-section');
    const reportContent = document.getElementById('report-content');
    
    // Calculate report data
    const totalStudents = students.length;
    const presentStudents = students.filter(student => 
        student.sessions.some(session => session.present)
    ).length;
    const participatingStudents = students.filter(student => 
        student.sessions.some(session => session.participated)
    ).length;
    
    // Generate report content
    reportContent.innerHTML = `
        <div class="report-stats">
            <p><strong>Total Students:</strong> ${totalStudents}</p>
            <p><strong>Students Present (at least one session):</strong> ${presentStudents}</p>
            <p><strong>Students Participated (at least one session):</strong> ${participatingStudents}</p>
        </div>
        <div class="chart-container">
            <h4>Attendance Overview</h4>
            <div class="chart-bar" style="width: ${(presentStudents / totalStudents) * 100}%; background-color: #3498db;">
                Present: ${presentStudents}
            </div>
            <div class="chart-bar" style="width: ${(participatingStudents / totalStudents) * 100}%; background-color: #2ecc71;">
                Participated: ${participatingStudents}
            </div>
        </div>
    `;
    
    // Show the report section
    reportSection.style.display = 'block';
}

// Apply jQuery effects
function applyJQueryEffects() {
    // Highlight row on hover
    $('#attendance-table tbody tr').hover(
        function() {
            $(this).css('background-color', '#e8f4fc');
        },
        function() {
            // Reset to original color based on absence count
            const absences = parseInt($(this).find('td').eq(16).text());
            if (absences < 3) {
                $(this).css('background-color', '#d4edda');
            } else if (absences >= 3 && absences <= 4) {
                $(this).css('background-color', '#fff3cd');
            } else {
                $(this).css('background-color', '#f8d7da');
            }
        }
    );
    
    // Show student info on click
    $('#attendance-table tbody tr').click(function() {
        const lastName = $(this).find('td').eq(1).text();
        const firstName = $(this).find('td').eq(2).text();
        const absences = $(this).find('td').eq(16).text();
        
        alert(`Student: ${firstName} ${lastName}\nAbsences: ${absences}`);
    });
}

// Highlight excellent students
function highlightExcellentStudents() {
    $('#attendance-table tbody tr').each(function() {
        const absences = parseInt($(this).find('td').eq(16).text());
        const participations = parseInt($(this).find('td').eq(17).text());
        if (absences < 3 && participations >= 4) {
            $(this).animate({
                backgroundColor: '#2ecc71'
            }, 1000);
        }
    });
}

// Reset row colors
function resetColors() {
    $('#attendance-table tbody tr').each(function() {
        const absences = parseInt($(this).find('td').eq(16).text());
        let color;
        
        if (absences < 3) {
            color = '#d4edda';
        } else if (absences >= 3 && absences <= 4) {
            color = '#fff3cd';
        } else {
            color = '#f8d7da';
        }
        
        $(this).animate({
            backgroundColor: color
        }, 1000);
    });
}