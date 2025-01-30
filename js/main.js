// Main JavaScript file for Student Management System

// Enable Bootstrap tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
});

// Form validation
function validateForm(formId) {
    'use strict'
    const form = document.getElementById(formId);
    if (!form) return true;

    if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }
    form.classList.add('was-validated');
    return form.checkValidity();
}

// Dynamic table search
function tableSearch(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;

    input.addEventListener('keyup', function() {
        const filter = input.value.toLowerCase();
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            const cells = rows[i].getElementsByTagName('td');
            let found = false;

            for (let j = 0; j < cells.length; j++) {
                const cell = cells[j];
                if (cell) {
                    const text = cell.textContent || cell.innerText;
                    if (text.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            rows[i].style.display = found ? '' : 'none';
        }
    });
}

// Attendance marking system
function markAttendance(studentId, status) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'php/mark_attendance.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            if (response.success) {
                showAlert('success', 'Attendance marked successfully!');
            } else {
                showAlert('danger', 'Error marking attendance: ' + response.message);
            }
        }
    };
    
    xhr.send(`student_id=${studentId}&status=${status}`);
}

// Alert system
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// File upload preview
function previewFile(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        const preview = document.getElementById('imagePreview');
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Grade calculation
function calculateGrade(marks) {
    if (marks >= 90) return 'A+';
    else if (marks >= 80) return 'A';
    else if (marks >= 70) return 'B';
    else if (marks >= 60) return 'C';
    else if (marks >= 50) return 'D';
    else return 'F';
}

// Print function
function printContent(elementId) {
    const content = document.getElementById(elementId);
    const printWindow = window.open('', '', 'height=600,width=800');
    
    printWindow.document.write('<html><head><title>Print</title>');
    printWindow.document.write('<link href="css/style.css" rel="stylesheet">');
    printWindow.document.write('</head><body>');
    printWindow.document.write(content.innerHTML);
    printWindow.document.write('</body></html>');
    
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}

// Export to Excel
function exportToExcel(tableId, filename = 'data') {
    const table = document.getElementById(tableId);
    const wb = XLSX.utils.table_to_book(table, {sheet: "Sheet JS"});
    XLSX.writeFile(wb, filename + '.xlsx');
}

// Document ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize table search if search input exists
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        tableSearch('tableSearch', 'dataTable');
    }
    
    // Initialize any date pickers
    const datePickers = document.querySelectorAll('.datepicker');
    datePickers.forEach(function(picker) {
        new Datepicker(picker, {
            format: 'yyyy-mm-dd',
            autohide: true
        });
    });
});
