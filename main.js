// Load students from localStorage at start
let students = JSON.parse(localStorage.getItem('students')) || [
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
    // Initialize mobile navigation
    initMobileNav();
    
    // Update report statistics
    updateReportStatistics();
});

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

// Update report statistics automatically
function updateReportStatistics() {
    const totalStudents = students.length;
    if (totalStudents === 0) return;
    
    // Calculate attendance categories
    let excellent = 0, good = 0, average = 0, poor = 0, noData = 0;
    
    students.forEach(student => {
        const absences = student.sessions.filter(session => !session.present).length;
        const participations = student.sessions.filter(session => session.participated).length;
        
        if (absences < 3 && participations >= 4) {
            excellent++;
        } else if (absences >= 3 && absences <= 4) {
            average++;
        } else if (absences >= 5) {
            poor++;
        } else {
            good++;
        }
    });
    
    // Update the pie chart legend on all pages
    const legendItems = document.querySelectorAll('.legend-item');
    if (legendItems.length > 0) {
        legendItems[0].querySelector('span').textContent = `Excellent: ${excellent} (${Math.round((excellent/totalStudents)*100)}%)`;
        legendItems[1].querySelector('span').textContent = `Good: ${good} (${Math.round((good/totalStudents)*100)}%)`;
        legendItems[2].querySelector('span').textContent = `Average: ${average} (${Math.round((average/totalStudents)*100)}%)`;
        legendItems[3].querySelector('span').textContent = `Poor: ${poor} (${Math.round((poor/totalStudents)*100)}%)`;
        legendItems[4].querySelector('span').textContent = `No Data: ${noData}`;
    }
    
    // Update pie chart
    const pieChart = document.querySelector('.pie-chart');
    if (pieChart) {
        const excellentPercent = (excellent / totalStudents) * 100;
        const goodPercent = (good / totalStudents) * 100;
        const averagePercent = (average / totalStudents) * 100;
        const poorPercent = (poor / totalStudents) * 100;
        
        pieChart.style.background = `conic-gradient(
            #27ae60 0% ${excellentPercent}%,
            #3498db ${excellentPercent}% ${excellentPercent + goodPercent}%,
            #f39c12 ${excellentPercent + goodPercent}% ${excellentPercent + goodPercent + averagePercent}%,
            #e74c3c ${excellentPercent + goodPercent + averagePercent}% ${excellentPercent + goodPercent + averagePercent + poorPercent}%,
            #95a5a6 ${excellentPercent + goodPercent + averagePercent + poorPercent}% 100%
        )`;
    }
    
    // Update statistics on index page
    updateIndexStatistics();
}

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

// Save students to localStorage
function saveStudents() {
    localStorage.setItem('students', JSON.stringify(students));
}