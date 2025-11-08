// Toggle mobile navigation
const navToggle = document.getElementById('navToggle');
const navMenu = document.getElementById('navMenu');
const moreDropdown = document.getElementById('moreDropdown');

if (navToggle && navMenu) {
    navToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        navMenu.classList.toggle('active');
        navToggle.classList.toggle('active');
        
        // Fermer le dropdown "More" quand on ouvre/ferme le menu mobile
        if (window.innerWidth <= 768) {
            moreDropdown.classList.remove('active');
        }
    });
}

// Gestion du dropdown "More" en mobile
if (moreDropdown) {
    const moreLink = moreDropdown.querySelector('.nav-link');
    
    moreLink.addEventListener('click', (e) => {
        if (window.innerWidth <= 768) {
            e.preventDefault();
            e.stopPropagation();
            moreDropdown.classList.toggle('active');
        }
    });
}

// Fermer le menu mobile quand on clique en dehors
document.addEventListener('click', (e) => {
    if (navMenu.classList.contains('active') && 
        !navMenu.contains(e.target) && 
        !navToggle.contains(e.target)) {
        navMenu.classList.remove('active');
        navToggle.classList.remove('active');
    }
});

// Fermer le menu mobile quand on clique sur un lien
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
            navMenu.classList.remove('active');
            navToggle.classList.remove('active');
        }
    });
});

// Dark mode toggle
const themeToggle = document.getElementById('themeToggle');
let themeIcon = null;

if (themeToggle) {
    themeIcon = themeToggle.querySelector('i');
    
    themeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        
        if (document.body.classList.contains('dark-mode')) {
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        } else {
            themeIcon.classList.remove('fa-sun');
            themeIcon.classList.add('fa-moon');
        }
        
        // Sauvegarder la préférence dans localStorage
        const isDarkMode = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isDarkMode);
    });
}

// Vérifier la préférence de thème sauvegardée
document.addEventListener('DOMContentLoaded', () => {
    if (themeToggle && themeIcon) {
        const savedDarkMode = localStorage.getItem('darkMode') === 'true';
        
        if (savedDarkMode) {
            document.body.classList.add('dark-mode');
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        }
    }
});

// Scroll animations
const fadeElements = document.querySelectorAll('.fade-in');

const fadeInOnScroll = () => {
    fadeElements.forEach(element => {
        const elementTop = element.getBoundingClientRect().top;
        const elementVisible = 150;
        
        if (elementTop < window.innerHeight - elementVisible) {
            element.classList.add('visible');
        }
    });
};

window.addEventListener('scroll', fadeInOnScroll);
window.addEventListener('load', fadeInOnScroll);

// Get Started button functionality
const ctaButton = document.querySelector('.cta-button');
if (ctaButton) {
    ctaButton.addEventListener('click', function() {
        window.location.href = 'registration-login.html';
    });
}

// Gestion du redimensionnement de la fenêtre
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        if (navMenu) navMenu.classList.remove('active');
        if (navToggle) navToggle.classList.remove('active');
        if (moreDropdown) moreDropdown.classList.remove('active');
    }
});

// Highlight active page in navigation
function setActivePage() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const linkHref = link.getAttribute('href');
        if (linkHref === currentPage) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

// Set active page on load
document.addEventListener('DOMContentLoaded', setActivePage);

//*****************LOG IN AND REGISTRATION*******************

// Attendre que le DOM soit complètement chargé
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des onglets de connexion/inscription
    const authTabs = document.querySelectorAll('.auth-tab');
    const authForms = document.querySelectorAll('.auth-form');

    // Vérifier si on est sur la page login/signup
    if (authTabs.length > 0 && authForms.length > 0) {
        console.log('Initialisation de la page login/signup');
        
        // Initialiser l'onglet actif
        const activeTab = document.querySelector('.auth-tab.active');
        if (!activeTab && authTabs.length > 0) {
            authTabs[0].classList.add('active');
        }
        
        const activeForm = document.querySelector('.auth-form.active');
        if (!activeForm && authForms.length > 0) {
            authForms[0].classList.add('active');
        }

        authTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');
                console.log('Changement d\'onglet vers:', tabId);
                
                // Activer l'onglet sélectionné
                authTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                // Afficher le formulaire correspondant
                authForms.forEach(form => {
                    form.classList.remove('active');
                    if (form.id === `${tabId}Form`) {
                        form.classList.add('active');
                    }
                });
            });
        });

        // Basculer entre connexion et inscription
        const switchToLogin = document.getElementById('switchToLogin');
        if (switchToLogin) {
            switchToLogin.addEventListener('click', (e) => {
                e.preventDefault();
                console.log('Switch to login clicked');
                // Activer l'onglet login
                authTabs[0].click();
            });
        }

        // Sélection du type d'utilisateur
        const userTypes = document.querySelectorAll('.user-type');
        let selectedUserType = 'student';

        if (userTypes.length > 0) {
            userTypes.forEach(type => {
                type.addEventListener('click', () => {
                    userTypes.forEach(t => t.classList.remove('selected'));
                    type.classList.add('selected');
                    selectedUserType = type.getAttribute('data-type');
                    console.log('Type d\'utilisateur sélectionné:', selectedUserType);
                });
            });

            // Sélectionner "Étudiant" par défaut
            const hasSelected = document.querySelector('.user-type.selected');
            if (!hasSelected) {
                userTypes[0].classList.add('selected');
            }
        }

        // Gestion de la soumission du formulaire de connexion
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                
                const email = document.getElementById('loginEmail').value;
                const password = document.getElementById('loginPassword').value;
                
                console.log(`Tentative de connexion en tant que ${selectedUserType} avec l'email: ${email}`);
                
                // Simulation de connexion
                alert(`Connexion réussie en tant que ${selectedUserType}!`);
                
                // Redirection selon le type d'utilisateur
                switch(selectedUserType) {
                    case 'student':
                        window.location.href = 'student-dashboard.html';
                        break;
                    case 'professor':
                        window.location.href = 'professor-dashboard.html';
                        break;
                    case 'department':
                        window.location.href = 'department-dashboard.html';
                        break;
                    default:
                        window.location.href = 'student-dashboard.html';
                }
            });
        }

        // Gestion de la soumission du formulaire d'inscription
        const signupForm = document.getElementById('signupForm');
        if (signupForm) {
            signupForm.addEventListener('submit', (e) => {
                e.preventDefault();
                
                const firstName = document.getElementById('firstName').value;
                const lastName = document.getElementById('lastName').value;
                const email = document.getElementById('signupEmail').value;
                const password = document.getElementById('signupPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                
                console.log(`Tentative d'inscription en tant que ${selectedUserType}`);
                
                // Vérification des mots de passe
                if (password !== confirmPassword) {
                    alert('Les mots de passe ne correspondent pas');
                    return;
                }
                
                // Vérifier les termes
                const acceptTerms = document.getElementById('acceptTerms');
                if (!acceptTerms.checked) {
                    alert('Veuillez accepter les conditions d\'utilisation');
                    return;
                }
                
                // Génération du QR Code pour les étudiants
                if (selectedUserType === 'student') {
                    console.log('Génération du QR Code pour étudiant');
                    generateQRCode(email, firstName, lastName);
                    const qrModal = document.getElementById('qrModal');
                    if (qrModal) {
                        qrModal.classList.add('active');
                    }
                } else {
                    alert(`Inscription réussie en tant que ${selectedUserType}!`);
                    // Redirection selon le type d'utilisateur
                    switch(selectedUserType) {
                        case 'professor':
                            window.location.href = 'professor-dashboard.html';
                            break;
                        case 'department':
                            window.location.href = 'department-dashboard.html';
                            break;
                    }
                }
            });
        }

        // Génération du QR Code
        function generateQRCode(email, firstName, lastName) {
            const qrData = JSON.stringify({
                type: 'student',
                email: email,
                name: `${firstName} ${lastName}`,
                id: Math.random().toString(36).substring(2, 10).toUpperCase(),
                timestamp: new Date().toISOString()
            });
            
            const qrCanvas = document.getElementById('qrCode');
            if (qrCanvas && typeof QRCode !== 'undefined') {
                QRCode.toCanvas(qrCanvas, qrData, {
                    width: 200,
                    margin: 2,
                    color: {
                        dark: '#2563eb',
                        light: '#ffffff'
                    }
                }, function (error) {
                    if (error) {
                        console.error('Erreur génération QR Code:', error);
                        alert('Erreur lors de la génération du QR Code');
                    } else {
                        console.log('QR Code généré avec succès');
                    }
                });
            } else {
                console.error('Canvas QR Code non trouvé ou librairie QRCode non chargée');
                alert('Erreur: Impossible de générer le QR Code');
            }
        }

        // Fermeture de la modal QR Code
        const closeModal = document.getElementById('closeModal');
        if (closeModal) {
            closeModal.addEventListener('click', () => {
                const qrModal = document.getElementById('qrModal');
                if (qrModal) {
                    qrModal.classList.remove('active');
                    // Redirection vers le tableau de bord étudiant
                    window.location.href = 'student-dashboard.html';
                }
            });
        }

        // Renvoyer le QR Code par email
        const sendEmail = document.getElementById('sendEmail');
        if (sendEmail) {
            sendEmail.addEventListener('click', () => {
                alert('QR Code renvoyé à votre adresse email!');
            });
        }

        // Mot de passe oublié
        const forgotPassword = document.getElementById('forgotPassword');
        if (forgotPassword) {
            forgotPassword.addEventListener('click', (e) => {
                e.preventDefault();
                alert('Un lien de réinitialisation a été envoyé à votre adresse email.');
            });
        }
    }
});


//******************** ADD STUDENT**************************/
// Department and level configuration
const departmentLevels = {
    'computer_science': {
        'L1': ['General'],
        'L2': ['General'],
        'L3': ['ISIL', 'SI'],
        'M1': ['ASD', 'ISII', 'RES'],
        'M2': ['ASD', 'ISII', 'RES']
    },
    'snv': {
        'L1': ['General'],
        'L2': ['General'],
        'L3': ['Biology', 'Biochemistry', 'Geology'],
        'M1': ['Molecular Biology', 'Ecology', 'Biotechnology'],
        'M2': ['Molecular Biology', 'Ecology', 'Biotechnology']
    },
    'architecture': {
        'L1': ['General'],
        'L2': ['General'],
        'L3': ['Urban Planning', 'Interior Design', 'Landscape'],
        'M1': ['Urban Design', 'Sustainable Architecture', 'Heritage'],
        'M2': ['Urban Design', 'Sustainable Architecture', 'Heritage']
    },
    'mathematics': {
        'L1': ['General'],
        'L2': ['General'],
        'L3': ['Pure Mathematics', 'Applied Mathematics', 'Statistics'],
        'M1': ['Algebra', 'Analysis', 'Probability'],
        'M2': ['Algebra', 'Analysis', 'Probability']
    },
    'sm': {
        'L1': ['General'],
        'L2': ['General'],
        'L3': ['Metallurgy', 'Polymers', 'Ceramics'],
        'M1': ['Advanced Materials', 'Nanomaterials', 'Composite Materials'],
        'M2': ['Advanced Materials', 'Nanomaterials', 'Composite Materials']
    }
};

// Level options for all departments
const levelOptions = [
    { value: 'L1', text: 'License 1' },
    { value: 'L2', text: 'License 2' },
    { value: 'L3', text: 'License 3' },
    { value: 'M1', text: 'Master 1' },
    { value: 'M2', text: 'Master 2' }
];

// Auto-generate student ID
function generateMatricule() {
    const year = new Date().getFullYear().toString().substr(-2);
    const random = Math.floor(1000 + Math.random() * 9000);
    return `STU${year}${random}`;
}

// Update level options based on department
function updateLevels() {
    const department = document.getElementById('department');
    const levelSelect = document.getElementById('level');
    
    if (!department || !levelSelect) return;
    
    // Clear existing options
    levelSelect.innerHTML = '<option value="">Select Level</option>';
    
    if (department.value && departmentLevels[department.value]) {
        // Add all level options
        levelOptions.forEach(level => {
            const option = document.createElement('option');
            option.value = level.value;
            option.textContent = level.text;
            levelSelect.appendChild(option);
        });
    }
    
    // Clear specialty
    const specialtySelect = document.getElementById('specialty');
    if (specialtySelect) {
        specialtySelect.innerHTML = '<option value="">Select Specialty</option>';
    }
}

// Update specialty options based on level
function updateSpecialties() {
    const department = document.getElementById('department');
    const level = document.getElementById('level');
    const specialtySelect = document.getElementById('specialty');
    
    if (!department || !level || !specialtySelect) return;
    
    // Clear existing options
    specialtySelect.innerHTML = '<option value="">Select Specialty</option>';
    
    if (department.value && level.value && departmentLevels[department.value] && departmentLevels[department.value][level.value]) {
        const specialties = departmentLevels[department.value][level.value];
        specialties.forEach(specialty => {
            const option = document.createElement('option');
            option.value = specialty.toLowerCase().replace(' ', '_');
            option.textContent = specialty;
            specialtySelect.appendChild(option);
        });
    }
}

// Initialize form
document.addEventListener('DOMContentLoaded', () => {
    const matriculeField = document.getElementById('matricule');
    if (matriculeField && !matriculeField.value) {
        matriculeField.value = generateMatricule();
    }

    // Add event listeners
    const departmentSelect = document.getElementById('department');
    const levelSelect = document.getElementById('level');
    
    if (departmentSelect) {
        departmentSelect.addEventListener('change', updateLevels);
    }
    
    if (levelSelect) {
        levelSelect.addEventListener('change', updateSpecialties);
    }

    // Form handling
    const saveBtn = document.getElementById('saveBtn');
    const saveAddAnotherBtn = document.getElementById('saveAddAnotherBtn');
    const cancelBtn = document.getElementById('cancelBtn');

    if (saveBtn) {
        saveBtn.addEventListener('click', function(e) {
            e.preventDefault();
            handleSave(false);
        });
    }

    if (saveAddAnotherBtn) {
        saveAddAnotherBtn.addEventListener('click', function(e) {
            e.preventDefault();
            handleSave(true);
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to cancel? All changes will be lost.')) {
                window.location.href = 'index.html';
            }
        });
    }
});

// Handle save functionality
function handleSave(addAnother) {
    const matricule = document.getElementById('matricule').value;
    const firstName = document.getElementById('firstName').value;
    const lastName = document.getElementById('lastName').value;
    const department = document.getElementById('department').value;
    const level = document.getElementById('level').value;
    const year = document.getElementById('year').value;
    
    if (matricule && firstName && lastName && department && level && year) {
        const departmentText = document.getElementById('department').options[document.getElementById('department').selectedIndex].text;
        const levelText = document.getElementById('level').options[document.getElementById('level').selectedIndex].text;
        
        alert(`Student ${firstName} ${lastName} (${matricule}) added successfully!\nDepartment: ${departmentText}\nLevel: ${levelText}\nYear: ${year}`);
        
        if (addAnother) {
            // Reset form with new ID for adding another student
            document.querySelector('form').reset();
            document.getElementById('matricule').value = generateMatricule();
            document.getElementById('firstName').focus();
        } else {
            // Just reset form with new ID
            document.querySelector('form').reset();
            document.getElementById('matricule').value = generateMatricule();
        }
    } else {
        alert('Please fill all required fields (*)');
    }
}