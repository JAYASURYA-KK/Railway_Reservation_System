// Train Booking System - JavaScript Functions

// Modal Functions
function openBookingForm(trainNo) {
    document.getElementById('train_no').value = trainNo;
    document.getElementById('bookingModal').style.display = 'flex';
}

function closeBookingForm() {
    document.getElementById('bookingModal').style.display = 'none';
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('bookingModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

// Navigation Section Switching
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links
            navLinks.forEach(l => l.classList.remove('active'));
            
            // Add active class to current link
            this.classList.add('active');
            
            // Get the action from href
            const href = this.getAttribute('href');
            const action = new URLSearchParams(href.split('?')[1]).get('action');
            
            // Show/hide sections based on action
            showSection(action);
        });
    });
});

function showSection(action) {
    // Hide all sections
    document.getElementById('search-section').style.display = 'none';
    document.getElementById('register-section').style.display = 'none';
    document.getElementById('bookings-section').style.display = 'none';
    
    // Show selected section
    switch(action) {
        case 'search':
            document.getElementById('search-section').style.display = 'block';
            break;
        case 'register':
            document.getElementById('register-section').style.display = 'block';
            break;
        case 'bookings':
            document.getElementById('bookings-section').style.display = 'block';
            break;
        default:
            document.getElementById('search-section').style.display = 'block';
    }
}

// Form Validation
function validateSearchForm(form) {
    const source = form.source.value.trim();
    const destination = form.destination.value.trim();
    const travelDate = form.travel_date.value;
    
    if (!source || !destination || !travelDate) {
        alert('Please fill in all fields');
        return false;
    }
    
    if (source === destination) {
        alert('Source and destination cannot be the same');
        return false;
    }
    
    const selectedDate = new Date(travelDate);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (selectedDate < today) {
        alert('Please select a future date');
        return false;
    }
    
    return true;
}

function validateRegisterForm(form) {
    const name = form.name.value.trim();
    const email = form.email.value.trim();
    const city = form.city.value.trim();
    const state = form.state.value.trim();
    const pincode = form.pincode.value.trim();
    const gender = form.gender.value;
    
    if (!name || !email || !city || !state || !pincode || !gender) {
        alert('Please fill in all fields');
        return false;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address');
        return false;
    }
    
    // Pincode validation (assuming 6 digits for Indian pincode)
    const pincodeRegex = /^\d{6}$/;
    if (!pincodeRegex.test(pincode)) {
        alert('Please enter a valid 6-digit pincode');
        return false;
    }
    
    return true;
}

function validateBookingForm(form) {
    const userId = form.user_id.value;
    const passengerId = form.passenger_id.value;
    const travelDate = form.travel_date.value;
    const trainClass = form.class.value;
    
    if (!userId || !passengerId || !travelDate || !trainClass) {
        alert('Please fill in all fields');
        return false;
    }
    
    const selectedDate = new Date(travelDate);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (selectedDate < today) {
        alert('Please select a future date');
        return false;
    }
    
    return true;
}

// Form submission handlers
const forms = document.querySelectorAll('form');
forms.forEach(form => {
    form.addEventListener('submit', function(e) {
        const action = this.action ? this.action : this.querySelector('input[name="action"]').value;
        
        if (action === 'search_trains') {
            if (!validateSearchForm(this)) {
                e.preventDefault();
            }
        } else if (action === 'register_user') {
            if (!validateRegisterForm(this)) {
                e.preventDefault();
            }
        } else if (action === 'book_ticket') {
            if (!validateBookingForm(this)) {
                e.preventDefault();
            }
        }
    });
});

// Date input - set minimum date to today
document.addEventListener('DOMContentLoaded', function() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    const todayStr = `${year}-${month}-${day}`;
    
    dateInputs.forEach(input => {
        input.setAttribute('min', todayStr);
    });
});

// Search suggestions
function getStations() {
    // This would normally fetch from the server
    return [
        'Delhi', 'Mumbai', 'Bangalore', 'Kolkata', 'Hyderabad'
    ];
}

// Real-time search filtering
const sourceSelect = document.querySelector('select[name="source"]');
const destinationSelect = document.querySelector('select[name="destination"]');

if (sourceSelect && destinationSelect) {
    sourceSelect.addEventListener('change', function() {
        const source = this.value;
        // Disable destination if same as source
        if (destinationSelect) {
            Array.from(destinationSelect.options).forEach(option => {
                if (option.value === source && option.value !== '') {
                    option.disabled = true;
                } else {
                    option.disabled = false;
                }
            });
        }
    });
}

// Print ticket functionality
function printTicket(pnr) {
    const printWindow = window.open('', '', 'width=800,height=600');
    const ticketRow = document.querySelector(`tr[data-pnr="${pnr}"]`);
    
    if (ticketRow) {
        const content = `
            <html>
                <head>
                    <title>Ticket - ${pnr}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .ticket { border: 2px solid #2563eb; padding: 20px; border-radius: 8px; }
                        .header { text-align: center; margin-bottom: 20px; }
                        .info { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
                        .label { font-weight: bold; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="ticket">
                        <div class="header"><h1>Train Ticket</h1></div>
                        <div class="info">
                            <div><span class="label">PNR:</span> ${pnr}</div>
                            <div><span class="label">Train:</span> ${ticketRow.cells[2].textContent}</div>
                            <div><span class="label">Passenger:</span> ${ticketRow.cells[3].textContent}</div>
                            <div><span class="label">Status:</span> ${ticketRow.cells[5].textContent}</div>
                        </div>
                    </div>
                </body>
            </html>
        `;
        printWindow.document.write(content);
        printWindow.document.close();
        printWindow.print();
    }
}

// Format date to DD/MM/YYYY
function formatDate(dateString) {
    const options = { year: 'numeric', month: '2-digit', day: '2-digit' };
    return new Date(dateString).toLocaleDateString('en-IN', options);
}

// Convert time to 12-hour format
function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

// Display formatted times on page load
document.addEventListener('DOMContentLoaded', function() {
    const timeCells = document.querySelectorAll('td');
    timeCells.forEach(cell => {
        const text = cell.textContent;
        // Check if cell contains time in HH:MM:SS format
        if (/^\d{2}:\d{2}:\d{2}$/.test(text.trim())) {
            cell.textContent = formatTime(text.trim());
        }
    });
});

// Loading indicator
function showLoading(buttonElement) {
    buttonElement.disabled = true;
    buttonElement.innerHTML = '<span class="loading"></span> Processing...';
}

function hideLoading(buttonElement, originalText) {
    buttonElement.disabled = false;
    buttonElement.innerHTML = originalText;
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // ESC to close modal
    if (e.key === 'Escape') {
        closeBookingForm();
    }
});

// Search state management
let searchState = {
    lastSearch: null,
    filters: {}
};

function updateSearchState(source, destination, date) {
    searchState.lastSearch = {
        source: source,
        destination: destination,
        date: date,
        timestamp: new Date()
    };
    // Save to localStorage for session persistence
    localStorage.setItem('lastSearch', JSON.stringify(searchState.lastSearch));
}

function restoreLastSearch() {
    const saved = localStorage.getItem('lastSearch');
    if (saved) {
        try {
            searchState.lastSearch = JSON.parse(saved);
            // Check if search is still valid (less than 1 hour old)
            const searchTime = new Date(searchState.lastSearch.timestamp);
            const now = new Date();
            if ((now - searchTime) < 3600000) {
                // Restore search fields
                const sourceSelect = document.querySelector('select[name="source"]');
                const destSelect = document.querySelector('select[name="destination"]');
                const dateInput = document.querySelector('input[name="travel_date"]');
                
                if (sourceSelect) sourceSelect.value = searchState.lastSearch.source;
                if (destSelect) destSelect.value = searchState.lastSearch.destination;
                if (dateInput) dateInput.value = searchState.lastSearch.date;
            }
        } catch (e) {
            console.error('Error restoring search state:', e);
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    restoreLastSearch();
});
