// Initialize index page
document.addEventListener('DOMContentLoaded', function() {
    updateIndexStatistics();
});

// Update statistics on index page
function updateIndexStatistics() {
    const totalStudents = students.length;
    const presentToday = students.filter(student => 
        student.sessions[0].present
    ).length;
    const participationRate = Math.round((students.filter(student => 
        student.sessions.some(session => session.participated)
    ).length / totalStudents) * 100);
    
    if (document.getElementById('total-students')) {
        document.getElementById('total-students').textContent = totalStudents;
    }
    if (document.getElementById('present-today')) {
        document.getElementById('present-today').textContent = presentToday;
    }
    if (document.getElementById('attendance-rate')) {
        document.getElementById('attendance-rate').textContent = `${participationRate}%`;
    }
}