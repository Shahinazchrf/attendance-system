// Initialize attendance page
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('attendance-table')) {
        populateAttendanceTable();
        updateStatistics();
        setupEventListeners();
    }
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
            ${student.sessions.map((session, index) => `
                <td class="${session.present ? 'status-present' : 'status-absent'}">
                    <input type="checkbox" class="attendance-checkbox" ${session.present ? 'checked' : ''} data-student="${student.id}" data-session="${index}" data-type="present">
                </td>
                <td class="${session.participated ? 'status-present' : 'status-absent'}">
                    <input type="checkbox" class="participation-checkbox" ${session.participated ? 'checked' : ''} data-student="${student.id}" data-session="${index}" data-type="participated">
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
                
                // Save to localStorage
                saveStudents();
                
                // Update the table
                populateAttendanceTable();
                updateStatistics();
                updateReportStatistics();
            }
        });
    });
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

// Apply jQuery effects
function applyJQueryEffects() {
    if (typeof $ === 'undefined') {
        console.warn('jQuery is not loaded');
        return;
    }
    
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

// Highlight excellent students
function highlightExcellentStudents() {
    if (typeof $ === 'undefined') return;
    
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
    if (typeof $ === 'undefined') return;
    
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