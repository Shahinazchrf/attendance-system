// Initialize add student page
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('student-form')) {
        setupFormListeners();
    }
});

// Set up form listeners for add student page
function setupFormListeners() {
    // Form submission
    const studentForm = document.getElementById('student-form');
    if (studentForm) {
        studentForm.addEventListener('submit', handleFormSubmit);
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
        
        // Update localStorage to persist data
        saveStudents();
        
        // Show confirmation message
        showConfirmationMessage(`Student ${firstName} ${lastName} added successfully! Redirecting to attendance page...`);
        
        // Reset form
        document.getElementById('student-form').reset();
        
        // Update statistics
        updateReportStatistics();
        
        // Redirect to attendance page after 2 seconds
        setTimeout(() => {
            window.location.href = 'attendance.html';
        }, 2000);
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