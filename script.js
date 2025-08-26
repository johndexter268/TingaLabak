document.addEventListener('DOMContentLoaded', function () {
    // Mobile Menu Toggle
    const menuToggle = document.querySelector('.mobile-menu-toggle button');
    const mainNav = document.querySelector('.main-navigation');
    const menuItems = document.querySelectorAll('.menu-item-has-children');

    if (menuToggle) {
        menuToggle.addEventListener('click', function () {
            mainNav.classList.toggle('mobile-active');
        });
    }

    // Login Modal
    const loginWrapper = document.querySelector('.login-wrapper');
    const loginForm = document.querySelector('.form-box.login');
    const iconClose = document.querySelector('.icon-close');
    const loginLinkPopup = document.querySelector('.loginLink-popup');

    // Show login modal
    loginLinkPopup.addEventListener('click', () => {
        loginWrapper.classList.add('active-popup');
        loginForm.classList.add('show');
    clearInputs();
});

    // Close modal
    iconClose.addEventListener('click', () => {
        loginWrapper.classList.remove('active-popup');
        loginForm.classList.remove('show');
    clearInputs();
});

    // Clear inputs
    function clearInputs() {
        const allInputs = loginWrapper.querySelectorAll('input');
        allInputs.forEach(input => {
        input.value = '';
        if (input.type === 'checkbox') input.checked = false;
    });

    const loginError = document.getElementById("loginError");
        if (loginError) loginError.style.display = "none";
    }

    // Login validation logic
    const loginBtn = document.querySelector('.login-btn');
    const loginError = document.getElementById('loginError');
        loginBtn.addEventListener('click', (e) => {
    e.preventDefault();

    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();

        if (email === "admin@tingalabak.ph" && password === "12345678") {
            loginWrapper.classList.remove('active-popup');
            loginForm.classList.remove('show');
            loginError.style.display = "none";
                window.location.href = "Dboard/dashboard.html";
        } else {
        loginError.style.display = "block";
    }
});

    // Services Logic
    const serviceCategoryButtons = document.querySelectorAll('.category-btn');

    serviceCategoryButtons.forEach(button => {
        const dropdown = document.getElementById(button.dataset.target);
        let isInside = false;

        button.addEventListener('click', function (e) {
             e.stopPropagation(); // prevent bubbling to document
            // Close all other dropdowns
        document.querySelectorAll('.dropdown').forEach(d => {
            if (d !== dropdown) d.classList.remove('active');
        });
        document.querySelectorAll('.category-btn').forEach(b => {
            if (b !== button) b.classList.remove('active');
        });

        // Toggle current
        dropdown.classList.toggle('active');
        button.classList.toggle('active');
    });

  // Keep dropdown open if hovering
    dropdown.addEventListener('mouseenter', () => {
        isInside = true;
    });
    dropdown.addEventListener('mouseleave', () => {
        isInside = false;
        dropdown.classList.remove('active');
        button.classList.remove('active');
    });

    // Also close if mouse leaves button and not in dropdown
    button.addEventListener('mouseleave', () => {
        setTimeout(() => {
        if (!isInside) {
            dropdown.classList.remove('active');
            button.classList.remove('active');
            }
        }, 200);
    });
});

// Close dropdowns when clicking outside
document.addEventListener('click', () => {
  document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('active'));
  document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
});






function toggleDropdown(id) {
    const targetDropdown = document.getElementById(id);

    // Close other dropdowns
    document.querySelectorAll('.dropdown').forEach(drop => {
        if (drop !== targetDropdown) drop.style.display = 'none';
    });

    // Toggle current dropdown
    if (targetDropdown) {
        const isVisible = targetDropdown.style.display === 'block';
        targetDropdown.style.display = isVisible ? 'none' : 'block';
    }
}

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.category')) {
            document.querySelectorAll('.dropdown').forEach(drop => {
                drop.style.display = 'none';
            });
        }
    });

    // === Dropdown Toggle for Service Categories ===
    const categoryButtons = document.querySelectorAll('.category-btn');

    categoryButtons.forEach(button => {
        button.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const targetDropdown = document.getElementById(targetId);

            // Close other dropdowns
            document.querySelectorAll('.dropdown').forEach(drop => {
                if (drop !== targetDropdown) drop.style.display = 'none';
            });

            // Toggle current dropdown
            if (targetDropdown) {
                const isVisible = targetDropdown.style.display === 'block';
                targetDropdown.style.display = isVisible ? 'none' : 'block';
            }
        });
    });

    // Close dropdowns on outside click
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.category')) {
            document.querySelectorAll('.dropdown').forEach(drop => {
                drop.style.display = 'none';
            });
        }
    });

    // Calendar Section 
    const calendarBody = document.getElementById("calendar-body");
    const calendarHeader = document.querySelector(".calendar-header h3");
    const prevBtn = document.getElementById("prevMonth");
    const nextBtn = document.getElementById("nextMonth");
    const todayBtn = document.getElementById("today");

    let today = new Date();
    let currentMonth = today.getMonth();
    let currentYear = today.getFullYear();

    const events = {
        "2025-05-01": { title: "Labor Day", type: "holiday" },
        "2025-05-12": { title: "Natonal and Local Election Day", type: "election" }
    };

    function updateCalendar(month, year) {
    currentMonth = month;
    currentYear = year;

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDay = firstDay.getDay(); // 0 (Sun) - 6 (Sat)
    const daysInMonth = lastDay.getDate();
    const prevMonthLastDate = new Date(year, month, 0).getDate();

    const monthNames = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];
    calendarHeader.textContent = `${monthNames[month]} ${year}`;
    calendarBody.innerHTML = "";

    let date = 1;
    let nextMonthDate = 1;
    let dayCounter = 0;

    // Calculate total cells needed (35 or 42)
    const totalCells = (startDay + daysInMonth) > 35 ? 42 : 35;

    for (let i = 0; i < totalCells / 7; i++) {
        const row = document.createElement("tr");

    for (let j = 0; j < 7; j++) {
        const cell = document.createElement("td");
        const dayNumber = document.createElement("span");
        dayNumber.classList.add("day-number");
        const now = new Date();

        let cellDateKey;

        if (dayCounter < startDay) {
        // Fill previous month's end
            const prevDate = prevMonthLastDate - startDay + j + 1;
            dayNumber.textContent = prevDate;
            cell.classList.add("prev-month");

        } else if (date <= daysInMonth) {
        // Current month dates
            dayNumber.textContent = date;

            const paddedMonth = String(month + 1).padStart(2, '0');
            const paddedDate = String(date).padStart(2, '0');
            cellDateKey = `${year}-${paddedMonth}-${paddedDate}`;

        if (
            date === now.getDate() &&
            month === now.getMonth() &&
            year === now.getFullYear()
        ) {
            cell.classList.add("today");

            const label = document.createElement("div");
            label.textContent = "Present day!";
            label.classList.add("present-label");
            cell.appendChild(label);
        }

        if (events[cellDateKey]) {
            const dot = document.createElement("div");
            dot.classList.add("event-dot");
            cell.classList.add("has-event", events[cellDateKey].type);

            const tooltip = document.createElement("div");
            tooltip.classList.add("event-tooltip");
            tooltip.innerHTML = `
                <p><strong>${events[cellDateKey].title}</strong></p>`;
            cell.appendChild(dot);
            cell.appendChild(tooltip);
        }

        cell.addEventListener("click", () => {
            alert(`You clicked on ${cellDateKey}`);
        });

        date++;

        } else {
        // Fill next month's beginning
            dayNumber.textContent = nextMonthDate++;
            cell.classList.add("next-month");
        }

        cell.appendChild(dayNumber);
        row.appendChild(cell);
        dayCounter++;
    }

        calendarBody.appendChild(row);
        }
    }

    // Navigation
    prevBtn.addEventListener("click", () => {
        currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
        updateCalendar(currentMonth, currentYear);
    });

    nextBtn.addEventListener("click", () => {
        currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
        updateCalendar(currentMonth, currentYear);
    });

    todayBtn.addEventListener("click", () => {
        const now = new Date();
        currentMonth = now.getMonth();
        currentYear = now.getFullYear();
            updateCalendar(currentMonth, currentYear);
    });

    // Initial load
    updateCalendar(currentMonth, currentYear);


    // Close mobile menu on outside click
    document.addEventListener('click', function (e) {
        if (mainNav.classList.contains('mobile-active') &&
            !e.target.closest('.main-navigation') &&
            !e.target.closest('.mobile-menu-toggle')) {
            mainNav.classList.remove('mobile-active');
        }
    });

    // Smooth Scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                window.scrollTo({
                    top: target.offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Sticky Header
    const header = document.querySelector('.site-header');
    const headerOffset = header ? header.offsetTop : 0;

    function stickyHeader() {
        if (window.pageYOffset > headerOffset) {
            header.classList.add('sticky');
        } else {
            header.classList.remove('sticky');
        }
    }

    if (header) {
        window.addEventListener('scroll', stickyHeader);
    }

    // === Quick Links Tab Switching ===
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function () {
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Add active class to the clicked button and corresponding content
            this.classList.add('active');
            const targetId = this.getAttribute('data-tab');
            const targetContent = document.getElementById(targetId);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });
});
