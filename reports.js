// Initialize reports page
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('detailed-report-content')) {
        updateReportsPage();
    }
});

// Update reports page with detailed information
function updateReportsPage() {
    updateReportStatistics();
    updateDetailedReport();
    updateReportStats();
}

// Update detailed report
function updateDetailedReport() {
    const detailedReport = document.getElementById('detailed-report-content');
    if (!detailedReport) return;
    
    const totalStudents = students.length;
    
    // Calculate various statistics
    const excellentStudents = students.filter(student => {
        const absences = student.sessions.filter(session => !session.present).length;
        const participations = student.sessions.filter(session => session.participated).length;
        return absences < 3 && participations >= 4;
    }).length;
    
    const warningStudents = students.filter(student => {
        const absences = student.sessions.filter(session => !session.present).length;
        return absences >= 3;
    }).length;
    
    const overallParticipation = Math.round((students.filter(student => 
        student.sessions.some(session => session.participated)
    ).length / totalStudents) * 100);
    
    // Generate detailed report content
    detailedReport.innerHTML = `
        <div class="report-stats">
            <h4>Overall Statistics</h4>
            <p><strong>Total Students:</strong> ${totalStudents}</p>
            <p><strong>Excellent Performance:</strong> ${excellentStudents} students</p>
            <p><strong>Need Attention:</strong> ${warningStudents} students</p>
            <p><strong>Overall Participation Rate:</strong> ${overallParticipation}%</p>
        </div>
        
        <div class="chart-container" style="margin-top: 20px;">
            <h4>Performance Distribution</h4>
            <div class="chart-bar" style="width: ${(excellentStudents / totalStudents) * 100}%; background-color: #27ae60;">
                Excellent: ${excellentStudents}
            </div>
            <div class="chart-bar" style="width: ${(warningStudents / totalStudents) * 100}%; background-color: #e74c3c;">
                Need Attention: ${warningStudents}
            </div>
        </div>
        
        <div style="margin-top: 30px;">
            <h4>Student Performance Details</h4>
            <table style="width: 100%; margin-top: 15px;">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Absences</th>
                        <th>Participations</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${students.map(student => {
                        const absences = student.sessions.filter(session => !session.present).length;
                        const participations = student.sessions.filter(session => session.participated).length;
                        const status = generateMessage(absences, participations);
                        return `
                            <tr>
                                <td>${student.id}</td>
                                <td>${student.firstName} ${student.lastName}</td>
                                <td>${student.course}</td>
                                <td>${absences}</td>
                                <td>${participations}</td>
                                <td>${status}</td>
                            </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        </div>
    `;
}

// Update report statistics on reports page
function updateReportStats() {
    const totalStudents = students.length;
    const excellentStudents = students.filter(student => {
        const absences = student.sessions.filter(session => !session.present).length;
        const participations = student.sessions.filter(session => session.participated).length;
        return absences < 3 && participations >= 4;
    }).length;
    
    const warningStudents = students.filter(student => {
        const absences = student.sessions.filter(session => !session.present).length;
        return absences >= 3;
    }).length;
    
    const overallParticipation = Math.round((students.filter(student => 
        student.sessions.some(session => session.participated)
    ).length / totalStudents) * 100);
    
    if (document.getElementById('total-students')) {
        document.getElementById('total-students').textContent = totalStudents;
    }
    if (document.getElementById('excellent-students')) {
        document.getElementById('excellent-students').textContent = excellentStudents;
    }
    if (document.getElementById('warning-students')) {
        document.getElementById('warning-students').textContent = warningStudents;
    }
    if (document.getElementById('participation-rate')) {
        document.getElementById('participation-rate').textContent = `${overallParticipation}%`;
    }
}