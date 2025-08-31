// Global Variables
let mobileMenuOpen = false;
let currentServiceFilter = 'permits';
let currentQuickTab = 'disclosure';

// DOM Elements
const mobileMenuBtn = document.getElementById('mobile-menu-btn');
const navMenu = document.getElementById('nav-menu');
const modal = document.getElementById('modal');
const modalTitle = document.getElementById('modal-title');
const modalBody = document.getElementById('modal-body');

// Announcements data
const announcements = {
    1: {
        title: 'Community Health Program Launch',
        date: 'August 25, 2025',
        content: 'We are excited to announce the launch of our new community health initiative. This program aims to provide better healthcare services to all residents of Barangay Tinga Labak. The program includes free medical checkups, health education seminars, and vaccination drives. All residents are encouraged to participate and take advantage of these free healthcare services.'
    },
    2: {
        title: 'Monthly Barangay Assembly',
        date: 'August 20, 2025',
        content: 'All residents are cordially invited to attend our monthly barangay assembly. We will be discussing important community matters including budget allocation, ongoing projects, and upcoming events. Your participation and input are valuable to us as we work together to improve our community.'
    },
    3: {
        title: 'Clean Environment Drive',
        date: 'August 15, 2025',
        content: 'Join us in our mission to keep Barangay Tinga Labak clean and green. Our environmental cleanup initiative includes proper waste segregation, tree planting activities, and community beautification projects. Together, we can create a cleaner and healthier environment for our families and future generations.'
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    initializeNavigation();
    initializeServiceFilters();
    initializeQuickLinks();
    initializeCalendar();
    initializeSmoothScrolling();
    initializeScrollEffects();
});

// Navigation Functions
function initializeNavigation() {
    // Mobile menu toggle
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
    }

    // Dropdown toggle for mobile
    const dropdownToggles = document.querySelectorAll('.nav-link[data-toggle="dropdown"]');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                const parent = this.parentElement;
                const dropdownMenu = parent.querySelector('.dropdown-menu');
                const isActive = dropdownMenu.classList.contains('active');
                
                // Close all other dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    if (menu !== dropdownMenu) {
                        menu.classList.remove('active');
                        menu.parentElement.querySelector('.nav-link').classList.remove('active');
                    }
                });

                // Toggle current dropdown
                dropdownMenu.classList.toggle('active');
                this.classList.toggle('active');
            }
        });
    });

    // Close mobile menu when clicking nav links without dropdown
    const navLinks = document.querySelectorAll('.nav-link:not([data-toggle="dropdown"])');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (mobileMenuOpen) {
                toggleMobileMenu();
            }
        });
    });

    // Close mobile menu when clicking dropdown links
    const dropdownLinks = document.querySelectorAll('.dropdown-link');
    dropdownLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (mobileMenuOpen) {
                toggleMobileMenu();
            }
        });
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
        if (mobileMenuOpen && !navMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
            toggleMobileMenu();
        }
    });
}

function toggleMobileMenu() {
    mobileMenuOpen = !mobileMenuOpen;
    navMenu.classList.toggle('active');
    mobileMenuBtn.classList.toggle('active');
    document.body.style.overflow = mobileMenuOpen ? 'hidden' : '';
}

// Service Filters
function initializeServiceFilters() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const serviceCards = document.querySelectorAll('.service-card');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Update active button
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Filter services
            const filter = btn.dataset.filter;
            currentServiceFilter = filter;

            serviceCards.forEach(card => {
                if (card.dataset.category === filter) {
                    card.classList.remove('hidden');
                    // Add stagger animation
                    card.style.animationDelay = `${Array.from(serviceCards).indexOf(card) * 50}ms`;
                    card.style.animation = 'fadeInUp 0.6s ease forwards';
                } else {
                    card.classList.add('hidden');
                }
            });
        });
    });
}

// Quick Links Functions
function initializeQuickLinks() {
    const quickTabs = document.querySelectorAll('.quick-tab');
    const quickPanels = document.querySelectorAll('.quick-panel');

    quickTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Update active tab
            quickTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            // Show corresponding panel
            const content = tab.dataset.content;
            currentQuickTab = content;

            quickPanels.forEach(panel => {
                if (panel.id === `${content}-panel`) {
                    panel.classList.add('active');
                } else {
                    panel.classList.remove('active');
                }
            });
        });
    });
}

// Calendar Functions
function initializeCalendar() {
    const calendarEl = document.getElementById('calendar');
    if (calendarEl && window.FullCalendar) {
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            },
            events: [
                {
                    title: 'Community Health Program',
                    date: '2025-09-15',
                    description: 'Annual community health program with free medical checkups and consultations.'
                },
                {
                    title: 'Barangay Assembly',
                    date: '2025-09-05',
                    description: 'Monthly barangay assembly meeting to discuss community matters.'
                },
                {
                    title: 'Clean-up Drive',
                    date: '2025-09-20',
                    description: 'Community cleanup initiative for environmental preservation.'
                },
                {
                    title: 'Youth Development Program',
                    date: '2025-09-25',
                    description: 'Skills training and development program for the youth.'
                }
            ],
            eventClick: function (info) {
                showEventModal(info.event);
            }
        });
        calendar.render();
    }
}

// Modal Functions
function showEventModal(event) {
    modalTitle.textContent = event.title;
    modalBody.innerHTML = `
        <div style="margin-bottom: 1rem;">
            <strong>Date:</strong> ${event.start.toLocaleDateString()}
        </div>
        <div>
            <strong>Description:</strong> ${event.extendedProps.description}
        </div>
    `;
    showModal();
}

function showAnnouncementModal(id) {
    const announcement = announcements[id];
    if (announcement) {
        modalTitle.textContent = announcement.title;
        modalBody.innerHTML = `
            <div style="margin-bottom: 1rem;">
                <strong>Date:</strong> ${announcement.date}
            </div>
            <div>
                ${announcement.content}
            </div>
        `;
        showModal();
    }
}

function showModal() {
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Add entrance animation
    setTimeout(() => {
        modal.querySelector('.modal-dialog').style.animation = 'modalSlideIn 0.3s ease-out';
    }, 10);
}

function closeModal() {
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

// Form Functions
function submitContactForm(event) {
    event.preventDefault();

    // Get form data
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData);

    // Show success message
    alert('Thank you for your message! We will get back to you soon.');

    // Reset form
    event.target.reset();
}

function showMoreAnnouncements() {
    alert('More announcements feature would load additional announcement cards here.');
}

// Smooth Scrolling
function initializeSmoothScrolling() {
    const links = document.querySelectorAll('a[href^="#"]');

    links.forEach(link => {
        link.addEventListener('click', function (e) {
            if (!link.closest('.dropdown-menu') || window.innerWidth > 768) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);

                if (targetElement) {
                    const offsetTop = targetElement.getBoundingClientRect().top + window.pageYOffset - 80;

                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
}

// Scroll Effects
function initializeScrollEffects() {
    const navbar = document.getElementById('navbar');
    let lastScrollTop = 0;

    window.addEventListener('scroll', () => {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        // Add background when scrolling
        if (scrollTop > 50) {
            navbar.style.background = '#042c3c';
            navbar.style.boxShadow = 'var(--shadow-md)';
        } else {
            navbar.style.background = 'transparent';
            navbar.style.boxShadow = 'none';
        }


    });
}

// Animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);